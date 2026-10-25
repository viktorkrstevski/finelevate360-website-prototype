/* Exercise animation time, loop continuity and user interruption without a DOM dependency. */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync(`${__dirname}/../wordpress/themes/finelevate-advisory/assets/site.js`, 'utf8');
const listeners = () => ({
  events: {},
  addEventListener(name, handler) { (this.events[name] ||= []).push(handler); },
  emit(name, event = {}) { (this.events[name] || []).forEach(handler => handler(event)); }
});
function setup(reduce = false) {
  let time = 0, callbackId = 0, scroll = 0, queuedScroll = false, focused = false;
  const frames = new Map();
  const media = Object.assign(listeners(), { matches: reduce });
  const win = Object.assign(listeners(), { matchMedia: () => media });
  const original = {
    width: 2108,
    getBoundingClientRect() { return { width: this.width }; },
    cloneNode() {
      const copy = { images: [{ alt: 'Tessa' }, { alt: 'Inbox' }], attrs: {},
        setAttribute(key, value) { this.attrs[key] = value; },
        querySelectorAll() { return this.images; },
        remove() { track.copies.splice(track.copies.indexOf(this), 1); }
      };
      return copy;
    }
  };
  const banner = Object.assign(listeners(), { contains: () => focused });
  const track = { copies: [], querySelector: () => original, append: copy => track.copies.push(copy) };
  const viewport = Object.assign(listeners(), { closest: () => banner, querySelector: () => track });
  Object.defineProperty(viewport, 'scrollLeft', {
    get: () => scroll,
    set(value) { const next = Math.round(value); if (next !== scroll) queuedScroll = true; scroll = next; }
  });
  const help = { dataset: { enabled: 'Motion enabled', paused: 'Motion paused', reduced: 'Reduced motion' }, textContent: '' };
  viewport.attrs = {};
  viewport.setAttribute = (key, value) => { viewport.attrs[key] = value; };
  viewport.removeAttribute = key => { delete viewport.attrs[key]; };
  const doc = { hidden: false, activeElement: null, getElementById: id => id === 'partner-motion-help' ? help : null,
    querySelectorAll: () => [], querySelector: () => viewport };
  let resize;
  vm.runInNewContext(source, {
    document: doc, window: win, performance: { now: () => time },
    requestAnimationFrame(fn) { frames.set(++callbackId, fn); return callbackId; },
    cancelAnimationFrame(id) { frames.delete(id); },
    ResizeObserver: function (fn) { resize = fn; this.observe = () => {}; }
  });
  const flushScroll = () => { if (queuedScroll) { queuedScroll = false; viewport.emit('scroll'); } };
  const frame = (advance = 16) => {
    time += advance;
    const pending = [...frames.values()]; frames.clear();
    pending.forEach(fn => fn(time)); flushScroll();
  };
  return { frame, frames, banner, viewport, win, media, track, original, doc, help,
    space(repeat = false) { const event = { key: ' ', repeat, preventDefault() { this.prevented = true; } }; viewport.emit('keydown', event); return event; },
    focus(value) { focused = value; }, resize: () => resize(),
    manual(value) { viewport.scrollLeft = value; flushScroll(); },
    reduce(value) { media.matches = value; media.emit('change'); flushScroll(); }
  };
}
const app = setup();
assert.equal(app.track.copies.length, 1);
assert.equal(app.track.copies[0].attrs['aria-hidden'], 'true');
assert.equal(app.track.copies[0].inert, true);
assert(app.track.copies[0].images.every(img => img.alt === ''));
for (let i = 0; i < 126; i++) app.frame();
assert(app.viewport.scrollLeft >= 47 && app.viewport.scrollLeft <= 49, '24px/s survives integer scrollLeft rounding');
const hold = action => {
  const before = app.viewport.scrollLeft; action();
  for (let i = 0; i < 60; i++) app.frame();
  assert.equal(app.viewport.scrollLeft, before, 'interaction pauses animation');
};
hold(() => app.banner.emit('pointerenter', { pointerType: 'mouse' }));
app.banner.emit('pointerleave'); app.frame(); app.frame(64);
assert(app.viewport.scrollLeft > 48);
hold(() => app.focus(true));
assert.equal(app.space().prevented, true);
assert.equal(app.help.textContent, 'Motion paused');
app.focus(false);
const persistent = app.viewport.scrollLeft;
app.frame(20000); assert.equal(app.viewport.scrollLeft, persistent, 'Space pause survives leaving focus for 20 seconds');
app.space(true); app.frame(); assert.equal(app.help.textContent, 'Motion paused', 'key repeat must not toggle');
app.focus(true); app.space(); assert.equal(app.help.textContent, 'Motion enabled');
app.frame(); assert.equal(app.viewport.scrollLeft, persistent, 'resuming while focused still waits for focus to leave');
app.focus(false); app.frame(); app.frame(64); assert(app.viewport.scrollLeft > persistent);
hold(() => { app.viewport.emit('pointerdown', { pointerType: 'touch' }); });
app.manual(350); app.frame(2000); assert.equal(app.viewport.scrollLeft, 350);
app.win.emit('pointerup'); app.frame(30000);
assert.equal(app.viewport.scrollLeft, 350, 'touch release remains paused after 30 seconds');
assert.equal(app.help.textContent, 'Motion paused');
app.space(); app.frame(); app.frame(64); assert(app.viewport.scrollLeft > 350);
app.viewport.emit('wheel'); app.manual(500); app.frame(5500);
app.manual(540); app.frame(30000);
assert.equal(app.viewport.scrollLeft, 540, 'manual scrolling stays paused without a restart timer');
app.manual(app.original.width - 2); app.space(); app.frame();
for (let i = 0; i < 10; i++) app.frame();
assert(app.viewport.scrollLeft >= 0 && app.viewport.scrollLeft < 5, 'loop returns to identical first logos without extra travel');
app.original.width = 2754; app.resize(); app.frame(); app.frame(64);
assert.equal(app.track.copies.length, 1, 'resize never duplicates the visual copy');
hold(() => { app.doc.hidden = true; }); app.doc.hidden = false;
app.reduce(true);
assert.equal(app.track.copies.length, 0);
assert.equal(app.frames.size, 0);
assert.equal(app.help.textContent, 'Reduced motion');
assert.equal(app.viewport.attrs['aria-keyshortcuts'], undefined);
app.space(); assert.equal(app.frames.size, 0, 'Space cannot override reduced motion');
assert.equal(app.viewport.scrollLeft, 0);
app.manual(200); app.frame(10000); assert.equal(app.viewport.scrollLeft, 200, 'reduced motion stays manually scrollable');
app.reduce(false); app.reduce(false);
assert.equal(app.track.copies.length, 1, 'preference changes never accumulate copies');
const reduced = setup(true);
assert.equal(reduced.frames.size, 0);
assert.equal(reduced.track.copies.length, 0);
console.log('Sponsor speed/rounding, seamless loop, persistent Space/touch/manual pauses, repeat suppression, accurate status, resize, hidden page and reduced-motion tests passed.');
