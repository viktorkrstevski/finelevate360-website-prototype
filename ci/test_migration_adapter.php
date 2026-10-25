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
function get_post_meta(...$args){return '/advisory-board/';}
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
if($render('<p>Advisory introduction</p>')!=='<p>Advisory introduction</p><div>Native profile grid</div>')throw new Exception('Missing excerpt fallback or native profiles.');
$excerpt='Saved introduction';
if($render('<p>Page content</p>')!=='<p>Saved introduction</p><div>Native profile grid</div>')throw new Exception('Saved excerpt not preserved.');
echo "Adapter excerpt recursion regression checks passed.\n";
