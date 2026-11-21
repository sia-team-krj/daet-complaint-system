<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Makes the landing-page FAQs municipal data instead of translation keys.
 *
 * The four questions residents already see are copied into the table in the
 * same order, so the public page does not change on deploy. After this, the
 * super admin can add, edit, archive, restore, reorder and delete them without
 * a code change.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question_en');
            $table->string('question_fil')->nullable();
            $table->text('answer_en');
            $table->text('answer_fil')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        $now = now();

        DB::table('faqs')->insert([
            [
                'question_en' => 'Do I need an account?',
                'question_fil' => 'Kailangan ko ba ng account?',
                'answer_en' => 'Yes, a free account lets you track your complaint and get updates.',
                'answer_fil' => 'Oo. Libre ang account at dito mo masusubaybayan ang reklamo mo.',
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'question_en' => 'Will my name be public?',
                'question_fil' => 'Magiging pampubliko ba ang pangalan ko?',
                'answer_en' => 'No. Public pages show only the category, barangay, status and date.',
                'answer_fil' => 'Hindi. Kategorya, barangay, status at petsa lang ang makikita sa publiko.',
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'question_en' => 'How long will it take?',
                'question_fil' => 'Gaano katagal ito?',
                'answer_en' => 'It depends on the issue and the office. You can see every update on your tracking page.',
                'answer_fil' => 'Depende ito sa isyu at sa tanggapan. Makikita mo ang bawat update sa tracking page mo.',
                'sort_order' => 3,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'question_en' => 'Can I use Filipino?',
                'question_fil' => 'Puwede bang gumamit ng Filipino?',
                'answer_en' => 'Yes. Switch between English and Filipino at the top of the page.',
                'answer_fil' => 'Oo. Puwede kang lumipat sa pagitan ng English at Filipino sa itaas ng pahina.',
                'sort_order' => 4,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
