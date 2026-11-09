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
            'label' => 'Garbage & Sanitation',
            'helper' => 'Garbage, sewage, bad smells',
        ],
        'spaces' => [
            'label' => 'Parks & Public Areas',
            'helper' => 'Parks, plazas, playgrounds',
        ],
        'neighborhood' => [
            'label' => 'Noise & Animals',
            'helper' => 'Noise, stray animals',
        ],
        'business' => [
            'label' => 'Business & Permits',
            'helper' => 'Permits and licenses',
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
        'others' => [
            'label' => 'Other Concerns',
            'helper' => 'Anything that does not fit above',
        ],
    ],
];
