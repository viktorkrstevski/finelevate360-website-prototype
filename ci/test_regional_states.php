<?php
// Execute the actual helper functions without requiring an installed WordPress.
define('ABSPATH', __DIR__);
define('FINELEVATE360_CPT_ITEM', 'finelev360_item');
define('FINELEVATE360_CPT_ATTR', 'finelev360_attr');
$origin='https://stg.finelevate360.com/'; $page=700; $meta=[];
$rules=['items'=>['hr'=>[11],'sl'=>[12]],'pages'=>['hr'=>701,'sl'=>702],'attributes'=>['hr'=>[21],'sl'=>[22]],'aliases'=>[]];
function home_url($unused) { global $origin; return $origin; }
function wp_parse_url($url,$component=-1) { return parse_url($url,$component); }
function get_option($key,$default=[]) { global $rules; return $key==='fe_staging_migration_research_rules' ? $rules : $default; }
function get_post_status($id) {return 'publish';}
function get_post_type($id) {return FINELEVATE360_CPT_ITEM;}
function get_queried_object_id() {global $page;return $page;}
function get_post_meta($id,$key,$single=true) {global $meta;return $meta[$id][$key]??'';}
function get_posts($args) {return array_map(function($id){return (object)['ID'=>$id,'post_title'=>'Feature '.$id];},$args['post__in']);}
$source=file_get_contents(__DIR__.'/../wordpress/plugins/finelevate360-directory/finelevate360-directory.php');
$start=strpos($source,'function finelevate360_migration_rule');
$end=strpos($source,'// END HR/SI RESEARCH HELPERS');
eval(substr($source,$start,$end-$start));
$tests=0;
function same($actual,$expected) {global $tests;$tests++;if($actual!==$expected)throw new RuntimeException('Assertion '.$tests.' failed');}
$sr=[2299,2300,2301,2302,2304,2303,2311,2305,2313,2306,2307,2315,2308,2319,2317,2321,2323,2325,2309];
$me=[2489,2485,2490,2487,2491,2493,2492,2488,2494,2486,2495];
foreach(['https://stg.finelevate360.com/','https://stg.finelevate360.com/sr/','https://stg.finelevate360.com/me/','https://stg.finelevate360.com:443/me/'] as $url) {
  $origin=$url;same(finelevate360_research_ids('sr'),$sr);same(finelevate360_research_ids('me'),$me);
  same(count(finelevate360_research_attr_ids('sr')),26);same(count(finelevate360_research_attr_ids('me')),26);
}
foreach(['https://finelevate360.com/sr/','https://example.com/me/','https://stg.finelevate360.com.evil.test/','http://stg.finelevate360.com/','https://stg.finelevate360.com:8443/sr/','https://user@stg.finelevate360.com/'] as $url) {
  $origin=$url;same(finelevate360_regional_staging_config('sr'),[]);same(finelevate360_regional_staging_config('me'),[]);
}
$origin='https://stg.finelevate360.com/sr/';
same(finelevate360_research_ids('hr'),[11]);same(finelevate360_research_ids('sl'),[12]);
same(finelevate360_research_market(2299),'sr');same(finelevate360_research_market(2487),'me');same(finelevate360_research_market(999),'');
$meta[700]['_fe_public_path']='/sr/digitalna-mapa-2/';same(finelevate360_research_page_market(),'sr');
$meta[700]['_fe_public_path']='/me/digitalna-mapa-banke/';same(finelevate360_research_page_market(),'me');
$page=701;same(finelevate360_research_page_market(),'hr');$page=702;same(finelevate360_research_page_market(),'sl');
$states=['yes','partial','no','unknown','not_applicable'];$attrs=finelevate360_research_attr_ids('sr');
foreach($attrs as $i=>$id)$meta[2299]['_finelevate360_item_attrs'][$id]=array_fill_keys(['mobile','desktop','biznis','privat'],$states[$i%5]);
$rows=finelevate360_research_rows(2299);same(count($rows),26);
foreach($rows as $i=>$row)foreach(['mobile','desktop','biznis','privat'] as $axis)same($row[$axis],$states[$i%5]);
$map=finelevate360_research_filter_map($rows);
foreach($rows as $row)same(isset($map[$row['id']]),$row['mobile']==='yes');
same(finelevate360_research_status('invalid'),'unknown');
foreach(['sr','me','hr','sl'] as $market)same(count(array_unique(array_intersect_key(finelevate360_research_labels($market),array_flip($states)))),5);
// Exercise the unchanged native save function after the regional gate is enabled.
define('FINELEVATE360_CPT_PARTNERS','finelev360_partner');
define('FINELEVATE360_CPT_ADVISORY','finelev360_advisory');
function wp_is_post_revision($id) {return false;}
function current_user_can($cap,$id=0) {global $testDeniedCaps;return !in_array($cap,$testDeniedCaps??[],true);}
function wp_verify_nonce($nonce,$action) {global $testExpectedNonceAction;return $nonce==='valid' && (!isset($testExpectedNonceAction) || $testExpectedNonceAction===$action);}
function finelevate360_attr_columns() {return array_fill_keys(['mobile','desktop','biznis','privat'],'label');}
function update_post_meta($id,$key,$value) {if(function_exists('regional_test_update_meta'))return regional_test_update_meta($id,$key,$value);global $meta,$writes;$writes[]=[$id,$key];$meta[$id][$key]=$value;}
$save_source=file_get_contents(__DIR__.'/../wordpress/plugins/finelevate360-directory/includes/metaboxes.php');
$save_source=substr($save_source,strpos($save_source,'function finelevate360_save_meta('));
eval(preg_replace('/\?>\s*$/','',$save_source));
foreach(['sr'=>2299,'me'=>2487] as $market=>$bank_id) {
  $attrs=finelevate360_research_attr_ids($market);
  $expected=[999=>['mobile'=>'historical']];
  $_POST=['finelevate360_meta_nonce'=>'valid','finelevate360_item_attr_cols'=>[]];
  foreach($attrs as $i=>$id) {
    $expected[$id]=[];
    foreach(['mobile','desktop','biznis','privat'] as $n=>$axis)$expected[$id][$axis]=$states[($i+$n)%5];
    $_POST['finelevate360_item_attr_cols'][$id]=$expected[$id];
  }
  $meta[$bank_id]['_finelevate360_item_attrs']=[999=>['mobile'=>'historical']];
  $meta[$bank_id]['_finelevate360_item_research']=['private_evidence'=>'unchanged'];
  $meta[$bank_id]['_unrelated']='preserve';
  finelevate360_save_meta($bank_id,(object)['post_type'=>FINELEVATE360_CPT_ITEM]);
  same($meta[$bank_id]['_finelevate360_item_attrs'],$expected);
  same($meta[$bank_id]['_finelevate360_item_research'],['private_evidence'=>'unchanged']);
  same($meta[$bank_id]['_unrelated'],'preserve');
  foreach($attrs as $id) {
    $row=$expected[$id];
    $aggregate=in_array('yes',$row,true)?'yes':(in_array('partial',$row,true)?'partial':(in_array('unknown',$row,true)?'unknown':(in_array('no',$row,true)?'no':'not_applicable')));
    same($meta[$bank_id]['_finelevate360_attr_'.$id],$aggregate);
  }
}
function add_action($name,$callback,$priority=10,$args=1) {global $actions;$actions[$name][$priority][]=[$callback,$args];}
function add_filter($name,$callback,$priority=10,$args=1) {add_action($name,$callback,$priority,$args);}
$native_source=file_get_contents(__DIR__.'/../wordpress/plugins/finelevate360-directory/includes/metaboxes.php');
preg_match("/add_action\('save_post', 'finelevate360_save_meta', 10, 2\);/",$native_source,$native_registration);
eval($native_registration[0]);
require __DIR__.'/../wordpress/plugins/finelevate360-directory/includes/regional-evidence.php';
function rejects($fn) {global $tests;$tests++;try {$fn();} catch(Throwable $e) {return;} throw new RuntimeException('Expected evidence rejection');}
foreach(['sr'=>2299,'me'=>2487] as $market=>$bank_id) {
  $evidence=['schema_version'=>1,'as_of'=>'2026-10-25','bank'=>['bank_key'=>$market.'_fixture','language'=>$market],
    'cells'=>[],'warnings'=>[],'sources'=>[['source_id'=>'official','url'=>'https://bank.example/feature','title'=>'Official feature']],
    'publication_binding'=>['bank_post_id'=>$bank_id,'language'=>$market]];
  foreach(finelevate360_research_attr_ids($market) as $id) {
    $evidence['cells'][$id]=array_fill_keys(['mobile','desktop','biznis','privat'],[
      'source_ids'=>['official'],'source_urls'=>['https://bank.example/feature'],'evidence_note'=>'Qualified evidence',
      'publication_status'=>'partial',
      'caveat'=>'Eligibility varies','original_record'=>['status'=>'Partial','limitations'=>'Restricted customer segment'],
    ]);
  }
  same(finelevate360_regional_validate_evidence(json_encode($evidence),$bank_id),$evidence);
  $bad=$evidence;$bad['publication_binding']['bank_post_id']=2065;rejects(fn()=>finelevate360_regional_validate_evidence(json_encode($bad),$bank_id));
  $bad=$evidence;unset($bad['cells'][array_key_first($bad['cells'])]);rejects(fn()=>finelevate360_regional_validate_evidence(json_encode($bad),$bank_id));
  $bad=$evidence;$id=array_key_first($bad['cells']);$bad['cells'][$id]['mobile']['source_ids']=['missing'];rejects(fn()=>finelevate360_regional_validate_evidence(json_encode($bad),$bank_id));
  $bad=$evidence;$bad['cells'][$id]['mobile']['source_urls']=['https://wrong.example/'];rejects(fn()=>finelevate360_regional_validate_evidence(json_encode($bad),$bank_id));
  $bad=$evidence;$bad['cells'][$id]['mobile']['evidence_note']='';rejects(fn()=>finelevate360_regional_validate_evidence(json_encode($bad),$bank_id));
  rejects(fn()=>finelevate360_regional_validate_evidence('bad JSON',$bank_id));
}
echo "Regional gates, actual row/filter/native save helpers and evidence validation: $tests assertions passed.\n";
