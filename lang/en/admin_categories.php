<?php

return [
    'page' => [
        'title' => 'Complaint categories',
        'subtitle' => 'Shape the problems residents can report, the office that receives each one, and what the form asks for.',
    ],

    'staff' => [
        'title' => 'Complaint categories',
        'subtitle' => 'Manage the categories residents can choose for :office. Problem types are shared across the municipality.',
        'scope' => 'Showing the categories that route to :office.',
        'back' => 'Back to office',
        'no_problem_types' => 'A super admin has not created a problem type yet. You can add categories as soon as one exists.',
    ],

    'filters' => [
        'label' => 'Category status',
        'active' => 'Active',
        'archived' => 'Archived',
        'all' => 'All',
    ],

    'problem_types' => [
        'title' => 'Problem types',
        'hint' => 'The headings residents see first. Archiving one hides every category beneath it, without touching the reports already filed.',
        'empty' => 'No problem types yet. Create one to start building the filing form.',
        'archived' => 'Problem type archived',
        'archived_hint' => 'Its categories are hidden from residents. Existing reports keep their labels and history.',
        'category_count' => ':count category|:count categories',
    ],

    'categories' => [
        'title' => 'Categories',
        'none_in_group' => 'No categories in this problem type yet.',
        'active' => 'Live',
        'archived' => 'Archived',
        'hidden_by_group' => 'Hidden by problem type',
        'filed_count' => ':count report filed|:count reports filed',
        'archive_hint' => 'Archiving removes this from the filing form. Reports already filed keep their labels, owner and history.',
        'key' => 'Reference key',
        'key_hint' => 'Generated once from the English label and never changes. It keeps old reports attached if you rename the category.',
        'no_office' => 'No receiving office',
        'fallback_office' => 'Receives through the Mayor\'s Office',
    ],

    'actions' => [
        'add_problem_type' => 'Add problem type',
        'add_category' => 'Add category',
        'edit' => 'Edit',
        'close' => 'Close',
        'archive' => 'Archive',
        'restore' => 'Restore',
        'save' => 'Save changes',
        'create_problem_type' => 'Create problem type',
        'create_category' => 'Create category',
    ],

    'form' => [
        'problem_type_create_title' => 'New problem type',
        'problem_type_create_helper' => 'A heading in the resident picker. It groups related categories but does not receive reports itself.',
        'problem_type_edit_title' => 'Edit problem type',
        'category_create_title' => 'New category',
        'category_create_helper' => 'A resident-selectable problem, with the office that receives it and what the form asks for.',
        'category_edit_title' => 'Edit category',
        'label_en' => 'English label',
        'label_fil' => 'Filipino label',
        'label_hint' => 'English is required. Filipino appears when the resident uses Filipino.',
        'helper_en' => 'English helper text',
        'helper_fil' => 'Filipino helper text',
        'helper_hint' => 'Optional. One short sentence shown under the category on the resident tile.',
        'group' => 'Problem type',
        'group_hint' => 'The heading this category appears under.',
        'office' => 'Receiving office',
        'office_hint' => 'The office that verifies and works reports filed under this category.',
        'icon' => 'Icon',
        'color' => 'Color',
        'icon_hint' => 'Drawn from the shared icon set, never from the label.',
        'color_hint' => 'A tint for the category circle.',
        'intake' => 'What the form asks for',
        'intake_hint' => 'Basic intake behavior. Work rules and publication policy stay with the existing defaults for now.',
        'sort_order' => 'Order',
        'sort_order_hint' => 'Lower numbers appear first. Empty adds it to the end.',
        'optional' => 'optional',
        'choose_icon' => 'Choose an icon',
        'choose_color' => 'Choose a color',
        'preview' => 'Resident tile preview',
        'preview_hint' => 'This is how the category appears in the picker.',
        'no_office' => 'Choose an office',
        'no_group' => 'Choose a problem type',
    ],

    'icons' => [
        'construction' => 'Road / construction',
        'waves' => 'Flooding',
        'lightbulb' => 'Streetlight',
        'trash-2' => 'Garbage',
        'spray-can' => 'Sanitation',
        'leaf' => 'Plastic / environment',
        'trees' => 'Park / trees',
        'volume-2' => 'Noise',
        'paw-print' => 'Stray animals',
        'briefcase' => 'Business',
        'file-badge' => 'Personnel',
        'users' => 'People',
        'circle-help' => 'Other',
    ],

    'colors' => [
        'roads' => 'Road amber',
        'flood' => 'Flood blue',
        'streetlight' => 'Streetlight gold',
        'waste' => 'Waste green',
        'sanitation' => 'Sanitation teal',
        'spaces' => 'Parks lime',
        'neighborhood' => 'Neighborhood violet',
        'noise' => 'Noise violet',
        'business' => 'Business cyan',
        'personnel' => 'Personnel pink',
        'stray' => 'Stray orange',
        'other' => 'Other slate',
    ],

    'intake_modes' => [
        'physical_required' => [
            'label' => 'Photos and location required',
            'helper' => 'Ask for 1–5 photos and a map pin or barangay address.',
        ],
        'proof_optional' => [
            'label' => 'Location required, photos optional',
            'helper' => 'Ask for a barangay and landmark; photos are welcome but not required.',
        ],
        'sensitive_intake' => [
            'label' => 'No photos or location',
            'helper' => 'Hide the photo and location steps. Use for reports that are sensitive to file.',
        ],
        'follow_up_optional' => [
            'label' => 'No photos or location; staff follow up',
            'helper' => 'Keep intake short. The office may request details later.',
        ],
    ],

    'empty' => [
        'title' => 'No categories yet',
        'body' => 'Create a problem type, then add the categories residents can choose. Until then, the filing form has nothing to offer.',
        'filter' => 'Nothing matches this filter.',
    ],

    'flash' => [
        'problem_type_created' => 'Problem type created.',
        'problem_type_updated' => 'Problem type updated.',
        'problem_type_archived' => 'Problem type archived. Its categories are hidden from residents.',
        'problem_type_restored' => 'Problem type restored. Its active categories are visible again.',
        'category_created' => 'Category created. It is live in the filing form.',
        'category_updated' => 'Category updated.',
        'category_archived' => 'Category archived. Existing reports keep their labels.',
        'category_restored' => 'Category restored and visible in the filing form again.',
    ],

    'validation' => [
        'problem_type_duplicate' => 'This problem type has already been created.',
        'category_duplicate_office' => 'This category has already been created for this office.',
    ],
];
