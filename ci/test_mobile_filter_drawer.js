/* Run in a live map page with the narrow drawer open. Read-only layout assertions. */
function assertMobileFilterDrawer() {
  const panel = document.querySelector('.finelevate-filters');
  const failures = [];
  const require = (test, message) => { if (!test) failures.push(message); };
  require(!!panel, 'Filter drawer is missing');
  if (!panel) return {passed:false, failures};
  const box = panel.getBoundingClientRect();
  require(innerWidth <= 768, 'Use a narrow mobile viewport');
  require(panel.classList.contains('active'), 'Open the drawer before testing');
  require(box.left >= -1 && box.right <= innerWidth + 1, 'Drawer overflows viewport');
  require(box.height <= innerHeight + 1, 'Drawer is taller than viewport');
  require(['auto','scroll'].includes(getComputedStyle(panel).overflowY), 'Capability list cannot scroll');
  require(panel.getAttribute('role') === 'dialog' && panel.getAttribute('aria-modal') === 'true', 'Open drawer lacks dialog semantics');
  require(!panel.inert, 'Open drawer is inert');
  const options = [...panel.querySelectorAll('.checkbox-custom')];
  require(options.length > 5, 'Functionality options are missing');
  for (const row of options) {
    const input = row.querySelector('input');
    const indicator = row.querySelector('.check-indicator');
    const label = row.querySelector('.f360-filter-label-text');
    const count = row.querySelector('.f360-filter-count');
    const r = row.getBoundingClientRect(), i = input.getBoundingClientRect(), l = label.getBoundingClientRect(), c = count.getBoundingClientRect();
    require(getComputedStyle(indicator).display === 'none', 'Duplicate decorative control remains visible');
    require(r.height <= 76, 'Option is excessively tall: '+label.textContent);
    require(l.width >= 90 && l.left > i.right, 'Option label is squeezed or overlaps control: '+label.textContent);
    require(c.left >= l.right - 1 && Math.abs((c.top+c.height/2)-(i.top+i.height/2)) <= 2, 'Count is wrapped onto another row: '+label.textContent);
    require(i.width >= 16 && i.height >= 16 && getComputedStyle(input).visibility !== 'hidden', 'Native input is hidden or too small');
  }
  return {passed:failures.length===0, failures, viewport:{width:innerWidth,height:innerHeight}, optionCount:options.length, platformCount:panel.querySelectorAll('input[type="radio"]').length, capabilityCount:panel.querySelectorAll('input[type="checkbox"]').length, panel:{height:box.height,scrollHeight:panel.scrollHeight,clientHeight:panel.clientHeight}};
}
