<?php

/*
 * Ang pamamahala ng FAQ sa landing page para sa super admin.
 * Kailangan ang Ingles; opsyonal ang Filipino at Ingles ang gagamitin kapag
 * walang laman.
 */

return [

    'page' => [
        'title' => 'Mga madalas itanong',
        'description' => 'Isulat ang mga tanong ng mga residente bago sila maghain. Lumalabas ang mga naka-publish na sagot sa landing page sa ayos na ito.',
    ],

    'filters' => [
        'label' => 'Filter ng status ng FAQ',
        'active' => 'Naka-publish',
        'archived' => 'Naka-archive',
        'all' => 'Lahat',
    ],

    'create' => [
        'title' => 'Magdagdag ng tanong',
        'description' => 'Kailangan ang Ingles. Opsyonal ang Filipino at Ingles ang gagamitin kapag walang laman.',
    ],

    'form' => [
        'question_en' => 'Tanong (Ingles)',
        'question_fil' => 'Tanong (Filipino)',
        'answer_en' => 'Sagot (Ingles)',
        'answer_fil' => 'Sagot (Filipino)',
        'sort_order' => 'Ayos',
        'sort_order_hint' => 'Iwanang blangko para mailagay sa dulo. Mas maliit na numero ang nauuna.',
        'optional' => 'Opsyonal',
        'fallback_hint' => 'Iwanang blangko ang Filipino para Ingles ang ipakita.',
    ],

    'actions' => [
        'add' => 'Magdagdag ng tanong',
        'save' => 'I-save ang mga pagbabago',
        'edit' => 'I-edit',
        'archive' => 'I-archive',
        'restore' => 'Ibalik',
        'delete' => 'Burahin nang tuluyan',
        'move_up' => 'Ilipat pataas',
        'move_down' => 'Ilipat pababa',
    ],

    'status' => [
        'active' => 'Naka-publish',
        'archived' => 'Naka-archive',
    ],

    'order' => [
        'label' => 'Posisyon :order',
    ],

    'list' => [
        'title' => 'Listahan ng tanong',
        'active' => 'Live ang mga sagot na ito sa landing page, sa ayos na nakikita.',
        'archived' => 'Nakatago sa landing page ang mga naka-archive na tanong at nananatili ang teksto hanggang burahin mo.',
        'all' => 'Naka-publish at naka-archive na mga tanong, sa ayos ng pagpapakita.',
    ],

    'archive' => [
        'confirm_lead' => 'Itago ang',
        'confirm_tail' => 'mula sa landing page? Nananatili ang tanong at sagot, at maaari mo itong ibalik mamaya.',
        'confirm_button' => 'I-archive ang tanong',
    ],

    'delete' => [
        'confirm_lead' => 'Burahin nang tuluyan ang',
        'confirm_tail' => 'Aalisin nito nang tuluyan ang tanong at sagot. Hindi na ito maibabalik. I-archive na lang kung gusto mo lamang itong itago.',
        'confirm_button' => 'Burahin nang tuluyan',
    ],

    'empty' => [
        'active' => 'Wala pang naka-publish na tanong. Magdagdag sa itaas para lumabas ito sa landing page.',
        'archived' => 'Wala pang naka-archive na tanong.',
        'all' => 'Wala pang naisulat na tanong. Magdagdag sa itaas para magsimula.',
    ],

    'flash' => [
        'created' => 'Naidagdag ang FAQ.',
        'updated' => 'Na-update ang FAQ.',
        'archived' => 'Naka-archive ang FAQ. Nakatago ito sa landing page.',
        'restored' => 'Naibalik ang FAQ. Live ito muli sa dulo ng listahan.',
        'moved' => 'Na-update ang ayos ng FAQ.',
        'deleted' => 'Nabura nang tuluyan ang FAQ.',
    ],

];
