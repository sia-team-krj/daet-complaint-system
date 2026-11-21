<?php

return [
    'page' => [
        'title' => 'Mga Kategorya ng Reklamo',
        'subtitle' => 'Ayusin ang mga problemang maaaring iulat ng residente, ang tanggapan na tatanggap sa bawat isa, at ang hinihingi ng form.',
    ],

    'staff' => [
        'title' => 'Mga Kategorya ng Reklamo',
        'subtitle' => 'Pamahalaan ang mga kategoryang maaaring piliin ng residente para sa :office. Ang mga uri ng problema ay ibinabahagi sa buong munisipyo.',
        'scope' => 'Ipinapakita ang mga kategoryang napupunta sa :office.',
        'back' => 'Bumalik sa opisina',
        'no_problem_types' => 'Wala pang uri ng problema na ginawa ang super admin. Makakapagdagdag ka ng kategorya kapag mayroon na.',
    ],

    'filters' => [
        'label' => 'Katayuan ng kategorya',
        'active' => 'Aktibo',
        'archived' => 'Naka-archive',
        'all' => 'Lahat',
    ],

    'problem_types' => [
        'title' => 'Mga Uri ng Problema',
        'hint' => 'Ang mga pamagat na unang nakikita ng residente. Kapag na-archive ang isa, nakatago ang lahat ng kategorya sa ilalim nito, ngunit hindi nagbabago ang mga naiulat na.',
        'empty' => 'Wala pang uri ng problema. Gumawa ng isa para simulan ang filing form.',
        'archived' => 'Naka-archive ang uri ng problema',
        'archived_hint' => 'Nakatago sa residente ang mga kategorya nito. Ang mga naiulat na ay may label at kasaysayan pa rin.',
        'category_count' => ':count kategorya|:count mga kategorya',
    ],

    'categories' => [
        'title' => 'Mga Kategorya',
        'none_in_group' => 'Wala pang kategorya sa uri ng problemang ito.',
        'active' => 'Aktibo',
        'archived' => 'Naka-archive',
        'hidden_by_group' => 'Nakatago dahil sa uri ng problema',
        'filed_count' => ':count reklamong naitala|:count mga reklamong naitala',
        'archive_hint' => 'Kapag na-archive, matatanggal ito sa filing form. Ang mga naiulat na ay may label, tanggapan at kasaysayan pa rin.',
        'key' => 'Reference key',
        'key_hint' => 'Ginawa nang isang beses mula sa English label at hindi na nagbabago. Pinapanatili nitong nakaugnay ang lumang mga reklamo kapag pinalitan mo ang pangalan ng kategorya.',
        'no_office' => 'Walang tanggapang opisina',
        'fallback_office' => 'Tinatanggap sa pamamagitan ng Mayor\'s Office',
    ],

    'actions' => [
        'add_problem_type' => 'Magdagdag ng uri ng problema',
        'add_category' => 'Magdagdag ng kategorya',
        'edit' => 'I-edit',
        'close' => 'Isara',
        'archive' => 'I-archive',
        'restore' => 'Ibalik',
        'save' => 'I-save ang mga pagbabago',
        'create_problem_type' => 'Gumawa ng uri ng problema',
        'create_category' => 'Gumawa ng kategorya',
    ],

    'form' => [
        'problem_type_create_title' => 'Bagong uri ng problema',
        'problem_type_create_helper' => 'Isang pamagat sa picker ng residente. Pinagsasama-sama nito ang magkakaugnay na kategorya ngunit hindi ito tumatanggap ng reklamo.',
        'problem_type_edit_title' => 'I-edit ang uri ng problema',
        'category_create_title' => 'Bagong kategorya',
        'category_create_helper' => 'Isang problemang maaaring piliin ng residente, kasama ang tanggapang opisina at hinihingi ng form.',
        'category_edit_title' => 'I-edit ang kategorya',
        'label_en' => 'English na label',
        'label_fil' => 'Filipino na label',
        'label_hint' => 'Kailangan ang English. Lumalabas ang Filipino kapag Filipino ang gamit ng residente.',
        'helper_en' => 'English na helper text',
        'helper_fil' => 'Filipino na helper text',
        'helper_hint' => 'Opsyonal. Isang maikling pangungusap sa ilalim ng kategorya sa tile ng residente.',
        'group' => 'Uri ng problema',
        'group_hint' => 'Ang pamagat kung saan lalabas ang kategoryang ito.',
        'office' => 'Tatanggap na opisina',
        'office_hint' => 'Ang opisinang magbe-verify at gagawa ng mga reklamong nakatala sa kategoryang ito.',
        'icon' => 'Icon',
        'color' => 'Kulay',
        'icon_hint' => 'Mula sa shared icon set, hindi sa label.',
        'color_hint' => 'Isang tint para sa bilog ng kategorya.',
        'intake' => 'Hinihingi ng form',
        'intake_hint' => 'Pangunahing intake behavior. Ang work rules at publication policy ay nananatili sa kasalukuyang default.',
        'sort_order' => 'Ayos',
        'sort_order_hint' => 'Mas maliit na numero, mas nauuna. Kapag blangko, idadagdag sa dulo.',
        'optional' => 'opsyonal',
        'choose_icon' => 'Pumili ng icon',
        'choose_color' => 'Pumili ng kulay',
        'preview' => 'Preview ng tile ng residente',
        'preview_hint' => 'Ganito lumalabas ang kategorya sa picker.',
        'no_office' => 'Pumili ng opisina',
        'no_group' => 'Pumili ng uri ng problema',
    ],

    'icons' => [
        'construction' => 'Kalsada / konstruksyon',
        'waves' => 'Pagbaha',
        'lightbulb' => 'Ilaw sa kalye',
        'trash-2' => 'Basura',
        'spray-can' => 'Sanitasyon',
        'leaf' => 'Plastik / kalikasan',
        'trees' => 'Parke / puno',
        'volume-2' => 'Ingay',
        'paw-print' => 'Gumagalang hayop',
        'briefcase' => 'Negosyo',
        'file-badge' => 'Kawani',
        'users' => 'Mga tao',
        'circle-help' => 'Iba pa',
    ],

    'colors' => [
        'roads' => 'Amber ng kalsada',
        'flood' => 'Asul ng baha',
        'streetlight' => 'Ginto ng ilaw',
        'waste' => 'Berde ng basura',
        'sanitation' => 'Teal ng sanitasyon',
        'spaces' => 'Lime ng parke',
        'neighborhood' => 'Violet ng kapitbahayan',
        'noise' => 'Violet ng ingay',
        'business' => 'Cyan ng negosyo',
        'personnel' => 'Rosas ng kawani',
        'stray' => 'Orange ng hayop',
        'other' => 'Slate ng iba pa',
    ],

    'intake_modes' => [
        'physical_required' => [
            'label' => 'Larawan at lokasyon kailangan',
            'helper' => 'Humingi ng 1–5 larawan at pin sa mapa o address ng barangay.',
        ],
        'proof_optional' => [
            'label' => 'Lokasyon kailangan, larawan opsyonal',
            'helper' => 'Humingi ng barangay at landmark; malugod na tinatanggap ang larawan ngunit hindi kailangan.',
        ],
        'sensitive_intake' => [
            'label' => 'Walang larawan o lokasyon',
            'helper' => 'Itago ang hakbang sa larawan at lokasyon. Para sa mga reklamong sensitibo sa pag-file.',
        ],
        'follow_up_optional' => [
            'label' => 'Walang larawan o lokasyon; follow-up ng staff',
            'helper' => 'Panatilihing maikli ang intake. Maaaring humingi ng detalye ang opisina mamaya.',
        ],
    ],

    'empty' => [
        'title' => 'Wala pang kategorya',
        'body' => 'Gumawa ng uri ng problema, pagkatapos magdagdag ng mga kategoryang maaaring piliin ng residente. Hanggang doon, wala pang maiaalok ang filing form.',
        'filter' => 'Walang tugma sa filter na ito.',
    ],

    'flash' => [
        'problem_type_created' => 'Nagawa ang uri ng problema.',
        'problem_type_updated' => 'Na-update ang uri ng problema.',
        'problem_type_archived' => 'Naka-archive na ang uri ng problema. Nakatago na sa residente ang mga kategorya nito.',
        'problem_type_restored' => 'Naibalik na ang uri ng problema. Makikita na muli ang aktibong mga kategorya nito.',
        'category_created' => 'Nagawa ang kategorya. Aktibo na ito sa filing form.',
        'category_updated' => 'Na-update ang kategorya.',
        'category_archived' => 'Naka-archive na ang kategorya. May label pa rin ang mga naiulat na.',
        'category_restored' => 'Naibalik na ang kategorya at makikita na muli sa filing form.',
    ],

    'validation' => [
        'problem_type_duplicate' => 'Nagawa na ang uri ng problemang ito.',
        'category_duplicate_office' => 'Nagawa na ang kategoryang ito para sa opisinang ito.',
    ],
];
