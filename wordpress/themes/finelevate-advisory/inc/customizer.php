<?php
if (!defined('ABSPATH')) { exit; }
add_action('customize_register', function ($customizer) {
    $customizer->add_panel('fe_homepage', array('title' => __('Finelevate homepage', 'finelevate-advisory'), 'priority' => 25));
    $sections = array('hero' => 'Hero & mission', 'expertise' => 'Expertise', 'map' => 'Digital Map entry', 'advisory' => 'Advisory Board', 'partners' => 'Partners & news', 'summit' => 'Summit', 'links' => 'Navigation & published destinations', 'footer' => 'Footer & contact');
    foreach ($sections as $id => $label) {
        $customizer->add_section('fe_' . $id, array('title' => __($label, 'finelevate-advisory'), 'panel' => 'fe_homepage'));
    }
    foreach (fe_defaults() as $key => $field) {
        $customizer->add_setting('fe_' . $key, array('default' => $field['value'], 'sanitize_callback' => $field['type'] === 'url' ? 'esc_url_raw' : 'sanitize_textarea_field'));
        $customizer->add_control('fe_' . $key, array('label' => $field['label'], 'section' => 'fe_' . $field['section'], 'type' => $field['type'] === 'url' ? 'url' : 'textarea'));
    }
    foreach (array('hero' => array('hero', 'Conceptual architecture image'), 'cooperation' => array('partners', 'FEIT cooperation photograph'), 'advisory_1' => array('advisory', 'Vladimir Atanasovski portrait'), 'advisory_2' => array('advisory', 'Filip Simeonov portrait'), 'advisory_3' => array('advisory', 'Ezeni Brzovska portrait')) as $key => $field) {
        $customizer->add_setting('fe_image_' . $key, array('sanitize_callback' => 'absint'));
        $customizer->add_control(new WP_Customize_Media_Control($customizer, 'fe_image_' . $key, array('label' => __($field[1], 'finelevate-advisory'), 'section' => 'fe_' . $field[0], 'mime_type' => 'image')));
    }
});
