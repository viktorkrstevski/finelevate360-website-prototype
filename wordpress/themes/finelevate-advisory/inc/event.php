<?php
/** Normalize the published Summit body without importing its scripts or forms. */
if (!defined('ABSPATH')) { exit; }

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
    foreach (array('site-header', 'site-footer', 'hero-brand', 'sticky-cta', 'pf') as $class) {
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
