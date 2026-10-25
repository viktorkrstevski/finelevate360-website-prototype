<?php
define('ABSPATH','/');
$filters=[];$excerpt='';
function add_filter($name,$callback,...$args){global $filters;$filters[$name]=$callback;}
function add_action(...$args){}
function is_admin(){return false;}
function in_the_loop(){return true;}
function is_main_query(){return true;}
function get_option($key){return true;}
function get_the_ID(){return 7;}
function get_post_meta($id,$key,...$args){return $key==='_thumbnail_id'?42:'/advisory-board/';}
function get_post_type($id){return 'finelev360_advisory';}
function get_stylesheet(){return 'finelevate-advisory';}
function wp_get_attachment_image($id,...$args){return '<img data-id="'.$id.'">';}
function fe_routes(){return ['routes'=>['/advisory-board/'=>['group'=>'advisory']]];}
function get_post_field(...$args){global $excerpt;return $excerpt;}
function get_the_excerpt(){throw new Exception('Recursive excerpt API called inside the_content.');}
function shortcode_exists($tag){return true;}
function do_shortcode($text){return '<div>Native profile grid</div>';}
function strip_shortcodes($text){return $text;}
function wp_strip_all_tags($text){return strip_tags($text);}
function wp_trim_words($text,$count){return implode(' ',array_slice(explode(' ',$text),0,$count));}
function esc_html($text){return htmlspecialchars($text,ENT_QUOTES,'UTF-8');}
require __DIR__.'/../wordpress/plugins/finelevate-staging-migration/adapter.php';
$render=$filters['the_content'];
if($render('<p>Advisory introduction</p>')!=='<p>Advisory introduction</p><div class="fe-native-profiles fe-native-profiles--advisory"><div>Native profile grid</div></div>')throw new Exception('Missing excerpt fallback or native profiles.');
$excerpt='Saved introduction';
if($render('<p>Page content</p>')!=='<p>Saved introduction</p><div class="fe-native-profiles fe-native-profiles--advisory"><div>Native profile grid</div></div>')throw new Exception('Saved excerpt not preserved.');
if(class_exists('DOMDocument')){
    $html='<div class="finelevate360-hero-page-intro"><p>Full introduction</p><ul><li>Preserved board remit</li></ul></div><div class="finelevate360-container">Old static profiles</div>';
    $out=fe_migration_listing_intro($html,7);
    if(strpos($out,'Preserved board remit')===false||strpos($out,'Old static profiles')!==false)throw new Exception('Listing introduction lost or static cards duplicated.');
}
if($filters['post_thumbnail_html']('',7)!=='<img data-id="42">')throw new Exception('Mapped profile thumbnail missing.');
if($filters['post_thumbnail_html']('Existing thumbnail',7)!=='Existing thumbnail')throw new Exception('Existing thumbnail replaced.');
echo "Adapter excerpt, introduction and thumbnail regression checks passed.\n";

foreach(['mk','sr','hr','sl','me'] as $lang){
 foreach(['Platform','All','Filter by functionality','Apply','Reset','Close','Filter'] as $label)if(fe_migration_map_label($label,$lang)===$label)throw new Exception('Missing localized filter '.$lang.' '.$label);
 if(!fe_migration_map_unit('entity',$lang)||!fe_migration_map_unit('functionality',$lang))throw new Exception('Missing localized summary unit.');
}
echo "Map locale labels and summary units passed.\n";
