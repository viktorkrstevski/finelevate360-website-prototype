<?php
require __DIR__ . '/../wordpress/migration/directory-data.php';
use function Finelevate\Migration\{require_staging_target, validate_id_map, decode_array, remap_attribute_rows, remap_rating_meta, remap_research, remap_research_rules};
$tests = 0;
function same($actual, $expected) {
    global $tests; $tests++;
    if ($actual !== $expected) throw new RuntimeException('Assertion ' . $tests . ' failed: ' . var_export($actual, true));
}
function rejects($callback) {
    global $tests; $tests++;
    try { $callback(); } catch (Throwable $error) { return; }
    throw new RuntimeException('Expected rejection at assertion ' . $tests);
}
require_staging_target('https://stg.finelevate360.com/');
foreach (['https://finelevate360.com', 'http://stg.finelevate360.com', 'https://stg.finelevate360.com.evil.test', 'https://stg.finelevate360.com/?x=1'] as $url) rejects(fn() => require_staging_target($url));
rejects(fn() => validate_id_map([54=>9001,603=>9001]));
rejects(fn() => validate_id_map(['054'=>9001]));
rejects(fn() => validate_id_map([54=>'9001junk']));
$map = [54=>9001,603=>9002];
$rows = [54=>['mobile'=>'yes','desktop'=>'partial','biznis'=>'unknown','privat'=>'not_applicable'],603=>'no'];
$mapped = [9001=>$rows[54],9002=>'no'];
same(remap_attribute_rows($rows,$map),$mapped);
$meta = ['_finelevate360_item_attrs'=>serialize($rows),'_finelevate360_attr_54'=>'yes','_finelevate360_attr_603'=>'no'];
$result = remap_rating_meta($meta,$map);
same(unserialize($result['_finelevate360_item_attrs']),$mapped);
same($result['_finelevate360_attr_9001'],'yes');
same($result['_finelevate360_attr_9002'],'no');
same(count($result),3);
same(remap_rating_meta(['_finelevate360_item_attrs'=>$rows],$map)['_finelevate360_item_attrs'],$mapped);
rejects(fn() => remap_attribute_rows([999=>'yes'],$map));
rejects(fn() => remap_rating_meta(['session_tokens'=>[]],$map));
rejects(fn() => decode_array('a:1:{i:54;s:3:"yes";}junk'));
rejects(fn() => decode_array('a:1:{i:54;O:8:"stdClass":0:{}}'));
rejects(fn() => decode_array('a:1:{i:54;s:99:"yes";}'));
$unicode = [54=>['description'=>'Потврдено – Na voljo','source'=>'https://bank.example/research?x=54']];
same(decode_array(serialize($unicode)),$unicode);
$research = ['market'=>'sl','cells'=>[54=>['mobile'=>['status'=>'partial','note'=>'Na voljo','url'=>'https://bank.example/54']],603=>[]]];
$expected = $research; $expected['cells'] = [9001=>$research['cells'][54],9002=>[]];
same(remap_research($research,$map),$expected);
same(json_decode(remap_research(json_encode($research),$map),true),$expected);
$json = json_encode($research);
$envelope = ['encoding'=>'gzip-base64-json-v1','data'=>base64_encode(gzencode($json)),'uncompressed_bytes'=>strlen($json),'sha256'=>hash('sha256',$json)];
$mapped_envelope = remap_research($envelope,$map);
$decoded = gzdecode(base64_decode($mapped_envelope['data']));
same(json_decode($decoded,true),$expected);
same($mapped_envelope['uncompressed_bytes'],strlen($decoded));
same($mapped_envelope['sha256'],hash('sha256',$decoded));
same(json_decode(remap_research(json_encode($envelope),$map),true),$mapped_envelope);
$bad = $envelope; $bad['sha256'] = str_repeat('0',64);
rejects(fn() => remap_research($bad,$map));
rejects(fn() => remap_research(['cells'=>[999=>[]]],$map));
rejects(fn() => remap_research(['encoding'=>'other'],$map));
rejects(fn() => remap_research('bad json',$map));
// Synthetic IDs prove code-level research gates use destination IDs as well.
$all = []; foreach (range(1,2200) as $id) $all[$id] = $id + 10000;
$rules = remap_research_rules($all);
same($rules['pages'],['hr'=>11042,'sl'=>11974]);
same($rules['aliases'][10762],10054);
same($rules['items']['hr'][0],11007);
same($rules['attributes']['sl'][0],11925);
same(count($rules['items']['sl']),12);
same(count($rules['attributes']['hr']),27);
rejects(fn() => remap_research_rules($map));
echo "Directory data transformations: $tests assertions passed.\n";
