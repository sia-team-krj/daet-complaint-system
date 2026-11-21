<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * The super admin's landing-page FAQ manager.
 *
 * FAQ copy is municipal data now, not translation keys. Archive is the normal
 * removal: it hides the row from the public page and keeps the text for a
 * later restore. Permanent delete is a separate, confirmed action because
 * there is nothing behind an FAQ to preserve.
 */
class AdminFaqController extends Controller
{
    public function __construct(private ActivityLogger $activityLogger) {}

    public function index(Request $request): View
    {
        $status = $this->statusFrom($request);

        $activeCount = Faq::query()->where('is_active', true)->count();
        $archivedCount = Faq::query()->where('is_active', false)->count();

        $faqs = Faq::query()
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'archived', fn ($query) => $query->where('is_active', false))
            ->ordered()
            ->get();

        return view('admin.faqs.index', [
            'faqs' => $faqs,
            'status' => $status,
            'activeCount' => $activeCount,
            'archivedCount' => $archivedCount,
            'totalCount' => $activeCount + $archivedCount,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules(), [], $this->attributeNames());

        $faq = DB::transaction(function () use ($validated): Faq {
            $faq = Faq::create([
                ...$this->payload($validated),
                'sort_order' => $validated['sort_order'] ?? $this->nextSortOrder(),
                'is_active' => true,
            ]);

            $this->activityLogger->log(
                'faq.created',
                "FAQ \"{$faq->question_en}\" created.",
                $faq,
                metadata: [
                    'question_en' => $faq->question_en,
                    'sort_order' => $faq->sort_order,
                ],
            );

            return $faq;
        });

        return $this->redirectToIndex($request)
            ->with('status', __('admin_faqs.flash.created'))
            ->with('highlight', 'faq-'.$faq->id);
    }

    public function update(Request $request, Faq $faq): RedirectResponse
    {
        $validated = $request->validate($this->rules(), [], $this->attributeNames());

        DB::transaction(function () use ($faq, $validated): void {
            $faq->update([
                ...$this->payload($validated),
                'sort_order' => ($validated['sort_order'] ?? null) !== null
                    ? (int) $validated['sort_order']
                    : $faq->sort_order,
            ]);

            $this->activityLogger->log(
                'faq.updated',
                "FAQ \"{$faq->question_en}\" updated.",
                $faq,
                metadata: [
                    'question_en' => $faq->question_en,
                    'is_active' => $faq->is_active,
                    'sort_order' => $faq->sort_order,
                ],
            );
        });

        return $this->redirectToIndex($request)
            ->with('status', __('admin_faqs.flash.updated'))
            ->with('highlight', 'faq-'.$faq->id);
    }

    public function toggle(Request $request, Faq $faq): RedirectResponse
    {
        $restoring = ! $faq->is_active;

        DB::transaction(function () use ($faq, $restoring): void {
            $faq->update([
                'is_active' => $restoring,
                // A restored question joins the end of the public list. Keeping
                // its old position could put it in front of questions the
                // admin has since reordered.
                'sort_order' => $restoring ? $this->nextSortOrder() : $faq->sort_order,
            ]);

            $this->activityLogger->log(
                'faq.'.($restoring ? 'restored' : 'archived'),
                "FAQ \"{$faq->question_en}\" ".($restoring ? 'restored' : 'archived').'.',
                $faq,
                metadata: [
                    'question_en' => $faq->question_en,
                    'is_active' => $faq->is_active,
                    'sort_order' => $faq->sort_order,
                ],
            );
        });

        return $this->redirectToIndex($request)->with(
            'status',
            $restoring ? __('admin_faqs.flash.restored') : __('admin_faqs.flash.archived'),
        );
    }

    /**
     * Reorder within the published list. Renumbering the whole scope on every
     * move keeps the order stable even when old rows share a sort value.
     */
    public function move(Request $request, Faq $faq, string $direction): RedirectResponse
    {
        if (! $faq->is_active || ! in_array($direction, ['up', 'down'], true)) {
            return $this->redirectToIndex($request);
        }

        $ordered = Faq::query()
            ->where('is_active', true)
            ->ordered()
            ->get()
            ->values();

        $index = $ordered->search(fn (Faq $item): bool => $item->is($faq));
        $target = $direction === 'up' ? $index - 1 : $index + 1;

        if ($index === false || $target < 0 || $target >= $ordered->count()) {
            return $this->redirectToIndex($request);
        }

        DB::transaction(function () use ($ordered, $index, $target, $faq): void {
            $moving = $ordered->pull($index);
            $ordered->splice($target, 0, [$moving]);

            foreach ($ordered as $position => $item) {
                $item->update(['sort_order' => $position + 1]);
            }

            $this->activityLogger->log(
                'faq.reordered',
                "FAQ \"{$faq->question_en}\" moved {$target}.",
                $faq,
                metadata: [
                    'question_en' => $faq->question_en,
                    'position' => $target + 1,
                ],
            );
        });

        return $this->redirectToIndex($request)
            ->with('status', __('admin_faqs.flash.moved'))
            ->with('highlight', 'faq-'.$faq->id);
    }

    public function destroy(Request $request, Faq $faq): RedirectResponse
    {
        DB::transaction(function () use ($faq): void {
            $this->activityLogger->log(
                'faq.deleted',
                "FAQ \"{$faq->question_en}\" deleted permanently.",
                $faq,
                metadata: [
                    'question_en' => $faq->question_en,
                    'is_active' => $faq->is_active,
                    'sort_order' => $faq->sort_order,
                ],
            );

            $faq->delete();
        });

        return $this->redirectToIndex($request)
            ->with('status', __('admin_faqs.flash.deleted'));
    }

    /**
     * @return array<string, list<mixed>>
     */
    private function rules(): array
    {
        return [
            'question_en' => ['required', 'string', 'max:255'],
            'question_fil' => ['nullable', 'string', 'max:255'],
            'answer_en' => ['required', 'string', 'max:5000'],
            'answer_fil' => ['nullable', 'string', 'max:5000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function payload(array $validated): array
    {
        return [
            'question_en' => trim((string) $validated['question_en']),
            'question_fil' => $this->nullable($validated['question_fil'] ?? null),
            'answer_en' => trim((string) $validated['answer_en']),
            'answer_fil' => $this->nullable($validated['answer_fil'] ?? null),
        ];
    }

    private function nullable(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nextSortOrder(): int
    {
        return (int) (Faq::query()->max('sort_order') ?? 0) + 1;
    }

    private function statusFrom(Request $request): string
    {
        $status = (string) $request->input('status', $request->query('status', 'active'));

        return in_array($status, ['active', 'archived', 'all'], true) ? $status : 'active';
    }

    private function redirectToIndex(Request $request): RedirectResponse
    {
        return redirect()->route('admin.faqs.index', ['status' => $this->statusFrom($request)]);
    }

    /**
     * @return array<string, string>
     */
    private function attributeNames(): array
    {
        return [
            'question_en' => strtolower(__('admin_faqs.form.question_en')),
            'question_fil' => strtolower(__('admin_faqs.form.question_fil')),
            'answer_en' => strtolower(__('admin_faqs.form.answer_en')),
            'answer_fil' => strtolower(__('admin_faqs.form.answer_fil')),
            'sort_order' => strtolower(__('admin_faqs.form.sort_order')),
        ];
    }
}
