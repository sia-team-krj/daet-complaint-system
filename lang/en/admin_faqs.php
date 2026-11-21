<?php

/*
 * The super admin's landing-page FAQ manager.
 * English is required; Filipino is optional and falls back to English.
 */

return [

    'page' => [
        'title' => 'Frequently asked questions',
        'description' => 'Write the questions residents ask before they file. Published answers appear on the landing page in this order.',
    ],

    'filters' => [
        'label' => 'FAQ status filter',
        'active' => 'Published',
        'archived' => 'Archived',
        'all' => 'All',
    ],

    'create' => [
        'title' => 'Add a question',
        'description' => 'English is required. Filipino is optional and falls back to English when empty.',
    ],

    'form' => [
        'question_en' => 'Question (English)',
        'question_fil' => 'Question (Filipino)',
        'answer_en' => 'Answer (English)',
        'answer_fil' => 'Answer (Filipino)',
        'sort_order' => 'Sort order',
        'sort_order_hint' => 'Leave blank to place it at the end. Lower numbers appear first.',
        'optional' => 'Optional',
        'fallback_hint' => 'Leave a Filipino field empty to show the English text instead.',
    ],

    'actions' => [
        'add' => 'Add question',
        'save' => 'Save changes',
        'edit' => 'Edit',
        'archive' => 'Archive',
        'restore' => 'Restore',
        'delete' => 'Delete permanently',
        'move_up' => 'Move up',
        'move_down' => 'Move down',
    ],

    'status' => [
        'active' => 'Published',
        'archived' => 'Archived',
    ],

    'order' => [
        'label' => 'Position :order',
    ],

    'list' => [
        'title' => 'Question list',
        'active' => 'These answers are live on the landing page, in the order shown.',
        'archived' => 'Archived questions are hidden from the landing page and keep their text until you delete them.',
        'all' => 'Published and archived questions, in display order.',
    ],

    'archive' => [
        'confirm_lead' => 'Hide',
        'confirm_tail' => 'from the landing page? The question and answer are kept, and you can restore it later.',
        'confirm_button' => 'Archive question',
    ],

    'delete' => [
        'confirm_lead' => 'Permanently delete',
        'confirm_tail' => 'This removes the question and answer for good. It cannot be undone. Archive it instead if you only want to hide it.',
        'confirm_button' => 'Delete permanently',
    ],

    'empty' => [
        'active' => 'No questions are published yet. Add one above to show it on the landing page.',
        'archived' => 'No questions have been archived.',
        'all' => 'No questions have been written yet. Add one above to start.',
    ],

    'flash' => [
        'created' => 'FAQ added.',
        'updated' => 'FAQ updated.',
        'archived' => 'FAQ archived. It is hidden from the landing page.',
        'restored' => 'FAQ restored. It is live again at the end of the list.',
        'moved' => 'FAQ order updated.',
        'deleted' => 'FAQ deleted permanently.',
    ],

];
