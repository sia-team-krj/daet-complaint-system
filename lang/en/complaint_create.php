<?php

return [
    'sections' => [
        'step' => 'STEP :number',
    ],
    'labels' => [
        'category' => 'Complaint category',
        'required' => 'required',
        'optional' => 'optional',
    ],

    'fields' => [
        'named_party_person' => 'Name of the person concerned',
        'named_party_business' => 'Name of the business concerned',
        'named_party_hint_person' => 'Seen by the office handling this report and by the Mayor\'s office. It is never shown on the public page.',
        'named_party_hint_business' => 'Seen by the office handling this report and by the Mayor\'s office. It is never shown on the public page.',
        'named_party_position' => 'Their position or office',
        'named_party_position_hint' => 'Optional. It helps the office find the right person.',
    ],

    'visibility' => [
        'gated_title' => 'This report names someone.',
        'gated_body' => 'Because it names a person or a business, it is filed private. When the office resolves it and verifies the information is true, it goes automatically to the Mayor\'s office, which redacts the public copy and decides whether to publish it. You will be told either way.',
        'gated_ack' => 'I understand this report names somebody and will be filed private. When the office resolves it and verifies the information is true, it goes automatically to the Mayor\'s office, which redacts the public copy and decides whether to publish it. I will be told either way.',
        'public_title' => 'Public',
        'public_note' => 'Anyone can read it there once the office verifies your report — under your display alias, never your real name. You can make it private again at any time.',
        'private_title' => 'Private',
        'private_note' => 'Only you and the staff handling your report can see it. It will not appear on the public page.',
        'footnote' => 'Reports appear on the public page only after the office verifies them, and are held back automatically if they read as spam or unrelated to municipal services. Staff may also remove sensitive details from the public page, but they cannot change this choice — and you will always see your own report exactly as you filed it.',
    ],

    'page' => [
        'title' => 'File a complaint',
        'lede' => 'Tell us what needs attention. We will send it to the right office and keep you updated.',
    ],

    'back' => 'Back to my complaints',

    'progress' => [
        'step' => 'Step {n} of {total}',
    ],

    'live' => [
        'step' => 'Step {n} of {total}: {name}',
    ],

    'steps' => [
        'group' => 'Problem type',
        'category' => 'Category',
        'describe' => 'Details',
        'photos' => 'Photos',
        'where' => 'Location',
        'pin' => 'Map pin',
        'visibility' => 'Privacy',
        'review' => 'Review',
    ],

    'screens' => [
        'group' => [
            'title' => 'What kind of problem is it?',
            'help' => 'Pick the closest match.',
        ],
        'category' => [
            'title' => 'Which one fits best?',
            'help' => 'This helps us send it to the right office.',
        ],
    ],

    'buttons' => [
        'back' => 'Back',
        'next' => 'Next',
        'skip' => 'Skip',
        'continue' => 'Continue',
        'submit' => 'Submit complaint',
        'change' => 'Change',
        'save_and_review' => 'Save & review',
    ],

    'chip' => [
        'goes_to' => 'Goes to {office}',
    ],

    'hints' => [
        'group' => 'Please choose a problem type.',
        'category' => 'Please choose what fits best.',
        'describe' => 'Add a short title and at least 20 characters of detail.',
        'photos' => 'Add at least one photo.',
        'where' => 'Choose your barangay.',
        'where_landmark' => 'Choose your barangay and add a landmark or address.',
        'review' => 'Please tick the box to confirm.',
    ],

    'map' => [
        'help' => 'Click inside Daet to place a marker, or enter a nearby landmark above.',
        'outside' => 'That point is outside Daet. Place the pin inside the city.',
        'previous_outside' => 'Your previous pin was outside Daet and was removed. Place a new pin, or skip this step.',
    ],

    'errors' => [
        'outside_daet' => 'That point is outside Daet. Place the pin inside the city, or skip the map and use the address or landmark field.',
    ],

    'draft' => [
        'banner' => 'Resume your previous draft?',
        'resume' => 'Resume',
        'reset' => 'Start over',
    ],

    'wizard' => [
        'priority_toggle' => 'How is priority decided?',
        'take_photo' => 'Take a photo',
        'choose_gallery' => 'Choose from gallery',
        'photo_tips' => 'Photo tips',
        'landmark_toggle' => '+ Add a landmark (optional)',
        'landmark_toggle_required' => '+ Add a landmark',
        'why_toggle' => 'Why we ask',
        'edit' => 'Edit',
        'what_next' => 'What happens next',
        'next_1' => 'Department review: your report is checked for legitimacy and scope.',
        'next_2' => 'Priority confirmed: the assigned office confirms the suggested priority.',
        'next_3' => 'Status updates: you can follow progress from My Complaints.',
        'title_label' => 'Title',
        'description_label' => 'Description',
        'photos_label' => 'Photos',
        'visibility_label' => 'Visibility',
        'desc_more' => '{count} more characters',
        'desc_good' => 'Looks good',
        'photos_required_help' => 'Required. Add up to five clear photos taken at the issue location.',
        'photos_optional_help' => 'Optional, but encouraged for better information. Add up to five clear photos if you have them.',
    ],

    'groups' => [
        'roads' => [
            'label' => 'Roads & Drainage',
            'helper' => 'Potholes, streetlights, flooding',
        ],
        'waste' => [
            'label' => 'Garbage, Sanitation & Plastic',
            'helper' => 'Garbage, sewage, plastic packaging',
        ],
        'other' => [
            'label' => 'Something else',
            'helper' => 'Anything else',
        ],
    ],

    'leaves' => [
        'road_damage' => [
            'label' => 'Road Damage',
            'helper' => 'Potholes, cracks, cave-ins',
        ],
        'flooding' => [
            'label' => 'Flooding / Drainage',
            'helper' => 'Flooded streets, clogged canals',
        ],
        'streetlight' => [
            'label' => 'Streetlight Issues',
            'helper' => 'Lights that are out or flickering',
        ],
        'garbage' => [
            'label' => 'Garbage Collection',
            'helper' => 'Missed pickup, piled-up trash',
        ],
        'sanitation' => [
            'label' => 'Sanitation',
            'helper' => 'Bad smells, sewage, unclean areas',
        ],
        'plastic_usage' => [
            'label' => 'Plastic Product Usage',
            'helper' => 'Business using plastic in its products',
        ],
        'park_maintenance' => [
            'label' => 'Park / Public Area Maintenance',
            'helper' => 'Damaged benches, playgrounds, plazas',
        ],
        'noise_complaint' => [
            'label' => 'Noise Complaint',
            'helper' => 'Loud music, karaoke, construction',
        ],
        'stray_animals' => [
            'label' => 'Stray Animals',
            'helper' => 'Stray dogs or cats nearby',
        ],
        'business_permit' => [
            'label' => 'Business Permit Issues',
            'helper' => 'Permit, license or business problems',
        ],
        'staff_treatment' => [
            'label' => 'How You Were Treated at the LGU',
            'helper' => 'How a member of personnel handled you',
        ],
        'others' => [
            'label' => 'Other Concerns',
            'helper' => 'Anything that does not fit above',
        ],
    ],

    'errors' => [
        'no_office' => 'No office is available to receive this category right now. Please choose another category or try again later.',
    ],

    'empty' => [
        'title' => 'No categories are available yet',
        'body' => 'The municipality is still setting up the complaint categories. Please check back soon or contact the Mayor\'s Office for urgent concerns.',
        'cta' => 'Back to my complaints',
    ],
];
