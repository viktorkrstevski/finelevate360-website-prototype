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
    foreach (array('mk' => 'Macedonian', 'sr' => 'Serbian', 'hr' => 'Croatian', 'sl' => 'Slovenian', 'me' => 'Montenegrin') as $language => $label) {
        $panel = 'fe_translation_' . $language;
        $customizer->add_panel($panel, array('title' => 'Finelevate - ' . $label, 'priority' => 26));
        foreach ($sections as $section => $section_label) {
            if ($section === 'links') { continue; }
            $customizer->add_section($panel . '_' . $section, array('title' => $section_label, 'panel' => $panel));
        }
        foreach (fe_defaults() as $key => $field) {
            if ($field['type'] !== 'text') { continue; }
            $setting = 'fe_' . $language . '_' . $key;
            $customizer->add_setting($setting, array('default' => fe_translations()[$language][$key] ?? $field['value'], 'sanitize_callback' => 'sanitize_textarea_field'));
            $customizer->add_control($setting, array('label' => $field['label'], 'description' => 'Editable localized homepage copy.', 'section' => $panel . '_' . $field['section'], 'type' => 'textarea'));
        }
    }
    foreach (array('hero' => array('hero', 'Conceptual architecture image'), 'cooperation' => array('partners', 'FEIT cooperation photograph'), 'advisory_1' => array('advisory', 'Vladimir Atanasovski portrait'), 'advisory_2' => array('advisory', 'Filip Simeonov portrait'), 'advisory_3' => array('advisory', 'Ezeni Brzovska portrait')) as $key => $field) {
        $customizer->add_setting('fe_image_' . $key, array('sanitize_callback' => 'absint'));
        $customizer->add_control(new WP_Customize_Media_Control($customizer, 'fe_image_' . $key, array('label' => __($field[1], 'finelevate-advisory'), 'section' => 'fe_' . $field[0], 'mime_type' => 'image')));
    }
    $event = fe_event_locales();
    $customizer->add_section('fe_event_mk_copy', array('title' => 'Summit - published Macedonian copy', 'panel' => 'fe_translation_mk'));
    foreach ($event['targets'] as $target) {
        $key = $target['key'];
        if (!isset($event['strings']['mk'][$key]) || in_array($target['tag'], array('button', 'label', 'input', 'textarea'), true) || preg_match('/^(nav|pf|cd)/', $key)) { continue; }
        $setting = 'fe_event_mk_' . $key;
        $customizer->add_setting($setting, array('default' => $event['strings']['mk'][$key], 'sanitize_callback' => 'wp_kses_post'));
        $customizer->add_control($setting, array('label' => ucfirst(preg_replace('/([a-z])([A-Z])/', '$1 $2', $key)), 'section' => 'fe_event_mk_copy', 'type' => 'textarea'));
    }
});
