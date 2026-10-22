<?php
define('ABSPATH', __DIR__);
function add_filter() {}
require __DIR__ . '/../wordpress/themes/finelevate-advisory/inc/event.php';
$html = '<div class="fl-summit"><div class="site-header">Duplicate header</div><div class="hero-brand">Duplicate logo</div><div class="fl-main"><h1>Summit title</h1><section><div class="pass-grid">Public passes</div></section><section><div class="agenda-head-meta">Public agenda</div></section><div class="pf">Old form shell</div></div></div>';
$result = fe_event_markup($html);
if (strpos($result, 'Duplicate') !== false || strpos($result, 'Old form shell') !== false ||
    strpos($result, 'id="passes"') === false || strpos($result, 'id="agenda"') === false ||
    substr_count($result, '<h1>') !== 1 || strpos($result, 'Public agenda') === false) {
    throw new Exception('Event normalization lost content, anchors or shared-header isolation');
}
echo "Event body preserves public content and anchors without duplicate site chrome.\n";
