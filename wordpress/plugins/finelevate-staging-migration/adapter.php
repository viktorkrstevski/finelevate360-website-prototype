<?php
if (!defined('ABSPATH')) exit;
add_filter('pll_get_post_types',function($types){foreach(['event','finelev360_partner','finelev360_advisory','finelev360_item','finelev360_attr','finelev360_report','wpcf7_contact_form'] as $type)$types[$type]=$type;return $types;});
add_filter('pll_get_taxonomies',function($types){$types['finelev360_cat']='finelev360_cat';return $types;});
add_filter('wpcf7_skip_mail','__return_true',PHP_INT_MAX);
add_action('after_setup_theme',function(){remove_filter('template_include','finelevate360_template_include');});
add_filter('template_include',function($template){if(get_stylesheet()==='finelevate-advisory'&&is_singular()&&get_post_meta(get_queried_object_id(),'_fe_public_path',true)){$path=get_post_meta(get_queried_object_id(),'_fe_public_path',true);$group=function_exists('fe_routes')?(fe_routes()['routes'][$path]['group']??''):'';return get_template_directory().($group==='home'?'/front-page.php':'/page.php');}return $template;},99);
add_filter('the_content',function($content){
    if(is_admin()||!in_the_loop()||!is_main_query()||!get_option('fe_staging_migration_complete'))return $content;
    $id=get_the_ID();$path=get_post_meta($id,'_fe_public_path',true);$group=function_exists('fe_routes')?(fe_routes()['routes'][$path]['group']??''):'';
    if(in_array($group,['banking','insurance','fintech'],true)&&function_exists('finelevate360_shortcode_items')){
        $shortcode=get_post_meta($id,'_page_attr_field3',true);if(!$shortcode)return $content;
        $counts='';$lang=function_exists('fe_language')?fe_language():'en';
        foreach([1=>'entity',2=>'functionality'] as $n=>$key){$value=get_post_meta($id,'_page_attr_field'.$n,true);$label=get_post_meta($id,'finelevate_'.$key.'_label_'.$lang,true)?:get_post_meta($id,'finelevate_'.$key.'_label',true);if($value)$counts.='<span><strong>'.wp_kses_post(do_shortcode($value)).'</strong> '.esc_html($label).'</span> ';}
        return '<div class="finelevate-map-page"><div class="finelevate-header-item-count">'.$counts.'</div><div class="finelevate-map-content">'.$content.'</div><section id="finelevate-map-interactive" class="finelevate-map-interactive">'.do_shortcode($shortcode).'</section></div>';
    }
    $excerpt=get_post_field('post_excerpt',$id);if(!$excerpt)$excerpt=wp_trim_words(wp_strip_all_tags(strip_shortcodes($content)),45);
    if($group==='partners'&&shortcode_exists('finelevate360_partners'))return '<p>'.esc_html($excerpt).'</p>'.do_shortcode('[finelevate360_partners count="1000" orderby="menu_order" order="ASC"]');
    if($group==='advisory'&&shortcode_exists('finelevate360_advisory'))return '<p>'.esc_html($excerpt).'</p>'.do_shortcode('[finelevate360_advisory count="1000" orderby="menu_order" order="ASC"]');
    if(get_post_type($id)==='finelev360_item'&&function_exists('finelevate360_render_card'))return $content.finelevate360_render_card($id,FINELEVATE360_CPT_ITEM);
    return $content;
},30);
add_action('wp_enqueue_scripts',function(){if(get_stylesheet()==='finelevate-advisory'&&is_singular()){wp_enqueue_style('finelevate-migrated-map',plugins_url('assets/map.css',__FILE__),[],'0.2.3');wp_enqueue_script('finelevate-migrated-map',plugins_url('assets/map.js',__FILE__),[],'0.2.3',true);}},30);
// Keep language changes independent of selected map filters, translating ID references.
add_filter('pll_the_language_link',function($url,$slug){
    $filters=array_intersect_key(wp_unslash($_GET),array_flip(['country','country_id','country_slug','f360_cat','f360_col','f360_attr','channel','segment','sector']));
    $filters=map_deep($filters,'sanitize_text_field');
    if(isset($filters['f360_attr']))$filters['f360_attr']=array_map(function($id)use($slug){return function_exists('pll_get_post')?(pll_get_post((int)$id,$slug)?:$id):$id;},(array)$filters['f360_attr']);
    if(isset($filters['f360_cat'])&&function_exists('pll_get_term'))$filters['f360_cat']=pll_get_term((int)$filters['f360_cat'],$slug)?:$filters['f360_cat'];
    return $filters?add_query_arg($filters,$url):$url;
},20,2);
add_action('widgets_init',function(){register_sidebar(['id'=>'finelevate-legacy-content','name'=>'Finelevate imported widgets','description'=>'Legacy editable content retained alongside the staging redesign.','before_widget'=>'<section>','after_widget'=>'</section>','before_title'=>'<h2>','after_title'=>'</h2>']);});
// The old theme owned these controls. Keep them available when using the redesign.
add_action('add_meta_boxes_page',function(){add_meta_box('fe-map-fields','Digital Map content','fe_migration_map_metabox','page','normal','default');});
function fe_migration_map_metabox($post){wp_nonce_field('fe_map_fields','fe_map_nonce');foreach(['_page_attr_field1'=>'Entity count shortcode','_page_attr_field2'=>'Capability count shortcode','_page_attr_field3'=>'Interactive Directory shortcode','finelevate360_bank_order'=>'Bank display order'] as $key=>$label)echo '<p><label>'.esc_html($label).'<br><textarea class="widefat" name="fe_map_fields['.esc_attr($key).']">'.esc_textarea(get_post_meta($post->ID,$key,true)).'</textarea></label></p>';}
add_action('save_post_page',function($id){if(!isset($_POST['fe_map_nonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['fe_map_nonce'])),'fe_map_fields')||!current_user_can('edit_post',$id)||wp_is_post_revision($id))return;foreach(['_page_attr_field1','_page_attr_field2','_page_attr_field3','finelevate360_bank_order'] as $key)if(isset($_POST['fe_map_fields'][$key]))update_post_meta($id,$key,sanitize_text_field(wp_unslash($_POST['fe_map_fields'][$key])));});
// Native Polylang synchronisation is retained for common WordPress fields. Localised
// evidence and source identity fields must never be copied verbatim across languages.
add_filter('pll_copy_post_metas',function($keys){return array_values(array_filter($keys,function($key){return !preg_match('/^(?:_fe_|_finelevate360_|_event_|_page_attr_|_fa_|finelevate_entity_label|finelevate_functionality_label|finelevate360_bank_order)/',$key);}));},PHP_INT_MAX);
