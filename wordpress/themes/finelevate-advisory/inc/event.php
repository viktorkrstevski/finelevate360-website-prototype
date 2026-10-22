<?php
/** Normalize the published Summit body without importing its scripts or forms. */
if (!defined('ABSPATH')) { exit; }

function fe_event_locales() {
    static $data;
    if (!isset($data)) { $data = json_decode(file_get_contents(__DIR__ . '/event-translations.json'), true); }
    return $data;
}

function fe_event_normalize_text($text) {
    return trim(preg_replace('/[\s\x{00a0}]+/u', ' ', html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8')));
}

function fe_localize_event_nodes($document, $root) {
    $language = function_exists('fe_language') ? fe_language() : 'en';
    $data = fe_event_locales();
    if ($language === 'en' || !isset($data['strings'][$language])) { return; }
    $lookup = array();
    foreach ($data['targets'] as $target) {
        $key = $target['key'];
        if (!isset($data['strings'][$language][$key])) { continue; }
        foreach (array($data['strings']['en'][$key], $target['source_text'] ?? '') as $source) {
            $source = fe_event_normalize_text($source);
            if ($source !== '') { $lookup[$target['tag']][$source] = $target; }
        }
    }
    $nodes = array();
    foreach ($root->getElementsByTagName('*') as $node) { $nodes[] = $node; }
    foreach (array_reverse($nodes) as $node) {
        $text = fe_event_normalize_text($node->textContent);
        if (!isset($lookup[$node->tagName][$text])) { continue; }
        $target = $lookup[$node->tagName][$text];
        $translation = get_theme_mod('fe_event_' . $language . '_' . $target['key'], $data['strings'][$language][$target['key']]);
        while ($node->firstChild) { $node->removeChild($node->firstChild); }
        if (!$target['html']) { $node->appendChild($document->createTextNode($translation)); continue; }
        $allowed = array('strong' => array(), 'em' => array(), 'b' => array(), 'i' => array(), 'br' => array(), 'mark' => array(), 'span' => array('class' => true), 'a' => array('href' => true, 'title' => true, 'class' => true));
        $fragment = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $fragment->loadHTML('<!doctype html><html><head><meta charset="UTF-8"></head><body>' . wp_kses($translation, $allowed) . '</body></html>');
        libxml_clear_errors(); libxml_use_internal_errors($previous);
        $body = $fragment->getElementsByTagName('body')->item(0);
        foreach ($body->childNodes as $child) { $node->appendChild($document->importNode($child, true)); }
    }
}

function fe_event_markup($content) {
    if (!class_exists('DOMDocument')) { return $content; }
    $document = new DOMDocument('1.0', 'UTF-8');
    $previous = libxml_use_internal_errors(true);
    $document->loadHTML('<!doctype html><html><head><meta charset="UTF-8"></head><body>' . $content . '</body></html>');
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    $xpath = new DOMXPath($document);
    $root = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " fl-summit ")]')->item(0);
    if (!$root) { return $content; }
    fe_localize_event_nodes($document, $root);
    foreach (array('site-header', 'site-footer', 'hero-brand', 'sticky-cta', 'pf', 'countdown') as $class) {
        $remove = array();
        foreach ($xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " ' . $class . ' ")]', $root) as $node) { $remove[] = $node; }
        foreach ($remove as $node) { $node->parentNode->removeChild($node); }
    }
    foreach (array('fl-main' => 'top', 'booking' => 'book-space') as $class => $id) {
        $node = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " ' . $class . ' ")]', $root)->item(0);
        if ($node) { $node->setAttribute('id', $id); }
    }
    foreach (array('about-grid' => 'about', 'pass-grid' => 'passes', 'agenda-head-meta' => 'agenda', 'venue-grid' => 'venue', 'faq-grid' => 'faq', 'partners-bg' => 'partners') as $class => $id) {
        $node = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " ' . $class . ' ")]/ancestor-or-self::section[1]', $root)->item(0);
        if ($node) { $node->setAttribute('id', $id); }
    }
    return $document->saveHTML($root);
}

add_filter('the_content', function ($content) {
    return get_post_type() === 'event' && get_post_meta(get_the_ID(), '_fe_public_path', true) ? fe_event_markup($content) : $content;
}, 18);
