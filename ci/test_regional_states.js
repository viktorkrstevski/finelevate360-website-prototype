'use strict';
const fs = require('fs');
const vm = require('vm');
const assert = require('assert');
const path = require('path');
const source = fs.readFileSync(path.join(__dirname, '../wordpress/plugins/finelevate360-directory/assets/js/modal.js'), 'utf8');
let drawer, modal, closeButton;
const bodyClasses = new Set();
const classes = new Set();
modal = {
  className: '', setAttribute() {}, removeAttribute() {}, contains() { return true; },
  classList: {add(v) { classes.add(v); }, remove(v) { classes.delete(v); }, contains(v) { return classes.has(v); }},
  querySelector() { return drawer; }
};
drawer = {innerHTML: '', appendChild(b) {closeButton=b;}, insertAdjacentHTML(_,v) {this.innerHTML+=v;}};
const document = {
  getElementById() { return modal; }, addEventListener() {},
  body: {classList: {add(v) {bodyClasses.add(v);}, remove(v) {bodyClasses.delete(v);}}},
  createElement() {return {setAttribute() {}, addEventListener() {}, focus() {}};}
};
const context = {document, window: {}, setTimeout(fn) {fn();}, console};
vm.runInNewContext(source, context);
const states=['yes','partial','no','unknown','not_applicable'];
for (const market of ['sr','me','hr','sl']) {
  const rows=states.map((state,i)=>({id:i+1,title:'A < B & C',mobile:state,desktop:state,biznis:state,privat:state}));
  const card={dataset:{researchMarket:market,title:'Bank',attrs:JSON.stringify(rows)},focus(){}};
  context.window.f360OpenModal(card);
  const html=drawer.innerHTML;
  for (const state of states) assert.equal((html.match(new RegExp('class="f360-state-'+state+'" title=', 'g'))||[]).length,4,market+' '+state);
  assert(html.includes('A &lt; B &amp; C'));
  assert(!html.includes('class="f360-attr-no"'));
  assert(classes.has('f360-modal--research'));
  assert.equal(closeButton.type,'button');
  context.window.f360CloseModal(modal);
  assert(!classes.has('is-open'));
}
context.window.f360OpenModal({dataset:{title:'English',attrs:JSON.stringify([{title:'Feature',mobile:'yes',desktop:'no',biznis:'yes',privat:'no'}])},focus(){}});
assert(drawer.innerHTML.includes('f360-attr-yes'));
assert(drawer.innerHTML.includes('f360-attr-no'));
assert(!drawer.innerHTML.includes('f360-state-unknown'));
console.log('Actual drawer code: all five states distinct in SR/ME/HR/SL; escaping, close and default view passed.');
