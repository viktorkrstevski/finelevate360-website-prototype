<?php
// Real installed Polylang callbacks, simulated WordPress hooks/storage. No database or network.
require __DIR__.'/test_regional_save_guard.php';
define('POLYLANG_VERSION','3.9.1');
define('FINELEVATE360_TAX_ITEMCAT','finelev360_cat');

function remove_action($hook,$callback,$priority=10) {
  global $actions;$found=false;
  foreach($actions[$hook][$priority]??[] as $i=>$entry)if($entry[0]===$callback){unset($actions[$hook][$priority][$i]);$found=true;}
  if(empty($actions[$hook][$priority]))unset($actions[$hook][$priority]);
  return $found;
}
function remove_filter($hook,$callback,$priority=10) {return remove_action($hook,$callback,$priority);}
function has_action($hook,$callback) {global $actions;foreach($actions[$hook]??[] as $priority=>$entries)foreach($entries as $entry)if($entry[0]===$callback)return $priority;return false;}
function has_filter($hook,$callback) {return has_action($hook,$callback);}
function do_action($hook,...$args) {global $actions;$entries=$actions[$hook]??[];ksort($entries);foreach($entries as $list)foreach($list as [$callback,$accepted])call_user_func_array($callback,array_slice($args,0,$accepted));}
function apply_filters($hook,$value,...$args) {global $actions;$entries=$actions[$hook]??[];ksort($entries);foreach($entries as $list)foreach($list as [$callback,$accepted])$value=call_user_func_array($callback,array_slice(array_merge([$value],$args),0,$accepted));return $value;}
function maybe_serialize($value) {return is_array($value)||is_object($value)?serialize($value):$value;}
function maybe_unserialize($value) {return is_string($value)&&preg_match('/^[aibsdON]:/',$value)?unserialize($value,['allowed_classes'=>false]):$value;}
function regional_test_update_meta($id,$key,$value) {
  global $meta,$writes;$value=wp_unslash($value);
  $exists=array_key_exists($key,$meta[$id]??[]);$hook=$exists?'update_post_metadata':'add_post_metadata';
  $check=apply_filters($hook,null,$id,$key,$value,false);if($check!==null)return (bool)$check;
  if($exists&&$meta[$id][$key]===$value)return false;
  if($exists)do_action('update_post_meta',1,$id,$key,$value);
  $meta[$id][$key]=$value;$writes[]=[$id,$key];
  do_action($exists?'updated_post_meta':'added_post_meta',1,$id,$key,$value);return true;
}
function add_metadata($type,$id,$key,$value) {return regional_test_update_meta($id,wp_unslash($key),$value);}
function update_metadata($type,$id,$key,$value,$previous='') {return regional_test_update_meta($id,wp_unslash($key),$value);}
function delete_metadata($type,$id,$key,$value='') {
  global $meta;$key=wp_unslash($key);if(apply_filters('delete_post_metadata',null,$id,$key,$value,false)!==null)return false;
  do_action('delete_post_meta',[1],$id);unset($meta[$id][$key]);do_action('deleted_post_meta',[1],$id,$key,$value);return true;
}
function get_metadata($type,$id,$key='',$single=false) {global $meta;if($key!=='')return $single?($meta[$id][$key]??''):[$meta[$id][$key]??''];return array_map(fn($v)=>[maybe_serialize($v)],$meta[$id]??[]);}
function get_metadata_by_mid($type,$mid) {return false;}
function get_post_custom_keys($id) {global $meta;return array_keys($meta[$id]??[]);}
function is_protected_meta($key) {return strpos($key,'_')===0;}
function get_post($id) {global $posts;return $posts[$id]??null;}
function clean_post_cache($id) {}
function wp_get_post_parent_id($id) {return get_post($id)->post_parent??0;}
function get_taxonomy($tax) {return (object)['object_type'=>[FINELEVATE360_CPT_ITEM]];}
function post_type_exists($type) {return true;}
function get_the_terms($id,$tax) {global $terms;return array_map(fn($id)=>(object)['term_id'=>$id],$terms[$id][$tax]??[]);}
function wp_list_pluck($items,$key) {return array_map(fn($i)=>$i->$key,$items);}
function wp_set_object_terms($id,$values,$tax,$append=false) {global $terms;$terms[$id][$tax]=$append?array_values(array_unique(array_merge($terms[$id][$tax]??[],$values))):$values;do_action('set_object_terms',$id,$values,$values,$tax,$append);}
function get_post_taxonomies($id) {return ['finelev360_cat'];}
function update_object_term_cache($ids,$type) {}
function update_option($key,$value) {}
class WP_Error {}
function is_wp_error($value) {return $value instanceof WP_Error;}
function metadata_exists($type,$id,$key) {global $meta;return array_key_exists($key,$meta[$id]??[]);}
function delete_post_meta($id,$key) {return delete_metadata('post',$id,$key);}
function wp_get_post_terms($id,$tax,$args=[]) {global $terms;return $terms[$id][$tax]??[];}
function get_term($id,$tax) {return (object)['term_id'=>$id,'slug'=>[83=>'banks-rs',138=>'banke-srbije',132=>'banks-me'][$id]??'other'];}
function wp_update_post($input,$error=false) {
  global $posts;$id=$input['ID'];
  $data=apply_filters('wp_insert_post_data',array_merge((array)$posts[$id],$input,['post_modified'=>'new time']),$input);
  foreach($data as $key=>$value)$posts[$id]->$key=wp_unslash($value);
  do_action('save_post',$id,$posts[$id]);
  foreach(['rank_math_seo_score','cmplz_hide_cookiebanner','cybocfi_hide_featured_image'] as $key)update_post_meta($id,$key,'late unwanted default');
  do_action('pll_save_post',$id,$posts[$id],pll_get_post_translations($id));return $id;
}
class PLL_Base {public $model;public $options;public $links;public $sync;}
class IsolationPostModel {
  public $allowed=true;
  public function get_translations($id) {return in_array($id,[2487,2290,2305],true)?['me'=>2487,'en'=>2290,'sr'=>2305]:['hr'=>11,'sl'=>12];}
  public function current_user_can_synchronize($id) {return $this->allowed;}
  public function get_language($id) {return array_search($id,$this->get_translations($id),true);}
  public function get_translation($id,$lang) {return $this->get_translations($id)[$lang]??0;}
}
class IsolationTermModel {public function get_translation($id,$lang) {return ['me'=>132,'en'=>133,'sr'=>134][$lang]??0;}}
class IsolationModel {
  public $post;public $term;
  public function __construct() {$this->post=new IsolationPostModel;$this->term=new IsolationTermModel;}
  public function get_translated_taxonomies() {return ['finelev360_cat'];}
  public function is_translated_taxonomy($tax) {return true;}
}
function PLL() {global $pll;return $pll;}
function pll_get_post_translations($id) {global $testFamilyOverride;return $testFamilyOverride??PLL()->model->post->get_translations($id);}
class IsolationDB {public $posts='posts';public function update($table,$values,$where){global $posts;foreach($values as $key=>$value)$posts[$where['ID']]->$key=$value;}}
foreach(['sync-metas','sync-post-metas','sync-term-metas','sync-tax','sync','admin-sync'] as $file)require __DIR__.'/fixtures/polylang-3.9.1/'.$file.'.php';

$original_hooks=$actions;
function setup_isolation_fixture() {
  global $actions,$original_hooks,$pll,$wpdb,$meta,$posts,$terms,$finelevate360_regional_isolation,$origin;
  $actions=$original_hooks;$finelevate360_regional_isolation=[];$origin='https://stg.finelevate360.com/';
  $pll=new PLL_Base;$pll->model=new IsolationModel;$pll->options=['sync'=>['menu_order','post_meta','taxonomies','_thumbnail_id'],'media_support'=>false];
  $pll->links=new class {public function get_data_from_new_post_translation_request(){return [];}};
  $pll->sync=new PLL_Admin_Sync($pll);$wpdb=new IsolationDB;
  $posts=[];$terms=[];$meta=[];
  foreach([2487=>88,2290=>86,2305=>87,11=>1,12=>2] as $id=>$order){$posts[$id]=(object)['ID'=>$id,'post_type'=>FINELEVATE360_CPT_ITEM,'menu_order'=>$order,'post_parent'=>0,'post_title'=>'Fixture '.$id,'post_modified'=>'original time'];$terms[$id]['finelev360_cat']=[$id];$meta[$id]=['_thumbnail_id'=>$id,'cookie'=>'keep-'.$id];}
}
setup_isolation_fixture();
// Reproduce the original direct-SQL order propagation using the real installed callback.
do_action('pll_save_post',2487,$posts[2487],pll_get_post_translations(2487));same($posts[2290]->menu_order,88);same($posts[2305]->menu_order,88);
wp_set_object_terms(2487,[132],'finelev360_cat');same($terms[2290]['finelev360_cat'],[133]);
update_post_meta(2487,'cmplz_hide_cookiebanner','');same(array_key_exists('cmplz_hide_cookiebanner',$meta[2290]),true);

setup_isolation_fixture();
[$baseline,$evidence]=fixture('me',2487);$meta[2487]['_finelevate360_item_attrs']=$baseline;$meta[2487]['_finelevate360_item_research']=$evidence;
$next=$baseline;$next_evidence=$evidence;$attr=finelevate360_research_attr_ids('me')[0];edit_cell($next,$next_evidence,$attr,'yes');
$_POST=request_for(2487,$next,$next_evidence)+['action'=>'editpost','post_ID'=>2487];$_SERVER['REQUEST_METHOD']='POST';
$family_before=[$meta[2290],$meta[2305],$terms[2290],$terms[2305],(array)$posts[2290],(array)$posts[2305]];
$valid_request=$_POST;$early_before=serialize([$meta,$terms,$posts]);
$_POST['finelevate360_regional_expected_hash']=str_repeat('0',64);rejects(fn()=>do_action('admin_init'));same(serialize([$meta,$terms,$posts]),$early_before);
$_POST=$valid_request;unset($_POST['finelevate360_regional_evidence_nonce']);rejects(fn()=>do_action('admin_init'));same(serialize([$meta,$terms,$posts]),$early_before);
$_POST=$valid_request;
do_action('admin_init');
same($finelevate360_regional_isolation['source'],2487);
// Guard is already active before core assigns terms or any save_post callback runs.
wp_set_object_terms(2487,[132],'finelev360_cat');
do_action('save_post',2487,$posts[2487]);
foreach(['cmplz_hide_cookiebanner','cybocfi_hide_featured_image','rank_math_seo_score','rank_math_contentai_score','_edit_last'] as $key)update_post_meta(2487,$key,'new default');
do_action('pll_save_post',2487,$posts[2487],pll_get_post_translations(2487));
// Repeat a late third-party metadata write after pll_save_post.
update_post_meta(2487,'rank_math_seo_score','late SEO value');
same($meta[2487]['_finelevate360_item_attrs'],$next);same($meta[2487]['_finelevate360_item_research'],$next_evidence);
same(array_key_exists('cmplz_hide_cookiebanner',$meta[2487]),false);same(array_key_exists('rank_math_seo_score',$meta[2487]),false);
same([$meta[2290],$meta[2305],$terms[2290],$terms[2305],(array)$posts[2290],(array)$posts[2305]],$family_before);
// No permission-filter override: reverse taxonomy and field paths are harmless too.
$pll->model->post->allowed=false;$source_order=$posts[2487]->menu_order;
same(apply_filters('wp_insert_post_data',['menu_order'=>$source_order],['ID'=>2487]),['menu_order'=>$source_order]);
same(apply_filters('wp_insert_post_parent',0,2487,['ID'=>2487]),0);
$source_terms=$terms[2487];$pll->sync->taxonomies->set_object_terms(2487,[132],[132],'finelev360_cat',false);same($terms[2487],$source_terms);
$pll->model->post->allowed=true;
// Direct copy/add/delete paths and repair-origin requests cannot synchronize either direction.
foreach([[2290,2487,'me'],[2487,2290,'en']] as [$from,$to,$lang]){
  $before=$meta[$to];$pll->sync->post_metas->copy($from,$to,$lang,true);same($meta[$to],$before);
}
// Other families retain their actual installed synchronization behavior in this request.
do_action('pll_save_post',11,$posts[11],pll_get_post_translations(11));same($posts[12]->menu_order,1);
// Context is request-scoped; no option or persistent synchronization setting was edited.
same($pll->options['sync'],['menu_order','post_meta','taxonomies','_thumbnail_id']);
echo "Installed Polylang 3.9.1 callbacks with simulated WordPress storage: $tests assertions passed; live verification still required.\n";

function repair_fixture() {
  global $posts,$terms,$meta;
  setup_isolation_fixture();$_POST=[];
  [$matrix,$evidence]=fixture('me',2487);$meta[2487]['_finelevate360_item_attrs']=$matrix;$meta[2487]['_finelevate360_item_research']=$evidence;
  foreach([2290,2305,2487] as $id){$posts[$id]->menu_order=88;$meta[$id]['cmplz_hide_cookiebanner']='';$meta[$id]['cybocfi_hide_featured_image']='';}
  $meta[2487]['rank_math_seo_score']='6';$meta[2487]['rank_math_contentai_score']=['keywords'=>'74.51','wordCount'=>'0'];$meta[2487]['_edit_last']='1';
  $terms[2290]['finelev360_cat']=[29,83];$terms[2305]['finelev360_cat']=[138];$terms[2487]['finelev360_cat']=[132];
  $rows=[];
  foreach([2290=>86,2305=>87,2487=>88] as $id=>$restore) {
    $keys=['cmplz_hide_cookiebanner','cybocfi_hide_featured_image'];if($id===2487)$keys=array_merge($keys,['rank_math_seo_score','rank_math_contentai_score','_edit_last']);
    $rows[]=['id'=>$id,'title'=>$posts[$id]->post_title,'expected_order'=>88,'restore_order'=>$restore,'expected_terms'=>$terms[$id]['finelev360_cat'],
      'restore_terms'=>[2290=>[83],2305=>[138],2487=>[132]][$id],'protected_hash'=>finelevate360_regional_metadata_hash($id),
      'remove_metadata'=>array_intersect_key($meta[$id],array_flip($keys))];
  }
  return ['records'=>$rows];
}
function repair_state() {global $meta,$terms,$posts;return serialize([$meta,$terms,$posts]);}
function rejected_repair($plan) {
  $before=repair_state();rejects(fn()=>finelevate360_regional_repair(json_encode($plan)));same(repair_state(),$before);
}
$plan=repair_fixture();$testDeniedCaps=['manage_options'];rejected_repair($plan);$testDeniedCaps=[];
$state=repair_state();$testDeniedCaps=['manage_options'];rejects(fn()=>finelevate360_regional_begin_isolation(2487,true));same(repair_state(),$state);$testDeniedCaps=[];
$testFamilyOverride=['me'=>2487,'en'=>2290,'sr'=>2305,'hr'=>11];rejects(fn()=>finelevate360_regional_begin_isolation(2487,true));same(repair_state(),$state);$testFamilyOverride=null;
$plan=repair_fixture();$bad=$plan;$bad['records'][0]['id']=11;rejected_repair($bad);
$bad=$plan;$bad['records'][0]['remove_metadata']['_thumbnail_id']=2290;rejected_repair($bad);
$bad=$plan;$bad['records'][0]['remove_metadata']['cmplz_hide_cookiebanner']='changed';rejected_repair($bad);
$bad=$plan;$bad['records'][0]['protected_hash']=str_repeat('0',64);rejected_repair($bad);
$bad=$plan;$bad['records'][0]['restore_terms']=[29];rejected_repair($bad);
$bad=$plan;unset($meta[2290]['cmplz_hide_cookiebanner']);rejected_repair($bad);
$plan=repair_fixture();$before_meta=$meta;$before_posts=unserialize(serialize($posts));
finelevate360_regional_repair(json_encode($plan));
same($finelevate360_regional_isolation['repair'],true);
foreach($plan['records'] as $row) {
  $id=$row['id'];$expected=$before_meta[$id];foreach($row['remove_metadata'] as $key=>$value)unset($expected[$key]);
  same($meta[$id],$expected);same($terms[$id]['finelev360_cat'],$row['restore_terms']);
  $expected_post=(array)$before_posts[$id];$expected_post['menu_order']=$row['restore_order'];same((array)$posts[$id],$expected_post);
}
same($meta[2487]['_finelevate360_item_attrs'],$before_meta[2487]['_finelevate360_item_attrs']);
same($meta[2487]['_finelevate360_item_research'],$before_meta[2487]['_finelevate360_item_research']);
same($meta[2487]['_thumbnail_id'],$before_meta[2487]['_thumbnail_id']);
rejected_repair($plan); // Captured-value CAS rejects a duplicate operation without repeating writes.
echo "Actual guarded repair mode, exact restoration, absent metadata, stale-plan rejection and protected ratings: $tests assertions passed (simulated WordPress storage).\n";
