<?php
/**
 * Translatable strings for finelevate360.
 * Loco Translate / WP-CLI i18n make-pot scans this file.
 */
if (!defined('ABSPATH')) exit;

function finelevate360_i18n() {
  $lang = function_exists('pll_current_language') ? pll_current_language('slug') : '';

  if ($lang === 'sl') {
    return [
      'attributes'     => 'Funkcionalnosti',
      'attribute'      => 'Funkcionalnost',
      'mobile'         => 'Prebivalstvo (aplikacija)',
      'desktop'        => 'Prebivalstvo (splet)',
      'yes'            => 'DA',
      'no'             => 'NE',
      'close'          => 'Zapri',
      'website'        => 'Spletna stran',
      'linkedin'       => 'LinkedIn',
      'contact'        => 'Kontakt',
      'biznis'         => 'Podjetja (aplikacija)',
      'privat'         => 'Podjetja (splet)',
      'tip'            => 'Vrsta',
      'platform'       => 'Platforma',
      'all'            => 'Vse',
      'filter_attrs'   => 'Filtriraj po funkcionalnosti',
      'all_categories' => 'Vse kategorije',
      'apply'          => 'Uporabi',
      'reset'          => 'Ponastavi',
      'no_items'       => 'Za izbrane filtre ni najdenih elementov.',
      'pagination'     => 'Strani',
      'prev'           => '« Prejšnja',
      'next'           => 'Naslednja »',
    ];
  }

  return [
    'attributes'     => _x( 'Attributes',                       'modal heading',  'finelevate360' ),
    'attribute'      => _x( 'Functionality',                    'modal heading',  'finelevate360' ),
    'mobile'         => _x( 'Mobile Private',                   'platform label', 'finelevate360' ),
    'desktop'        => _x( 'WEB Private',                      'platform label', 'finelevate360' ),
    'yes'            => _x( 'YES',                              'attribute value','finelevate360' ),
    'no'             => _x( 'NO',                               'attribute value','finelevate360' ),
    'close'          => _x( 'Close',                            'modal button',   'finelevate360' ),
    'website'        => _x( 'Website',                          'link label',     'finelevate360' ),
    'linkedin'       => _x( 'LinkedIn',                         'link label',     'finelevate360' ),
    'contact'        => _x( 'Contact',                          'section heading','finelevate360' ),
    'biznis'         => _x( 'Mobile Business',                  'platform label', 'finelevate360' ),
    'privat'         => _x( 'WEB Business',                     'platform label', 'finelevate360' ),
    'tip'            => _x( 'Type',                             'filter heading', 'finelevate360' ),
    'platform'       => _x( 'Platform',                         'filter heading', 'finelevate360' ),
    'all'            => _x( 'All',                              'filter option',  'finelevate360' ),
    'filter_attrs'   => _x( 'Filter by functionality',          'filter heading', 'finelevate360' ),
    'all_categories' => _x( 'All categories',                   'filter option',  'finelevate360' ),
    'apply'          => _x( 'Apply',                            'filter button',  'finelevate360' ),
    'reset'          => _x( 'Reset',                            'filter button',  'finelevate360' ),
    'no_items'       => _x( 'No items found for selected filters.', 'empty state', 'finelevate360' ),
    'pagination'     => _x( 'Pagination',                       'aria label',     'finelevate360' ),
    'prev'           => _x( '« Prev',                           'pagination',     'finelevate360' ),
    'next'           => _x( 'Next »',                           'pagination',     'finelevate360' ),
  ];
}
