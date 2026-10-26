<?php
// Actual installed Polylang callbacks; WordPress storage/image rendering are simulated.
require __DIR__.'/test_regional_sync_isolation.php';

function wp_attachment_is_image($id) {global $logoRenderable;return $logoRenderable && get_post($id) && get_post($id)->post_type==='attachment';}
function wp_get_attachment_image($id,$size) {return wp_attachment_is_image($id)?'<img src="fixture-bank-logo.png" alt="Bank">':'';}
function set_post_thumbnail($id,$attachment) {
  // Match core's write/deletion branch; the production guard must forbid the latter.
  if (wp_get_attachment_image($attachment,'thumbnail')) return update_post_meta($id,'_thumbnail_id',(int)$attachment);
  return delete_post_meta($id,'_thumbnail_id');
}
function logo_fixture($id=2489) {
  global $meta,$posts,$terms,$writes,$testFamilyOverride,$testDeniedCaps,$testExpectedNonceAction,$logoRenderable;
  $testFamilyOverride=null;setup_isolation_fixture();
  $testFamilyOverride=['me'=>$id,'en'=>3200,'sr'=>3201];$testDeniedCaps=[];$logoRenderable=true;
  $testExpectedNonceAction='finelevate360_regional_logo_'.$id;
  [$attachment,$title,$file]=finelevate360_regional_logo_bindings()[$id];
  foreach([$id,3200,3201] as $n) {
    $posts[$n]=(object)['ID'=>$n,'post_type'=>FINELEVATE360_CPT_ITEM,'post_title'=>$n===$id?$title:'Linked bank','menu_order'=>$n,'post_parent'=>0,'post_content'=>'Original content','post_modified'=>'Original date'];
    $terms[$n]['finelev360_cat']=[$n===$id?132:83];
    $meta[$n]=['rank_math_seo_score'=>'71','cookie'=>'original','_unrelated'=>'original'];
    [$matrix,$research]=fixture($n===$id?'me':'sr',$n);
    $meta[$n]['_finelevate360_item_attrs']=$matrix;$meta[$n]['_finelevate360_item_research']=$research;
    if($n!==$id)$meta[$n]['_thumbnail_id']=$n;
  }
  if($attachment){$posts[$attachment]=(object)['ID'=>$attachment,'post_type'=>'attachment'];$meta[$attachment]=['_wp_attached_file'=>'2026/09/'.$file];}
  $writes=[];$_POST=[];$_SERVER['REQUEST_METHOD']='POST';
  return ['finelevate360_logo_bank'=>(string)$id,'finelevate360_logo_attachment'=>(string)$attachment,
    'finelevate360_logo_nonce'=>'valid','finelevate360_logo_state'=>finelevate360_regional_logo_family_hash($id)];
}
function logo_state() {global $meta,$posts,$terms;return serialize([$meta,$posts,$terms]);}
function logo_rejected($request) {
  global $writes;$before=logo_state();$writes=[];$rejected=false;
  try{finelevate360_regional_assign_logo($request);}catch(RuntimeException $e){$rejected=true;}
  same($rejected,true);same(logo_state(),$before);same($writes,[]);
}

foreach(finelevate360_regional_logo_bindings() as $id=>[$attachment]) {
  $request=logo_fixture($id);
  same($attachment>0,true);
  $before_meta=$meta;$before_posts=serialize($posts);$before_terms=$terms;
  same(finelevate360_regional_assign_logo($request),$attachment);
  $expected=$before_meta;$expected[$id]['_thumbnail_id']=$attachment;
  same($meta,$expected);same(serialize($posts),$before_posts);same($terms,$before_terms);
  same($writes,[[$id,'_thumbnail_id']]);
  // Wrong value, wrong source, deletion and late unrelated writes cannot pass the four-argument filter.
  foreach([[$id,'_thumbnail_id',$attachment+1],[3200,'_thumbnail_id',$attachment],[$id,'rank_math_seo_score','changed'],[$id,'_finelevate360_item_attrs',[]]] as [$target,$key,$value])update_post_meta($target,$key,$value);
  delete_post_meta($id,'_thumbnail_id');
  do_action('pll_save_post',$id,$posts[$id],pll_get_post_translations($id));
  $pll->sync->post_metas->copy($id,3200,'en',true);
  same($meta,$expected);same(serialize($posts),$before_posts);same($terms,$before_terms);
  same(update_post_meta($id,'_thumbnail_id',$attachment),false);same($meta,$expected);
  // A repeat with the old absent-state hash is rejected without any second write.
  logo_rejected($request);
}

$request=logo_fixture();$request['finelevate360_logo_bank']='2299';logo_rejected($request);
$request=logo_fixture();$request['finelevate360_logo_attachment']='2983';logo_rejected($request);
$request=logo_fixture();$request['finelevate360_logo_attachment']='2982junk';logo_rejected($request);
$request=logo_fixture();$request['finelevate360_logo_nonce']='invalid';logo_rejected($request);
$request=logo_fixture();unset($request['finelevate360_logo_nonce']);logo_rejected($request);
$request=logo_fixture();$testExpectedNonceAction='finelevate360_regional_logo_2490';logo_rejected($request);
foreach(['manage_options','edit_post'] as $cap){$request=logo_fixture();$testDeniedCaps=[$cap];logo_rejected($request);}
foreach(['https://finelevate360.com/','https://unknown.example/','http://stg.finelevate360.com/','https://stg.finelevate360.com:8443/'] as $url){$request=logo_fixture();$origin=$url;logo_rejected($request);}
foreach(['GET','PUT'] as $method){$request=logo_fixture();$_SERVER['REQUEST_METHOD']=$method;logo_rejected($request);}
$request=logo_fixture();$request['finelevate360_logo_state']=str_repeat('0',64);logo_rejected($request);
foreach(['',0,2982] as $existing){$request=logo_fixture();$meta[2489]['_thumbnail_id']=$existing;logo_rejected($request);}
$request=logo_fixture();$meta[3200]['cookie']='concurrent linked edit';logo_rejected($request);
$request=logo_fixture();$meta[2489]['_finelevate360_item_research']['warnings'][]='concurrent research';logo_rejected($request);
$request=logo_fixture();$posts[2489]->post_title='Different identity';logo_rejected($request);
$request=logo_fixture();$logoRenderable=false;logo_rejected($request);
$request=logo_fixture();$posts[2982]->post_type='post';logo_rejected($request);
$request=logo_fixture();$meta[2982]['_wp_attached_file']='2026/09/different-bank.png';logo_rejected($request);
unset($testExpectedNonceAction);$testFamilyOverride=null;
echo "Exact staging logo mappings, nonce/admin/prior-state checks, four-argument metadata guard and installed Polylang isolation: $tests assertions passed; storage/image rendering simulated.\n";
