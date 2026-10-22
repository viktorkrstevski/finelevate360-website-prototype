<?php
define('ABSPATH', __DIR__);
function add_filter() {}
function fe_language() { return 'mk'; }
function get_theme_mod($name, $default) { return $GLOBALS['event_copy_override'] && $name === 'fe_event_mk_heroLead' ? 'Editorial override' : $default; }
function wp_kses($value, $allowed) { return strip_tags($value, '<strong><em><b><i><br><mark><span><a>'); }
$GLOBALS['event_copy_override'] = false;
require __DIR__ . '/../wordpress/themes/finelevate-advisory/inc/event.php';
$html = '<div class="fl-summit"><div class="site-header">Duplicate header</div><div class="hero-brand">Duplicate logo</div><div class="fl-main"><h1>Summit title</h1><section><div class="pass-grid">Public passes</div></section><section><div class="agenda-head-meta">Public agenda</div></section><div class="pf">Old form shell</div></div></div>';
$result = fe_event_markup($html);
if (strpos($result, 'Duplicate') !== false || strpos($result, 'Old form shell') !== false ||
    strpos($result, 'id="passes"') === false || strpos($result, 'id="agenda"') === false ||
    substr_count($result, '<h1>') !== 1 || strpos($result, 'Public agenda') === false) {
    throw new Exception('Event normalization lost content, anchors or shared-header isolation');
}
echo "Event body preserves public content and anchors without duplicate site chrome.\n";
$strings = fe_event_locales()['strings'];
$localized = fe_event_markup('<div class="fl-summit"><p>' . htmlspecialchars($strings['en']['heroLead']) . '</p></div>');
if (strpos($localized, $strings['mk']['heroLead']) === false) { throw new Exception('Published event translation did not render'); }
$GLOBALS['event_copy_override'] = true;
if (strpos(fe_event_markup('<div class="fl-summit"><p>' . htmlspecialchars($strings['en']['heroLead']) . '</p></div>'), 'Editorial override') === false) { throw new Exception('Event translation is not editable'); }
echo "Published Macedonian event copy and editorial overrides passed.\n";
