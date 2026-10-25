<?php
namespace RankMath\Traits {trait Meta {public function update_meta($type,$id,$key,$value){return \update_metadata($type,$id,$key,$value);}}}
namespace RankMath\Helpers {class Str {public static function starts_with($prefix,$text){return strpos($text,$prefix)===0;}}}
namespace RankMath\Schema {class DB {public static function get_schemas(...$args){return [];}}}
namespace RankMath\Rest {
  class Rest_Helper {const BASE='rankmath/v1';}
  class Sanitize {public static function get(){return new self;}public function sanitize($key,$value){return $value;}}
}
namespace {
require __DIR__.'/test_regional_sync_isolation.php';
class WP_REST_Controller {}
class WP_REST_Request {
  public $params;private $route;private $method;
  public function __construct($params,$route='/rankmath/v1/updateMeta',$method='POST'){$this->params=$params;$this->route=$route;$this->method=$method;}
  public function get_param($name){return $this->params[$name]??null;}
  public function get_route(){return $this->route;}
  public function get_method(){return $this->method;}
}
function remove_all_filters($name){global $actions;unset($actions[$name]);}
require __DIR__.'/fixtures/rankmath-1.0.280/class-shared.php';

// Reset all request-local hooks, Polylang objects, superglobals and context,
// retaining only the simulated persisted database between HTTP requests.
function next_publication_request() {
  global $meta,$posts,$terms,$testFamilyOverride,$testDeniedCaps;
  $saved=unserialize(serialize([$meta,$posts,$terms]));
  setup_isolation_fixture();[$meta,$posts,$terms]=$saved;
  $_POST=[];$_GET=[];$_SERVER=[];$testFamilyOverride=null;$testDeniedCaps=[];
}
function score_request($id,$meta,$marked=true) {
  $params=['objectID'=>$id,'objectType'=>'post','meta'=>$meta,'content'=>''];
  if($marked)$params['finelevate360_publication']=['post_id'=>$id,'nonce'=>'valid','expected_hash'=>finelevate360_regional_metadata_hash($id)];
  return new WP_REST_Request($params);
}
function rankmath_rest_callback($request) {return (new \RankMath\Rest\Shared)->update_metadata($request);}
function score_fixture($existing=false) {
  global $meta,$posts,$terms;
  setup_isolation_fixture();$posts[2488]->post_title='Adriatic Bank AD Podgorica';
  [$matrix,$evidence]=fixture('me',2488);$meta[2488]['_finelevate360_item_attrs']=$matrix;$meta[2488]['_finelevate360_item_research']=$evidence;$terms[2488]['finelev360_cat']=[132];
  if($existing){$meta[2488]['rank_math_seo_score']='71';$meta[2488]['rank_math_contentai_score']=['keywords'=>'8','wordCount'=>'3'];}
  return [$matrix,$evidence];
}
$incoming=['rank_math_seo_score'=>'6','rank_math_contentai_score'=>['keywords'=>'74.51','wordCount'=>'0']];
foreach([false,true] as $existing) {
  [$matrix,$evidence]=score_fixture($existing);$before=repair_state();$rest=score_request(2488,$incoming);
  next_publication_request();same($finelevate360_regional_isolation,[]);rankmath_rest_callback($rest);
  same(repair_state(),$before); // Missing stays absent; existing scores retain exact values.
  next_publication_request();$next=$matrix;$next_evidence=$evidence;$attr=finelevate360_research_attr_ids('me')[0];edit_cell($next,$next_evidence,$attr,'yes');
  $_POST=request_for(2488,$next,$next_evidence)+['action'=>'editpost','post_ID'=>2488];$_SERVER['REQUEST_METHOD']='POST';
  $family_before=serialize([$meta[2322],$meta[2323],$terms,$posts]);do_action('admin_init');do_action('save_post',2488,$posts[2488]);
  foreach($incoming as $key=>$value)update_post_meta(2488,$key,$value);do_action('pll_save_post',2488,$posts[2488],pll_get_post_translations(2488));
  same($meta[2488]['_finelevate360_item_attrs'],$next);same($meta[2488]['_finelevate360_item_research'],$next_evidence);
  same(serialize([$meta[2322],$meta[2323],$terms,$posts]),$family_before);
  same(metadata_exists('post',2488,'rank_math_seo_score'),$existing);
  if($existing){same($meta[2488]['rank_math_seo_score'],'71');same($meta[2488]['rank_math_contentai_score'],['keywords'=>'8','wordCount'=>'3']);}
  // A replay after the native save is stale. It cannot inherit native globals.
  next_publication_request();$after_native=repair_state();rejects(fn()=>rankmath_rest_callback($rest));same(repair_state(),$after_native);
  // A fresh marked repeat is harmless and does not create/delete either score.
  $fresh=score_request(2488,$incoming);next_publication_request();rankmath_rest_callback($fresh);same(repair_state(),$after_native);
}
foreach(['nonce','expected_hash','post_id'] as $key){score_fixture();$r=score_request(2488,$incoming);$r->params['finelevate360_publication'][$key]='invalid';next_publication_request();$b=repair_state();rejects(fn()=>rankmath_rest_callback($r));same(repair_state(),$b);}
score_fixture();$r=score_request(2488,$incoming);next_publication_request();$testDeniedCaps=['edit_post'];$b=repair_state();rejects(fn()=>rankmath_rest_callback($r));same(repair_state(),$b);$testDeniedCaps=[];
// Mixed payloads retain every non-score key, rather than blanking the metadata array.
score_fixture(true);$r=score_request(2488,$incoming+['rank_math_description'=>'intentional SEO text']);next_publication_request();rankmath_rest_callback($r);same($meta[2488]['rank_math_description'],'intentional SEO text');same($meta[2488]['rank_math_seo_score'],'71');
// Ordinary edits, foreign countries and production retain the original callback behavior.
foreach([[2488,'https://stg.finelevate360.com/',false],[11,'https://stg.finelevate360.com/',true],[2488,'https://finelevate360.com/',true],[2488,'https://example.com/',true]] as [$id,$host,$marked]){
  // Compare the entire real callback result to its original unfiltered behavior,
  // including the installed Polylang callback's normal country-dependent effects.
  score_fixture();$r=score_request($id,$incoming+['rank_math_description'=>'ordinary'],$marked);next_publication_request();$origin=$host;
  remove_filter('rank_math/filter_metadata','finelevate360_regional_preserve_rankmath_scores',PHP_INT_MAX);rankmath_rest_callback($r);$ordinary_expected=repair_state();
  score_fixture();$r=score_request($id,$incoming+['rank_math_description'=>'ordinary'],$marked);next_publication_request();$origin=$host;
  same(finelevate360_regional_preserve_rankmath_scores($r->get_param('meta'),$r),$r->get_param('meta'));
  rankmath_rest_callback($r);same(repair_state(),$ordinary_expected);
  same($meta[$id]['rank_math_seo_score'],'6');same($meta[$id]['rank_math_description'],'ordinary');
}

function adriatic_repair_fixture() {
  global $meta;
  score_fixture();$meta[2488]['rank_math_seo_score']='6';$meta[2488]['rank_math_contentai_score']=['keywords'=>'74.51','wordCount'=>'0'];
  $plan=['operation'=>'adriatic-score-additions','id'=>2488,'title'=>'Adriatic Bank AD Podgorica','family_state_hashes'=>[],'remove_metadata'=>[]];
  foreach([2322,2323,2488] as $id)$plan['family_state_hashes'][$id]=finelevate360_regional_bank_state_hash($id);
  foreach(['rank_math_seo_score','rank_math_contentai_score'] as $key)$plan['remove_metadata'][$key]=['before_absent'=>true,'expected_value'=>$meta[2488][$key]];
  return $plan;
}
foreach(['before_absent','expected_value','state_hash','extra_key','wrong_id','denied'] as $case){
  $p=adriatic_repair_fixture();next_publication_request();
  if($case==='before_absent')$p['remove_metadata']['rank_math_seo_score']['before_absent']=false;
  if($case==='expected_value')$p['remove_metadata']['rank_math_contentai_score']['expected_value']='wrong';
  if($case==='state_hash')$p['family_state_hashes'][2322]=str_repeat('0',64);
  if($case==='extra_key')$p['remove_metadata']['_thumbnail_id']=['before_absent'=>true,'expected_value'=>2488];
  if($case==='wrong_id')$p['id']=2487;
  if($case==='denied')$testDeniedCaps=['manage_options'];
  $b=repair_state();rejects(fn()=>finelevate360_regional_repair(json_encode($p)));same(repair_state(),$b);$testDeniedCaps=[];
}
$p=adriatic_repair_fixture();next_publication_request();$expected_meta=$meta;$expected_posts=serialize($posts);$expected_terms=$terms;
unset($expected_meta[2488]['rank_math_seo_score'],$expected_meta[2488]['rank_math_contentai_score']);
finelevate360_regional_repair(json_encode($p));same($meta,$expected_meta);same(serialize($posts),$expected_posts);same($terms,$expected_terms);
$after=repair_state();next_publication_request();rejects(fn()=>finelevate360_regional_repair(json_encode($p)));same(repair_state(),$after);
echo "Installed Rank Math REST callback, separate request resets, captured score-only restoration: $tests assertions passed (simulated WordPress storage; no live HTTP test).\n";
}
