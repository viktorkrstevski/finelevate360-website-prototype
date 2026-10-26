<?php
require __DIR__.'/test_regional_states.php';

class RegionalSaveRejected extends RuntimeException {}
function wp_die($message) {throw new RegionalSaveRejected($message);}
function esc_html($text) {return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');}
function wp_unslash($value) {return is_array($value) ? array_map('wp_unslash',$value) : (is_string($value) ? stripslashes($value) : $value);}
function wp_slash($value) {return is_array($value) ? array_map('wp_slash',$value) : (is_string($value) ? addslashes($value) : $value);}

function dispatch_native_save($id) {
  global $actions;
  $callbacks=$actions['save_post'];ksort($callbacks);
  same(array_keys($callbacks),[5,10]);
  foreach($callbacks as $priority=>$list)foreach($list as [$callback,$accepted])call_user_func_array($callback,array_slice([$id,(object)['ID'=>$id,'post_type'=>FINELEVATE360_CPT_ITEM]],0,$accepted));
}
function fixture($market,$id) {
  $states=['yes','partial','no','unknown'];$labels=['yes'=>'Yes','partial'=>'Partial','no'=>'No','unknown'=>'Unverified','not_applicable'=>'Not applicable'];
  $matrix=[999=>['mobile'=>'historical']];
  $research=['schema_version'=>1,'as_of'=>'2026-10-25','bank'=>['bank_key'=>$market.'_fixture','language'=>$market],
    'cells'=>[],'warnings'=>[],'sources'=>[['source_id'=>'official','url'=>'https://bank.example/feature','title'=>'Official feature']],
    'publication_binding'=>['bank_post_id'=>$id,'language'=>$market]];
  foreach(finelevate360_research_attr_ids($market) as $index=>$attr)foreach(['mobile','desktop','biznis','privat'] as $n=>$axis) {
    $state=$states[($index+$n)%4];
    if($index===0 && $axis==='mobile')$state='unknown';
    if($index===11 && in_array($axis,['desktop','privat'],true))$state='not_applicable';
    $definitive=in_array($state,['yes','partial','no'],true);
    $matrix[$attr][$axis]=$state;
    $note=$state==='not_applicable' ? 'Phone/tablet SoftPOS does not apply to browser channels under the agreed rule.' : ($state==='unknown' ? 'No qualifying public evidence found; availability remains unverified.' : "Official bank's documented capability.");
    $research['cells'][$attr][$axis]=[
      'source_ids'=>$definitive?['official']:[],'source_urls'=>$definitive?['https://bank.example/feature']:[],
      'evidence_note'=>$note,'publication_status'=>$state,'caveat'=>'Eligibility restrictions retained.',
      'original_record'=>['status'=>$state==='not_applicable'?null:$labels[$state],'applicable'=>$state!=='not_applicable','feature_index'=>$index+1,'axis_key'=>$axis,'evidence_summary'=>$note],
    ];
  }
  return [$matrix,$research];
}
function edit_cell(&$matrix,&$research,$attr,$state) {
  $matrix[$attr]['mobile']=$state;
  $research['cells'][$attr]['mobile']['publication_status']=$state;
  $research['cells'][$attr]['mobile']['source_ids']=['official'];
  $research['cells'][$attr]['mobile']['source_urls']=['https://bank.example/feature'];
  $research['cells'][$attr]['mobile']['evidence_note']="Official bank's new evidence.";
}
function request_for($id,$matrix,$research) {
  return ['finelevate360_meta_nonce'=>'valid','finelevate360_regional_evidence_nonce'=>'valid',
    'finelevate360_regional_expected_hash'=>finelevate360_regional_metadata_hash($id),
    'finelevate360_item_attr_cols'=>$matrix,'finelevate360_regional_evidence_json'=>addslashes(json_encode($research))];
}
function expect_rejected_save($id,$request,$message) {
  global $meta,$writes;
  $before=$meta;$writes=[];$_POST=$request;
  try {dispatch_native_save($id);throw new RuntimeException('Expected guard to reject '.$message);}
  catch(RegionalSaveRejected $error) {same(strpos($error->getMessage(),$message)!==false,true);}
  same($meta,$before);same($writes,[]);
}

$origin='https://stg.finelevate360.com/me/';
foreach(['sr'=>2299,'me'=>2487] as $market=>$id) {
  [$baseline,$evidence]=fixture($market,$id);
  $meta[$id]=['_finelevate360_item_attrs'=>$baseline,'_finelevate360_item_research'=>$evidence,'_unrelated'=>'preserved'];
  foreach(finelevate360_research_attr_ids($market) as $attr)$meta[$id]['_finelevate360_attr_'.$attr]='existing aggregate preserved on no-op';
  $attr=finelevate360_research_attr_ids($market)[0];
  $next=$baseline;$next_evidence=$evidence;edit_cell($next,$next_evidence,$attr,'yes');
  $valid=request_for($id,$next,$next_evidence);
  $bad=$valid;$bad['finelevate360_regional_evidence_json']='';expect_rejected_save($id,$bad,'Matching reviewed evidence');
  $bad=$valid;unset($bad['finelevate360_regional_evidence_json']);expect_rejected_save($id,$bad,'Matching reviewed evidence');
  $bad=$valid;unset($bad['finelevate360_regional_evidence_nonce']);expect_rejected_save($id,$bad,'editor nonce');
  $bad=$valid;$bad['finelevate360_regional_evidence_nonce']='invalid';expect_rejected_save($id,$bad,'editor nonce');
  $bad=$valid;$bad['finelevate360_regional_expected_hash']=str_repeat('0',64);expect_rejected_save($id,$bad,'changed since');
  $bad=$valid;$bad_evidence=$next_evidence;$bad_evidence['cells'][$attr]['mobile']['publication_status']='partial';$bad['finelevate360_regional_evidence_json']=addslashes(json_encode($bad_evidence));expect_rejected_save($id,$bad,'does not match');
  foreach(['yes','partial','no'] as $state) {
    $matrix=$baseline;$source_free=$evidence;edit_cell($matrix,$source_free,$attr,$state);
    $source_free['cells'][$attr]['mobile']['source_ids']=[];$source_free['cells'][$attr]['mobile']['source_urls']=[];
    expect_rejected_save($id,request_for($id,$matrix,$source_free),'resolved citation');
  }
  $bad=$valid;$bad_evidence=$next_evidence;$bad_evidence['cells'][$attr]['mobile']['source_ids']=['missing'];$bad['finelevate360_regional_evidence_json']=addslashes(json_encode($bad_evidence));expect_rejected_save($id,$bad,'Unresolved per-cell source');
  $bad=$valid;$bad_evidence=$next_evidence;$pos=finelevate360_research_attr_ids($market)[11];$bad_evidence['cells'][$pos]['desktop']['original_record']['applicable']=true;$bad['finelevate360_regional_evidence_json']=addslashes(json_encode($bad_evidence));expect_rejected_save($id,$bad,'Digital POS browser rule');
  $bad=$valid;$bad_evidence=$next_evidence;$unknown_attr=finelevate360_research_attr_ids($market)[3];$bad_evidence['cells'][$unknown_attr]['mobile']['original_record']['evidence_summary']='';$bad['finelevate360_regional_evidence_json']=addslashes(json_encode($bad_evidence));expect_rejected_save($id,$bad,'research-gap note');
  $bad=$valid;unset($bad['finelevate360_item_attr_cols'][$attr]['desktop']);expect_rejected_save($id,$bad,'All 104');
  // A category-only request has no native matrix nonce and writes no metadata.
  $before=$meta;$writes=[];$_POST=['tax_input'=>['finelev360_cat'=>[132]]];dispatch_native_save($id);same($meta,$before);same($writes,[]);
  // A genuine no-op needs no replacement evidence or regional nonce, and must not rewrite aggregates.
  $writes=[];$_POST=['finelevate360_meta_nonce'=>'valid','finelevate360_item_attr_cols'=>$baseline];dispatch_native_save($id);same($meta,$before);same($writes,[]);
  $writes=[];$_POST=['finelevate360_meta_nonce'=>'valid','finelevate360_item_attr_cols'=>$baseline,'finelevate360_regional_evidence_json'=>'   '];dispatch_native_save($id);same($meta,$before);same($writes,[]);
  // Full valid updates execute the real priority-5 guard followed by the priority-10 writer.
  $writes=[];$_POST=$valid;dispatch_native_save($id);
  same($meta[$id]['_finelevate360_item_attrs'],$next);
  // WordPress unslashes metadata before storage; the stub records the transport value.
  same(wp_unslash($meta[$id]['_finelevate360_item_research']),$next_evidence);
  same($meta[$id]['_unrelated'],'preserved');
  same($writes[0],[$id,'_finelevate360_item_research']);
  same(count($writes),28);
}
// Existing HR/SL native saves continue without the new regional evidence requirements.
foreach(['hr'=>11,'sl'=>12] as $market=>$id) {
  $attr=finelevate360_research_attr_ids($market)[0];$meta[$id]['_finelevate360_item_attrs']=[];
  $_POST=['finelevate360_meta_nonce'=>'valid','finelevate360_item_attr_cols'=>[$attr=>array_fill_keys(['mobile','desktop','biznis','privat'],'partial')]];
  dispatch_native_save($id);same($meta[$id]['_finelevate360_item_attrs'][$attr]['mobile'],'partial');
}
$id=500;$meta[$id]['_finelevate360_item_attrs']=[];
$_POST=['finelevate360_meta_nonce'=>'valid','finelevate360_item_attr_ids'=>[600],
  'finelevate360_item_attr_cols'=>[600=>['mobile'=>'yes','desktop'=>'partial','biznis'=>'no','privat'=>'no']]];
dispatch_native_save($id);same($meta[$id]['_finelevate360_item_attrs'][600],['mobile'=>'yes','desktop'=>'no','biznis'=>'no','privat'=>'no']);
echo "Actual priority-5/priority-10 save dispatch, fail-closed evidence, citation rules and category/no-op preservation: $tests assertions passed.\n";
