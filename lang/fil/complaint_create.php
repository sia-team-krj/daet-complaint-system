<?php

return [
    'sections' => [
        'step' => 'HAKBANG :number',
    ],
    'labels' => [
        'category' => 'Kategorya ng reklamo',
        'required' => 'kinakailangan',
        'optional' => 'opsyonal',
    ],

    'page' => [
        'title' => 'Maghain ng reklamo',
        'lede' => 'Sabihin sa amin kung ano ang kailangang tugunan. Ipapadala namin ito sa tamang tanggapan at ia-update ka.',
    ],

    'back' => 'Bumalik sa aking mga reklamo',

    'progress' => [
        'step' => 'Hakbang {n} sa {total}',
    ],

    'live' => [
        'step' => 'Hakbang {n} sa {total}: {name}',
    ],

    'steps' => [
        'group' => 'Uri ng problema',
        'category' => 'Kategorya',
        'describe' => 'Detalye',
        'photos' => 'Larawan',
        'where' => 'Lugar',
        'pin' => 'Pin sa mapa',
        'visibility' => 'Privacy',
        'review' => 'Suriin',
    ],

    'screens' => [
        'group' => [
            'title' => 'Anong uri ng problema ito?',
            'help' => 'Piliin ang pinakamalapit.',
        ],
        'category' => [
            'title' => 'Alin ang pinakaangkop?',
            'help' => 'Para maipadala ito sa tamang tanggapan.',
        ],
    ],

    'buttons' => [
        'back' => 'Bumalik',
        'next' => 'Susunod',
        'skip' => 'Laktawan',
        'continue' => 'Magpatuloy',
        'submit' => 'Isumite ang reklamo',
        'change' => 'Baguhin',
    ],

    'chip' => [
        'goes_to' => 'Mapupunta sa {office}',
    ],

    'hints' => [
        'group' => 'Pumili ng uri ng problema.',
        'category' => 'Piliin kung alin ang pinakaangkop.',
        'describe' => 'Maglagay ng maikling pamagat at hindi bababa sa 20 titik na detalye.',
        'photos' => 'Magdagdag ng kahit isang larawan.',
        'where' => 'Piliin ang inyong barangay.',
        'where_landmark' => 'Piliin ang inyong barangay at magdagdag ng palatandaan o address.',
        'review' => 'Lagyan ng tsek ang kahon upang kumpirmahin.',
    ],

    'map' => [
        'help' => 'I-click sa loob ng Daet upang maglagay ng marker, o maglagay ng malapit na palatandaan sa itaas.',
        'outside' => 'Ang puntong iyon ay nasa labas ng Daet. Ilagay ang pin sa loob ng Daet.',
        'previous_outside' => 'Ang dati mong pin ay nasa labas ng Daet at tinanggal. Maglagay ng bagong pin, o laktawan ang hakbang na ito.',
    ],

    'errors' => [
        'outside_daet' => 'Ang puntong iyon ay nasa labas ng Daet. Ilagay ang pin sa loob ng Daet, o laktawan ang mapa at gamitin ang address o palatandaan.',
    ],

    'draft' => [
        'banner' => 'Ituloy ang naunang draft?',
        'resume' => 'Ituloy',
        'reset' => 'Magsimulang muli',
    ],

    'wizard' => [
        'priority_toggle' => 'Paano pinagdedesisyunan ang prayoridad?',
        'take_photo' => 'Kumuha ng larawan',
        'choose_gallery' => 'Pumili mula sa gallery',
        'photo_tips' => 'Mga tip sa larawan',
        'landmark_toggle' => '+ Magdagdag ng palatandaan (opsyonal)',
        'why_toggle' => 'Bakit namin tinatanong',
        'edit' => 'Ayusin',
        'what_next' => 'Ano ang susunod',
        'next_1' => 'Pagsusuri ng departamento: sinusuri ang iyong ulat kung ito ay lehitimo at nasa saklaw.',
        'next_2' => 'Kumpirmadong prayoridad: kinukumpirma ng itinalagang tanggapan ang iminungkahing prayoridad.',
        'next_3' => 'Mga update ng status: maaari mong sundan ang progreso mula sa Aking Mga Reklamo.',
        'title_label' => 'Pamagat',
        'description_label' => 'Paglalarawan',
        'photos_label' => 'Mga larawan',
        'visibility_label' => 'Privacy',
        'desc_more' => '{count} pang karakter',
        'desc_good' => 'Mukhang maayos',
        'photos_required_help' => 'Kinakailangan. Magdagdag ng hanggang limang malinaw na larawan na kuha sa lugar ng isyu.',
        'photos_optional_help' => 'Opsyonal, ngunit inirerekomenda para sa mas magandang impormasyon. Magdagdag ng hanggang limang malinaw na larawan kung mayroon.',
    ],

    'groups' => [
        'roads' => [
            'label' => 'Kalsada at Kanal',
            'helper' => 'Lubak, ilaw sa kalye, baha',
        ],
        'waste' => [
            'label' => 'Basura at Kalinisan',
            'helper' => 'Basura, imburnal, mabahong amoy',
        ],
        'spaces' => [
            'label' => 'Parke at Pampublikong Lugar',
            'helper' => 'Parke, plaza, palaruan',
        ],
        'neighborhood' => [
            'label' => 'Ingay at Hayop',
            'helper' => 'Ingay, gala-galang hayop',
        ],
        'business' => [
            'label' => 'Negosyo at Permit',
            'helper' => 'Permit at lisensya',
        ],
        'other' => [
            'label' => 'Iba pa',
            'helper' => 'Anumang iba pa',
        ],
    ],

    'leaves' => [
        'road_damage' => [
            'label' => 'Sirang Kalsada',
            'helper' => 'Lubak, bitak, bumagsak na kalsada',
        ],
        'flooding' => [
            'label' => 'Baha / Kanal',
            'helper' => 'Baha, barado na kanal',
        ],
        'streetlight' => [
            'label' => 'Problema sa Ilaw sa Kalye',
            'helper' => 'Patay o kumukurap na ilaw',
        ],
        'garbage' => [
            'label' => 'Koleksyon ng Basura',
            'helper' => 'Hindi nakolektang basura, tambak na basura',
        ],
        'sanitation' => [
            'label' => 'Kalinisan',
            'helper' => 'Mabahong amoy, imburnal, maruming lugar',
        ],
        'park_maintenance' => [
            'label' => 'Pagpapanatili ng Parke',
            'helper' => 'Sirang upuan, palaruan, plaza',
        ],
        'noise_complaint' => [
            'label' => 'Reklamo sa Ingay',
            'helper' => 'Maingay na musika, karaoke, konstruksyon',
        ],
        'stray_animals' => [
            'label' => 'Gala-galang na Hayop',
            'helper' => 'Gala-galang aso o pusa',
        ],
        'business_permit' => [
            'label' => 'Problema sa Business Permit',
            'helper' => 'Problema sa permit, lisensya o negosyo',
        ],
        'others' => [
            'label' => 'Ibang mga Alalahanin',
            'helper' => 'Anumang hindi akma sa itaas',
        ],
    ],
];
