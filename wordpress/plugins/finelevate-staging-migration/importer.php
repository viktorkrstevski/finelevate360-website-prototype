<?php
if (!defined('ABSPATH')) exit;
require_once __DIR__.'/directory-data.php';
function fe_migration_payload() {
    static $data;
    if (!isset($data)) {
        $file=__DIR__.'/private/payload.php';
        if (!is_file($file)) throw new RuntimeException('The reviewed content package is not installed.');
        $data=require $file;
        if (($data['schema']??0)!==1 || ($data['target']??'')!=='https://stg.finelevate360.com') throw new RuntimeException('Wrong migration package target.');
    }
    return $data;
}
function fe_migration_rewrite($value) {
    if (is_array($value)) { foreach($value as &$v)$v=fe_migration_rewrite($v);unset($v);return $value; }
    if (!is_string($value))return $value;
    return preg_replace('~https?://(?:www\.)?finelevate360\.com(?=/|[?#]|$)~i','https://stg.finelevate360.com',$value);
}
function fe_migration_source_meta($data) {
    static $meta;
    if (!isset($meta)) { $meta=[];foreach($data['meta'] as $r)$meta[(int)$r['post_id']][$r['meta_key']]=$r['meta_value']; }
    return $meta;
}
function fe_migration_existing_map() {
    $map=[];
    $ids=get_posts(['post_type'=>['page','post','event','finelev360_partner','finelev360_advisory','finelev360_item','finelev360_attr','finelev360_report','attachment','nav_menu_item','wpcf7_contact_form'],'post_status'=>['publish','draft','pending','future','trash','inherit'],'numberposts'=>-1,'fields'=>'ids','suppress_filters'=>true,'meta_query'=>['relation'=>'OR',['key'=>'_fe_public_source_id','compare'=>'EXISTS'],['key'=>'_fe_migrated_source_id','compare'=>'EXISTS']]]);
    foreach($ids as $id) { $source=(int)(get_post_meta($id,'_fe_public_source_id',true)?:get_post_meta($id,'_fe_migrated_source_id',true));if(isset($map[$source])&&$map[$source]!==$id)throw new RuntimeException('Duplicate existing source identity.');$map[$source]=(int)$id; }
    return $map;
}
function fe_migration_preflight($data) {
    if (!fe_migration_is_staging() || get_stylesheet()!=='finelevate-advisory')throw new RuntimeException('Staging hostname or redesign theme mismatch.');
    $allowed=['page','post','event','finelev360_partner','finelev360_advisory','finelev360_item','finelev360_attr','finelev360_report','attachment','nav_menu_item','wpcf7_contact_form'];
    $seen=[];
    foreach($data['posts'] as $p) {
        $id=(int)$p['ID'];
        if(!$id||isset($seen[$id])||!in_array($p['post_type'],$allowed,true)||!in_array($p['post_status'],['publish','draft','pending','future','trash','inherit'],true))throw new RuntimeException('Invalid or duplicate source record.');
        foreach(['post_author','user_login','user_pass','user_email','session_tokens'] as $key)if(array_key_exists($key,$p))throw new RuntimeException('Account data is forbidden.');
        $seen[$id]=true;
    }
    if(count($seen)!==$data['manifest']['records']||!empty($data['missing_media']))throw new RuntimeException('Incomplete source package.');
    foreach($data['meta'] as $r) {
        if(!isset($seen[(int)$r['post_id']])||!fe_migration_meta_allowed($r['meta_key']))throw new RuntimeException('Unreviewed metadata in package: '.$r['meta_key']);
    }
    return ['records'=>count($seen),'metadata'=>count($data['meta']),'media_files'=>count($data['media_files']),'existing_records'=>count(fe_migration_existing_map())];
}
function fe_migration_meta_allowed($key) {
    if(preg_match('/^_finelevate360_attr_[1-9][0-9]*$/D',$key))return true;
    if(preg_match('/^finelevate_(entity|functionality)_label(?:_(en|mk|sr|hr|si|sl|me))?$/D',$key))return true;
    if(preg_match('/^_fa_[a-z0-9_]+$/D',$key))return true;
    return in_array($key,['_thumbnail_id','_wp_page_template','_wp_attached_file','_wp_attachment_metadata','_wp_attachment_image_alt','_event_agenda','_event_day','_event_month','_event_year','_event_location','_event_short_desc','_event_sponsors','_finelevate360_attr_description','_finelevate360_seed_key','_finelevate360_item_attrs','_finelevate360_item_research','_finelevate360_item_contact','_finelevate360_item_linkedin','_finelevate360_item_website','_finelevate360_partner_badge_class','_finelevate360_partner_badge_text','_finelevate360_partner_contact','_finelevate360_partner_linkedin','_finelevate360_partner_website','_finelevate360_advisory_company','_finelevate360_advisory_contact','_finelevate360_advisory_job_title','_finelevate360_advisory_linkedin','_finelevate360_advisory_website','_page_attr_field1','_page_attr_field2','_page_attr_field3','finelevate360_bank_order','_menu_item_type','_menu_item_menu_item_parent','_menu_item_object_id','_menu_item_object','_menu_item_target','_menu_item_classes','_menu_item_xfn','_menu_item_url','_pll_menu_item','_form','_locale'],true);
}
function fe_migration_result($result) {
    if(is_wp_error($result))throw new RuntimeException($result->get_error_message());
    if($result===false)throw new RuntimeException('WordPress operation failed.');
    return $result;
}
function fe_migration_copy_tree($source,$destination) {
    if(!is_dir($source))throw new RuntimeException('Missing bundled source directory.');
    wp_mkdir_p($destination);
    $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::SELF_FIRST);
    foreach($it as $file) {
        if($file->isLink())throw new RuntimeException('Symlinks are not accepted in the package.');
        $relative=substr($file->getPathname(),strlen($source)+1);$target=$destination.'/'.$relative;
        if($file->isDir())wp_mkdir_p($target);else if(!copy($file->getPathname(),$target))throw new RuntimeException('Unable to copy bundled file.');
    }
}
function fe_migration_official_plugins() {
    return ['classic-editor','classic-widgets','complianz-gdpr','conditionally-display-featured-image-on-singular-pages','contact-form-7','cookie-law-info','duplicate-page','duplicator','flamingo','intuitive-custom-post-order','loco-translate','mailin','polylang','safe-svg','seo-by-rank-math','simple-custom-post-order','string-locator','vibe-ai','wp-mail-smtp'];
}
function fe_migration_install_official($slug,&$state) {
    require_once ABSPATH.'wp-admin/includes/plugin.php';
    require_once ABSPATH.'wp-admin/includes/plugin-install.php';
    require_once ABSPATH.'wp-admin/includes/class-wp-upgrader.php';
    $info=fe_migration_result(plugins_api('plugin_information',['slug'=>$slug,'fields'=>['sections'=>false]]));
    if(!empty($info->requires_php)&&version_compare(PHP_VERSION,$info->requires_php,'<'))throw new RuntimeException('Plugin PHP requirement is not met: '.$slug);
    if(!empty($info->requires)&&version_compare(get_bloginfo('version'),$info->requires,'<'))throw new RuntimeException('Plugin WordPress requirement is not met: '.$slug);
    $plugins=get_plugins();$file='';
    foreach($plugins as $path=>$plugin)if(strpos($path,$slug.'/')===0){$file=$path;break;}
    $outdated=$file&&version_compare($plugins[$file]['Version'],$info->version,'<');
    if(!$file||$outdated) {
        $url=$info->download_link;
        if(wp_parse_url($url,PHP_URL_HOST)!=='downloads.wordpress.org'||wp_parse_url($url,PHP_URL_SCHEME)!=='https')throw new RuntimeException('Plugin package must come from official WordPress.org.');
        $upgrader=new Plugin_Upgrader(new Automatic_Upgrader_Skin());
        fe_migration_result($upgrader->install($url,['overwrite_package'=>$outdated]));$file=$upgrader->plugin_info();
        if(!$file)throw new RuntimeException('Plugin installer did not return the installed path.');
    }
    // WPVibe stays inactive: connecting it could create persistent access credentials.
    $inactive=['mailin','cookie-law-info','duplicator','vibe-ai'];
    if(!in_array($slug,$inactive,true)&&!is_plugin_active($file))fe_migration_result(activate_plugin($file));
    $now=get_plugin_data(WP_PLUGIN_DIR.'/'.$file,false,false);
    $state['plugin_versions'][$slug]=['version'=>$now['Version'],'active'=>is_plugin_active($file),'official_source'=>true];
}
function fe_migration_map_id($id,$map,$zero=true) {
    $id=(int)$id;if(!$id&&$zero)return 0;
    if(!isset($map[$id]))throw new RuntimeException('Unmapped referenced record: '.$id);
    return (int)$map[$id];
}
function fe_migration_native_meta($source,$key,$value,$state,$data) {
    $map=$state['post_map'];
    if(preg_match('/^_finelevate360_attr_([0-9]+)$/D',$key,$match)&&!isset($state['attribute_map'][(int)$match[1]]))return ['_fe_unresolved_source_attr_'.$match[1],$value];
    if($key==='_finelevate360_item_attrs')$value=array_intersect_key((array)$value,$state['attribute_map']);
    if($key==='_finelevate360_item_attrs'||preg_match('/^_finelevate360_attr_[0-9]+$/D',$key)) {
        $mapped=\Finelevate\Migration\remap_rating_meta([$key=>$value],$state['attribute_map']);
        return [array_key_first($mapped),reset($mapped)];
    }
    if($key==='_finelevate360_item_research')$value=\Finelevate\Migration\remap_research($value,$state['attribute_map']);
    elseif($key==='_thumbnail_id')$value=$value?fe_migration_map_id($value,$map):0;
    elseif($key==='finelevate360_bank_order')$value=implode(',',array_map(function($id)use($map){return fe_migration_map_id(trim($id),$map,false);},explode(',',$value)));
    elseif($key==='_menu_item_menu_item_parent')$value=fe_migration_map_id($value,$map);
    elseif($key==='_menu_item_object_id') {
        $meta=fe_migration_source_meta($data)[$source]??[];$type=$meta['_menu_item_type']??'';
        $value=$type==='custom'?($map[(int)$value]??0):fe_migration_map_id($value,$type==='taxonomy'?$state['term_map']:$map);
    }
    // The redesigned wrapper owns page templates; source template names are retained as provenance only.
    if($key==='_wp_page_template')$key='_fe_migrated_source_template';
    return [$key,fe_migration_rewrite($value)];
}
function fe_migration_run_batch(&$s,$d) {
    $phase=$s['phase'];$index=$s['index']??0;
    if($phase==='custom') {
        require_once ABSPATH.'wp-admin/includes/plugin.php';
        foreach(['finelevate360-directory/finelevate360-directory.php','events-cpt/events-cpt.php','finelevate-awards/finelevate-awards.php','finelevate-title-text-image-widget/finelevate-title-text-image-widget.php'] as $file) {
            $slug=dirname($file);fe_migration_copy_tree(__DIR__.'/bundle/plugins/'.$slug,WP_PLUGIN_DIR.'/'.$slug);wp_clean_plugins_cache(false);
            // Skip source activation seed routines; source content is imported explicitly below.
            fe_migration_result(activate_plugin($file,'',false,true));
        }
        fe_migration_copy_tree(__DIR__.'/bundle/languages',WP_LANG_DIR);
        $s['phase']='official';$s['index']=0;return 'Custom plugin code and translation catalogues installed.';
    }
    if($phase==='official') {
        $slugs=fe_migration_official_plugins();
        if(isset($slugs[$index])){fe_migration_install_official($slugs[$index],$s);$s['index']=$index+1;return 'Official plugin checked: '.$slugs[$index];}
        $s['phase']='languages';$s['index']=0;return 'Official plugins installed without production settings.';
    }
    if($phase==='languages') {
        if(!function_exists('PLL')||!function_exists('pll_set_post_language'))throw new RuntimeException('Polylang is required before content import.');
        foreach($d['terms'] as $t)if($t['taxonomy']==='language') {
            $lang=PLL()->model->get_language($t['slug']);
            if(!$lang){$a=$t['decoded_description'];$a['slug']=$t['slug'];$a['name']=$t['name'];$a['no_default_cat']=true;$lang=fe_migration_result(PLL()->model->add_language($a));}
            $s['term_map'][(int)$t['term_id']]=$lang->term_id;$s['language_slugs'][(int)$t['term_taxonomy_id']]=$t['slug'];
        }
        PLL()->model->update_default_lang('en');
        PLL()->model->options['media_support']=true;PLL()->model->options['sync']=[];PLL()->model->options['redirect_lang']=false;PLL()->model->options->save();
        $s['phase']='terms';$s['index']=0;return 'Six source languages configured; production account settings excluded.';
    }
    if($phase==='terms') {
        foreach($d['terms'] as $t) {
            if(!in_array($t['taxonomy'],['finelev360_cat','category','post_tag','nav_menu'],true))continue;
            $existing=get_term_by('slug',$t['slug'],$t['taxonomy']);
            if($existing)$id=$existing->term_id;
            else {$r=fe_migration_result(wp_insert_term($t['name'],$t['taxonomy'],['slug'=>$t['slug'],'description'=>$t['description']]));$id=$r['term_id'];}
            $s['term_map'][(int)$t['term_id']]=(int)$id;
        }
        foreach($d['terms'] as $t)if(!empty($t['parent'])&&isset($s['term_map'][(int)$t['term_id']],$s['term_map'][(int)$t['parent']]))fe_migration_result(wp_update_term($s['term_map'][(int)$t['term_id']],$t['taxonomy'],['parent'=>$s['term_map'][(int)$t['parent']]]));
        $s['phase']='files';$s['index']=0;return 'Source taxonomies and menus mapped into staging IDs.';
    }
    if($phase==='files') {
        $files=array_merge(array_map(function($r){$r['kind']='media';return $r;},$d['media_files']),array_map(function($r){$r['kind']='site';return $r;},$d['site_files']));
        $batch=array_slice($files,$index,80);$uploads=wp_upload_dir();
        foreach($batch as $r){$rel=$r['path'];if(strpos($rel,'..')!==false||substr($rel,0,1)==='/')throw new RuntimeException('Unsafe media path.');$src=__DIR__.'/bundle/'.$r['kind'].'/'.$rel;$dst=($r['kind']==='media'?$uploads['basedir']:rtrim(ABSPATH,'/')).'/'.$rel;if(!is_file($src)||!hash_equals($r['sha256'],hash_file('sha256',$src)))throw new RuntimeException('Bundled media checksum mismatch.');wp_mkdir_p(dirname($dst));if(!copy($src,$dst)||!hash_equals($r['sha256'],hash_file('sha256',$dst)))throw new RuntimeException('Staging media copy failed verification.');}
        $s['index']=$index+count($batch);if($s['index']>=count($files)){$s['phase']='posts';$s['index']=0;}
        return 'Verified public media files copied: '.($index+count($batch)).'/'.count($files);
    }
    if($phase==='posts') {
        $posts=array_values(array_filter($d['posts'],function($p){return $p['post_type']!=='nav_menu_item';}));$batch=array_slice($posts,$index,40);
        foreach($batch as $p) {
            $source=(int)$p['ID'];$dest=$s['post_map'][$source]??0;
            if($dest){if(get_post_type($dest)!==$p['post_type'])throw new RuntimeException('Existing source type mismatch.');}
            else {
                $args=['post_type'=>$p['post_type'],'post_status'=>$p['post_status']==='inherit'?'inherit':'draft','post_title'=>$p['post_title'],'post_name'=>$p['post_name'],'post_content'=>wp_kses_post(fe_migration_rewrite($p['post_content'])),'post_excerpt'=>wp_kses_post($p['post_excerpt']),'post_date'=>$p['post_date'],'post_date_gmt'=>$p['post_date_gmt'],'menu_order'=>(int)$p['menu_order'],'post_author'=>get_current_user_id(),'comment_status'=>'closed','ping_status'=>'closed'];
                if($p['post_type']==='attachment')$args['post_mime_type']=$p['post_mime_type'];
                $dest=fe_migration_result(wp_insert_post(wp_slash($args),true));$s['post_map'][$source]=(int)$dest;
                add_post_meta($dest,'_fe_migrated_source_id',$source,true);
            }
            if($p['post_type']==='finelev360_attr')$s['attribute_map'][$source]=(int)$dest;
        }
        $s['index']=$index+count($batch);if($s['index']>=count($posts)){$s['phase']='menus';$s['index']=0;update_option('fe_staging_migration_research_rules',\Finelevate\Migration\remap_research_rules($s['post_map']),false);}
        return 'Content identities mapped: '.($index+count($batch)).'/'.count($posts);
    }
    if($phase==='menus') {
        $posts=array_values(array_filter($d['posts'],function($p){return $p['post_type']==='nav_menu_item';}));$meta=fe_migration_source_meta($d);$tt=[];foreach($d['terms'] as $t)$tt[(int)$t['term_taxonomy_id']]=$t;
        foreach(array_slice($posts,$index,25) as $p){$source=(int)$p['ID'];if(isset($s['post_map'][$source]))continue;$m=$meta[$source]??[];$menu=0;foreach($d['relationships'] as $rel)if((int)$rel['object_id']===$source&&($tt[(int)$rel['term_taxonomy_id']]['taxonomy']??'')==='nav_menu'){$menu=$s['term_map'][(int)$tt[(int)$rel['term_taxonomy_id']]['term_id']];break;}if(!$menu)throw new RuntimeException('Menu item lacks its source menu.');$type=$m['_menu_item_type']??'custom';$object=$m['_menu_item_object']??'custom';$object_id=$type==='custom'?0:fe_migration_map_id($m['_menu_item_object_id'],$type==='taxonomy'?$s['term_map']:$s['post_map'],false);$dest=fe_migration_result(wp_update_nav_menu_item($menu,0,['menu-item-title'=>$p['post_title'],'menu-item-description'=>$p['post_content'],'menu-item-object'=>$object,'menu-item-object-id'=>$object_id,'menu-item-type'=>$type,'menu-item-url'=>fe_migration_rewrite($m['_menu_item_url']??''),'menu-item-target'=>$m['_menu_item_target']??'','menu-item-classes'=>implode(' ',(array)($m['_menu_item_classes']??[])),'menu-item-xfn'=>$m['_menu_item_xfn']??'','menu-item-status'=>$p['post_status'],'menu-item-position'=>(int)$p['menu_order']]));$s['post_map'][$source]=(int)$dest;add_post_meta($dest,'_fe_migrated_source_id',$source,true);}
        $s['index']=min($index+25,count($posts));if($s['index']>=count($posts)){$s['phase']='metadata';$s['index']=0;}
        return 'Menu items mapped: '.$s['index'].'/'.count($posts);
    }
    if($phase==='metadata') {
        foreach(array_slice($d['meta'],$index,180) as $r){$source=(int)$r['post_id'];$dest=fe_migration_map_id($source,$s['post_map'],false);[$key,$value]=fe_migration_native_meta($source,$r['meta_key'],$r['meta_value'],$s,$d);update_post_meta($dest,$key,wp_slash($value));if($r['meta_key']==='_finelevate360_item_attrs'){$unresolved=array_diff_key((array)$r['meta_value'],$s['attribute_map']);if($unresolved)update_post_meta($dest,'_fe_unresolved_source_rating_rows',wp_slash($unresolved));}}
        $s['index']=min($index+180,count($d['meta']));if($s['index']>=count($d['meta'])){$s['phase']='relationships';$s['index']=0;}
        return 'Source metadata applied: '.$s['index'].'/'.count($d['meta']);
    }
    if($phase==='relationships') {
        $tt=[];foreach($d['terms'] as $t)$tt[(int)$t['term_taxonomy_id']]=$t;$groups=[];
        foreach($d['relationships'] as $r){$t=$tt[(int)$r['term_taxonomy_id']]??null;if(!$t||!isset($s['post_map'][(int)$r['object_id']]))continue;$dest=$s['post_map'][(int)$r['object_id']];if($t['taxonomy']==='language'){pll_set_post_language($dest,$t['slug']);continue;}if(in_array($t['taxonomy'],['finelev360_cat','category','post_tag','nav_menu'],true))$groups[$dest][$t['taxonomy']][]=$s['term_map'][(int)$t['term_id']];}
        foreach($groups as $dest=>$taxes)foreach($taxes as $tax=>$ids)fe_migration_result(wp_set_object_terms($dest,array_values(array_unique(array_map('intval',$ids))),$tax,false));
        $tl=[];foreach($d['terms'] as $t)if($t['taxonomy']==='term_language')$tl[(int)$t['term_taxonomy_id']]=substr($t['slug'],4);
        foreach($d['term_languages'] as $r)if(isset($s['term_map'][(int)$r['object_id']],$tl[(int)$r['term_taxonomy_id']]))pll_set_term_language($s['term_map'][(int)$r['object_id']],$tl[(int)$r['term_taxonomy_id']]);
        $s['phase']='translations';$s['index']=0;return 'Taxonomy and language relationships applied through native APIs.';
    }
    if($phase==='translations') {
        foreach($d['terms'] as $t)if(in_array($t['taxonomy'],['post_translations','term_translations'],true)){$map=$t['taxonomy']==='post_translations'?$s['post_map']:$s['term_map'];$group=[];foreach((array)$t['decoded_description'] as $lang=>$source){if(!isset($map[(int)$source]))throw new RuntimeException('Missing translation identity: '.$source);$group[$lang]=$map[(int)$source];}if($t['taxonomy']==='post_translations')pll_save_post_translations($group);else pll_save_term_translations($group);}
        foreach($d['term_meta'] as $r){$dest=$s['term_map'][(int)$r['term_id']]??0;if($dest&&$r['meta_key']==='_pll_strings_translations')update_term_meta($dest,$r['meta_key'],wp_slash($r['meta_value']));}
        $s['phase']='finish';$s['index']=0;return 'Post, term and string translations linked through Polylang.';
    }
    if($phase==='finish') {
        foreach($d['posts'] as $p){$source=(int)$p['ID'];$dest=$s['post_map'][$source];$parent=(int)$p['post_parent'];$args=['ID'=>$dest,'menu_order'=>(int)$p['menu_order'],'post_status'=>$p['post_status'],'post_parent'=>$parent?($s['post_map'][$parent]??0):0];if($parent&&!isset($s['post_map'][$parent]))$s['unmapped_parents'][$source]=$parent;$current=get_post_field('post_content',$dest);$rewritten=fe_migration_rewrite($current);if($rewritten!==$current)$args['post_content']=wp_slash($rewritten);fe_migration_result(wp_update_post($args,true));}
        foreach($d['options'] as $r){if($r['option_name']==='finelevate_awards_page_id')update_option($r['option_name'],fe_migration_map_id($r['option_value'],$s['post_map'],false),false);elseif($r['option_name']==='widget_finelevate_title_text_image'){$widgets=$r['option_value'];foreach($widgets as &$widget)if(is_array($widget)&&!empty($widget['image_id']))$widget['image_id']=fe_migration_map_id($widget['image_id'],$s['post_map'],false);unset($widget);update_option($r['option_name'],fe_migration_rewrite($widgets),false);$sidebars=get_option('sidebars_widgets',[]);$inactive=$sidebars['wp_inactive_widgets']??[];foreach($widgets as $key=>$widget)if(is_int($key)&&is_array($widget))$inactive[]='finelevate_title_text_image-'.$key;$sidebars['wp_inactive_widgets']=array_values(array_unique($inactive));update_option('sidebars_widgets',$sidebars,false);}}
        $locations=[];$menus=[];foreach($d['menu_locations'] as $location=>$langs)foreach($langs as $lang=>$source){$menus[$location][$lang]=fe_migration_map_id($source,$s['term_map'],false);if($lang==='en')$locations[$location]=$menus[$location][$lang];}
        $prior=get_theme_mod('nav_menu_locations',[]);set_theme_mod('nav_menu_locations',array_merge($prior,$locations));$nav=PLL()->model->options['nav_menus'];$nav[get_stylesheet()]=$menus;PLL()->model->options['nav_menus']=$nav;PLL()->model->options['sync']=$d['source_sync'];PLL()->model->options->save();
        foreach($d['posts'] as $p)if($p['post_type']==='wpcf7_contact_form'){update_post_meta($s['post_map'][(int)$p['ID']],'_mail',['active'=>false,'recipient'=>'','sender'=>'','subject'=>'Staging delivery disabled','body'=>'']);update_post_meta($s['post_map'][(int)$p['ID']],'_mail_2',['active'=>false]);}
        update_option('blog_public','0');update_option('fe_staging_migration_complete',true,false);flush_rewrite_rules(false);wp_cache_flush();do_action('litespeed_purge_all');$s['phase']='verify';return 'Source publication states restored; staging protection remains active.';
    }
    if($phase==='verify'){$s['verification']=fe_migration_verify($s,$d);$s['phase']='done';return 'Verification completed. Review any reported issues before acceptance.';}
    return 'Migration already completed.';
}
function fe_migration_verify($s,$d) {
    $issues=[];$counts=[];update_meta_cache('post',array_values($s['post_map']));
    foreach($d['posts'] as $p){$dest=$s['post_map'][(int)$p['ID']]??0;$post=$dest?get_post($dest):null;if(!$post||$post->post_type!==$p['post_type']||$post->post_status!==$p['post_status'])$issues[]='Post identity/status mismatch: '.$p['ID'];$counts[$p['post_type']][$p['post_status']]=($counts[$p['post_type']][$p['post_status']]??0)+1;}
    foreach($d['meta'] as $r){[$key,$expected]=fe_migration_native_meta((int)$r['post_id'],$r['meta_key'],$r['meta_value'],$s,$d);$actual=get_post_meta($s['post_map'][(int)$r['post_id']],$key,true);if(serialize($actual)!==serialize($expected)&&!(is_scalar($actual)&&is_scalar($expected)&&(string)$actual===(string)$expected))$issues[]='Metadata mismatch: '.$r['post_id'].'/'.$key;}
    foreach($d['media_files'] as $r){$file=wp_upload_dir()['basedir'].'/'.$r['path'];if(!is_file($file)||!hash_equals($r['sha256'],hash_file('sha256',$file)))$issues[]='Media mismatch: '.$r['path'];}
    $unresolved=[];foreach($d['meta'] as $r)if(preg_match('/^_finelevate360_attr_([0-9]+)$/D',$r['meta_key'],$m)&&!isset($s['attribute_map'][(int)$m[1]]))$unresolved[$m[1]]=($unresolved[$m[1]]??0)+1;
    return ['unresolved_deleted_capability_values'=>$unresolved,'record_counts'=>$counts,'mapped_records'=>count($s['post_map']),'mapped_attributes'=>count($s['attribute_map']),'media_files'=>count($d['media_files']),'issues'=>$issues,'unmapped_parents'=>$s['unmapped_parents']??[],'theme'=>get_stylesheet(),'users'=>(int)count_users()['total_users'],'noindex'=>(string)get_option('blog_public')==='0','mail_blocked'=>apply_filters('pre_wp_mail',null,[])===false,'http_blocked'=>is_wp_error(apply_filters('pre_http_request',false,['method'=>'POST'],'https://api.brevo.com/v3/smtp/email')),'cron_blocked'=>wp_get_ready_cron_jobs()===[]];
}
function fe_migration_import_page() {
    echo '<div class="wrap"><h1>Finelevate staging migration</h1><p>Staging-only migration. Production accounts, credentials, submissions, outbound configuration and scheduled jobs are excluded.</p>';
    $state=get_option('fe_staging_migration_state',[]);
    try {
        $data=fe_migration_payload();
        if(($_SERVER['REQUEST_METHOD']??'')==='POST') {
            check_admin_referer('fe_staging_migration');
            if(!fe_migration_is_staging()||!current_user_can('manage_options'))throw new RuntimeException('Staging administrator access required.');
            $action=sanitize_key($_POST['fe_migration_action']??'');
            if($action==='preflight') {$report=fe_migration_preflight($data);echo '<div class="notice notice-success"><p>Preflight passed: '.esc_html(wp_json_encode($report)).'</p></div>';}
            elseif($action==='run') {
                if(!$state){fe_migration_preflight($data);$state=['phase'=>'custom','index'=>0,'post_map'=>fe_migration_existing_map(),'term_map'=>[],'attribute_map'=>[],'plugin_versions'=>[],'started'=>gmdate('c')];}
                $message=fe_migration_run_batch($state,$data);unset($state['last_error']);$state['last_result']=$message;$state['updated']=gmdate('c');update_option('fe_staging_migration_state',$state,false);
                echo '<div class="notice notice-success"><p>'.esc_html($message).'</p></div>';
            }
        }
        $display=$state;unset($display['post_map'],$display['term_map'],$display['attribute_map']);
        echo '<p><strong>Phase:</strong> '.esc_html($state['phase']??'ready').'; <strong>Batch index:</strong> '.(int)($state['index']??0).'</p>';
        echo '<form method="post">';wp_nonce_field('fe_staging_migration');
        echo '<button class="button" name="fe_migration_action" value="preflight">Validate reviewed package</button> ';
        if(($state['phase']??'')!=='done')echo '<button class="button button-primary" name="fe_migration_action" value="run">Run next staging batch</button>';
        echo '</form><pre style="white-space:pre-wrap">'.esc_html(wp_json_encode($display,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)).'</pre>';
    } catch(Throwable $error) {
        if($state){$state['last_error']=$error->getMessage();update_option('fe_staging_migration_state',$state,false);}
        echo '<div class="notice notice-error"><p>Stopped safely: '.esc_html($error->getMessage()).'</p></div>';
        echo '<p>The journal is retained. Reconcile the error before continuing; no automatic retry or rollback ran.</p>';
    }
    echo '</div>';
}
