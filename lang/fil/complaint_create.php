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

    'fields' => [
        'named_party_person' => 'Pangalan ng taong pinagkakansitan',
        'named_party_business' => 'Pangalan ng negosyong pinagkakansitan',
        'named_party_hint_person' => 'Nakikita ito ng tanging tanggapan na humahawlak sa ulat na ito. Hindi ito ipapakita sa pampublikong pahina.',
        'named_party_hint_business' => 'Nakikita ito ng tanging tanggapan na humahawlak sa ulat na ito. Hindi ito ipapakita sa pampublikong pahina.',
        'named_party_position' => 'Ang kanyang posisyon o tanggapan',
        'named_party_position_hint' => 'Opsyonal. Nakakatulong ito sa tanggapan na mahanap ang tamang tao.',
    ],

    'visibility' => [
        'gated_title' => 'Binabanggit ng ulat na ito ang isang tao.',
        'gated_body' => 'Dahil binabanggit nito ang isang tao o negosyo, ito ay ipinapasa nang pribado. Iipublish lamang ito kung ayusin ito ng tanggapan, beripikado ang impormasyon bilang totoo, nai-redact ang sensitibong detalye, at aprubahan ito ng tanggapan ng Alkalde. Ise-info ka man kaya.',
        'gated_ack' => 'Nauunawaan ko na binabanggit ng ulat na ito ang isang tao o negosyo, at ito ay ipapasa nang pribado. Lalabas lamang ito sa transparency page kung ayusin ito ng tanggapan, beripikado ang impormasyon bilang totoo, nai-redact ang sensitibong detalye, at aprubahan ito ng tanggapan ng Alkalde. Sila na ang magsasabi sa akin, kahit ano ang maging desisyon.',
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
        'save_and_review' => 'I-save at suriin',
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
        'landmark_toggle_required' => '+ Magdagdag ng palatandaan',
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
            'label' => 'Basura, Kalinisan at Plastik',
            'helper' => 'Basura, imburnal, plastic na balot',
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
        'plastic_usage' => [
            'label' => 'Paggamit ng Plastik',
            'helper' => 'Negosyong gumagamit ng plastik sa produkto nito',
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
        'staff_treatment' => [
            'label' => 'Paano Ka Tinugunan ng LGU',
            'helper' => 'Paano ka tinugunan ng isang kawani',
        ],
        'others' => [
            'label' => 'Ibang mga Alalahanin',
            'helper' => 'Anumang hindi akma sa itaas',
        ],
    ],
];
