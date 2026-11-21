<?php

namespace Database\Seeders\Support;

/**
 * The canonical complaint taxonomy every install starts with.
 *
 * This is install data, not policy: once an administrator edits a group or
 * category, the database is the authority. The class exists so the migration
 * that introduces the schema and the seeder that fills a fresh database tell
 * exactly the same story.
 *
 * Keys are generated from the English label and never change afterwards —
 * complaints store the key, so renaming a category must never orphan history.
 * Retired categories are seeded inactive, because complaints already filed
 * under them still need their label, office and icon.
 */
final class ComplaintTaxonomyDefaults
{
    /**
     * @return array<string, array{
     *     label_en: string,
     *     label_fil: string,
     *     helper_en: string,
     *     helper_fil: string,
     *     icon: string,
     *     color: string,
     *     sort_order: int
     * }>
     */
    public static function groups(): array
    {
        return [
            'roads' => [
                'label_en' => 'Roads & Drainage',
                'label_fil' => 'Kalsada at Drainage',
                'helper_en' => 'Potholes, flooding, streetlights and public structures.',
                'helper_fil' => 'Lubak, baha, ilaw sa kalye at pampublikong pasilidad.',
                'icon' => 'construction',
                'color' => 'roads',
                'sort_order' => 1,
            ],
            'waste' => [
                'label_en' => 'Waste & Sanitation',
                'label_fil' => 'Basura at Sanitasyon',
                'helper_en' => 'Garbage, sanitation and the plastic policy.',
                'helper_fil' => 'Basura, sanitasyon at patakaran sa plastik.',
                'icon' => 'trash-2',
                'color' => 'waste',
                'sort_order' => 2,
            ],
            'other' => [
                'label_en' => 'Other Concerns',
                'label_fil' => 'Iba pang Suliranin',
                'helper_en' => 'Anything that does not fit the groups above.',
                'helper_fil' => 'Anumang hindi kasama sa mga pangkat sa itaas.',
                'icon' => 'circle-help',
                'color' => 'other',
                'sort_order' => 3,
            ],
        ];
    }

    /**
     * @return array<string, array{
     *     group: string,
     *     department_code: string,
     *     label_en: string,
     *     label_fil: string,
     *     helper_en: string,
     *     helper_fil: string,
     *     icon: string,
     *     color: string,
     *     intake_mode: string,
     *     is_active: bool,
     *     sort_order: int
     * }>
     */
    public static function categories(): array
    {
        return [
            'road_damage' => [
                'group' => 'roads',
                'department_code' => 'ENG',
                'label_en' => 'Road Damage',
                'label_fil' => 'Pinsala sa Daan',
                'helper_en' => 'Potholes, cracked pavement or a road that has caved in.',
                'helper_fil' => 'Lubak, bitak o gumuhong daan.',
                'icon' => 'construction',
                'color' => 'roads',
                'intake_mode' => 'physical_required',
                'is_active' => true,
                'sort_order' => 1,
            ],
            'flooding' => [
                'group' => 'roads',
                'department_code' => 'ENG',
                'label_en' => 'Flooding / Drainage',
                'label_fil' => 'Pagbaha at Drainage',
                'helper_en' => 'Standing water or a blocked canal.',
                'helper_fil' => 'Baha sa kalsada o baradong kanal.',
                'icon' => 'waves',
                'color' => 'flood',
                'intake_mode' => 'physical_required',
                'is_active' => true,
                'sort_order' => 2,
            ],
            'streetlight' => [
                'group' => 'roads',
                'department_code' => 'ENG',
                'label_en' => 'Streetlight Issues',
                'label_fil' => 'Sirang Ilaw sa Kalye',
                'helper_en' => 'A streetlight that is out, flickering or damaged.',
                'helper_fil' => 'Ilaw sa kalye na patay, kumukurap o sira.',
                'icon' => 'lightbulb',
                'color' => 'streetlight',
                'intake_mode' => 'physical_required',
                'is_active' => true,
                'sort_order' => 3,
            ],
            'garbage' => [
                'group' => 'waste',
                'department_code' => 'MENRO',
                'label_en' => 'Garbage Collection',
                'label_fil' => 'Koleksyon ng Basura',
                'helper_en' => 'A missed collection or garbage piling up.',
                'helper_fil' => 'Hindi naubos na koleksyon o tambak na basura.',
                'icon' => 'trash-2',
                'color' => 'waste',
                'intake_mode' => 'physical_required',
                'is_active' => true,
                'sort_order' => 4,
            ],
            'sanitation' => [
                'group' => 'waste',
                'department_code' => 'MENRO',
                'label_en' => 'Sanitation',
                'label_fil' => 'Sanitasyon',
                'helper_en' => 'A foul smell, open sewage or an unclean public area.',
                'helper_fil' => 'Mabahong amoy, bukas na alkantarilya o maruming lugar.',
                'icon' => 'spray-can',
                'color' => 'sanitation',
                'intake_mode' => 'physical_required',
                'is_active' => true,
                'sort_order' => 5,
            ],
            'plastic_usage' => [
                'group' => 'waste',
                'department_code' => 'MENRO',
                'label_en' => 'Plastic Product Usage',
                'label_fil' => 'Paggamit ng Plastik',
                'helper_en' => 'A business using banned plastic products.',
                'helper_fil' => 'Negosyong gumagamit ng ipinagbabawal na plastik.',
                'icon' => 'leaf',
                'color' => 'waste',
                'intake_mode' => 'physical_required',
                'is_active' => true,
                'sort_order' => 6,
            ],
            'others' => [
                'group' => 'other',
                'department_code' => 'OM',
                'label_en' => 'Other Concerns',
                'label_fil' => 'Iba pang Suliranin',
                'helper_en' => 'A concern that does not fit the categories above.',
                'helper_fil' => 'Suliranging wala sa mga kategorya sa itaas.',
                'icon' => 'circle-help',
                'color' => 'other',
                'intake_mode' => 'follow_up_optional',
                'is_active' => true,
                'sort_order' => 7,
            ],

            // Withdrawn categories. They are not offered to residents, but
            // they stay in the table so complaints already filed under them
            // keep a label, an owner and their intake behaviour.
            'park_maintenance' => [
                'group' => 'other',
                'department_code' => 'OM',
                'label_en' => 'Park / Public Area Maintenance',
                'label_fil' => 'Pangangalaga sa Parke',
                'helper_en' => 'A damaged park, plaza or public recreation area.',
                'helper_fil' => 'Sirang parke, plaza o pampublikong palaruan.',
                'icon' => 'trees',
                'color' => 'spaces',
                'intake_mode' => 'physical_required',
                'is_active' => false,
                'sort_order' => 8,
            ],
            'noise_complaint' => [
                'group' => 'other',
                'department_code' => 'OM',
                'label_en' => 'Noise Complaint',
                'label_fil' => 'Reklamo sa Ingay',
                'helper_en' => 'Loud music, karaoke or construction at an unreasonable hour.',
                'helper_fil' => 'Malakas na musika, karaoke o konstruksyon sa hindi tamang oras.',
                'icon' => 'volume-2',
                'color' => 'neighborhood',
                'intake_mode' => 'proof_optional',
                'is_active' => false,
                'sort_order' => 9,
            ],
            'stray_animals' => [
                'group' => 'other',
                'department_code' => 'OM',
                'label_en' => 'Stray Animals',
                'label_fil' => 'Mga Gumagala na Hayop',
                'helper_en' => 'A stray dog or cat that needs the office to respond.',
                'helper_fil' => 'Gumagala na aso o pusang kailangang aksyunan.',
                'icon' => 'paw-print',
                'color' => 'stray',
                'intake_mode' => 'proof_optional',
                'is_active' => false,
                'sort_order' => 10,
            ],
            'business_permit' => [
                'group' => 'other',
                'department_code' => 'OM',
                'label_en' => 'Business Permit Issues',
                'label_fil' => 'Isyu sa Permit ng Negosyo',
                'helper_en' => 'A permit, licence or business-regulation concern.',
                'helper_fil' => 'Permit, lisensya o problema sa regulasyon ng negosyo.',
                'icon' => 'briefcase',
                'color' => 'business',
                'intake_mode' => 'sensitive_intake',
                'is_active' => false,
                'sort_order' => 11,
            ],
            'staff_treatment' => [
                'group' => 'other',
                'department_code' => 'OM',
                'label_en' => 'How You Were Treated at the LGU',
                'label_fil' => 'Paano Ka Tinrato sa LGU',
                'helper_en' => 'How a member of the municipal staff treated you.',
                'helper_fil' => 'Paano ka hinarap ng kawani ng munisipyo.',
                'icon' => 'file-badge',
                'color' => 'personnel',
                'intake_mode' => 'sensitive_intake',
                'is_active' => false,
                'sort_order' => 12,
            ],
        ];
    }

    /**
     * The office that catches a category nobody owns yet, and the fallback
     * when the selected office is missing or archived.
     */
    public static function fallbackDepartmentCode(): string
    {
        return 'OM';
    }

    /**
     * Style + office fallback used for a category an admin created before the
     * taxonomy was database-backed (or a row imported from somewhere else).
     *
     * @return array{group: string, department_code: string, icon: string, color: string, intake_mode: string}
     */
    public static function fallbackCategory(): array
    {
        return [
            'group' => 'other',
            'department_code' => self::fallbackDepartmentCode(),
            'icon' => 'circle-help',
            'color' => 'other',
            'intake_mode' => 'physical_required',
        ];
    }
}
