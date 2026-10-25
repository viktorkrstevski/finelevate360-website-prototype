<?php
define('ABSPATH','/');
require __DIR__.'/../wordpress/plugins/finelevate-staging-migration/importer.php';
$checks=0;
function same($expected,$actual){global $checks;$checks++;if($actual!==$expected)throw new RuntimeException('Importer assertion '.$checks.' failed.');}
$state=['post_map'=>[10=>110,20=>120],'attribute_map'=>[30=>130],'term_map'=>[40=>140]];
$data=['meta'=>[['post_id'=>10,'meta_key'=>'_menu_item_type','meta_value'=>'custom']]];
same(['_finelevate360_attr_130','yes'],fe_migration_native_meta(10,'_finelevate360_attr_30','yes',$state,$data));
same(['_fe_unresolved_source_attr_99','yes'],fe_migration_native_meta(10,'_finelevate360_attr_99','yes',$state,$data));
same(['_finelevate360_item_attrs',[130=>['mobile'=>'yes','desktop'=>'no','biznis'=>'partial','privat'=>'yes']]],fe_migration_native_meta(10,'_finelevate360_item_attrs',[30=>['mobile'=>'yes','desktop'=>'no','biznis'=>'partial','privat'=>'yes'],99=>['mobile'=>'yes']],$state,$data));
same(['_thumbnail_id',120],fe_migration_native_meta(10,'_thumbnail_id','20',$state,$data));
same(['finelevate360_bank_order','120,110'],fe_migration_native_meta(10,'finelevate360_bank_order','20,10',$state,$data));
same(['_menu_item_object_id',0],fe_migration_native_meta(10,'_menu_item_object_id','0',$state,$data));
same(['_fe_migrated_source_template','page-item.php'],fe_migration_native_meta(10,'_wp_page_template','page-item.php',$state,$data));
same(['url'=>'https://stg.finelevate360.com/mk/','outside'=>'https://bank.example/'],fe_migration_rewrite(['url'=>'https://www.finelevate360.com/mk/','outside'=>'https://bank.example/']));
same(false,fe_migration_meta_allowed('_mail'));
same(false,fe_migration_meta_allowed('session_tokens'));
same(true,fe_migration_meta_allowed('_event_agenda'));
same(true,fe_migration_meta_allowed('_pll_menu_item'));
try{fe_migration_native_meta(10,'_thumbnail_id',99,$state,$data);throw new RuntimeException('Missing reference accepted.');}catch(RuntimeException $e){same('Unmapped referenced record: 99',$e->getMessage());}
echo $checks." importer checks passed.\n";
