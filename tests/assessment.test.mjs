import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const script = fs.readFileSync(new URL('../assets/assessment.js', import.meta.url), 'utf8');
const key = 'fe360_assessment_v2';
const profile = { org: 'bank', size: 'Under 50 people', country: 'North Macedonia', role: 'Board or executive' };

function assessment(saved) {
  const rootEvents = {}, documentEvents = {}, timers = new Map();
  const storage = new Map(saved === undefined ? [] : [[key, typeof saved === 'string' ? saved : JSON.stringify(saved)]]);
  let id = 0;
  const root = { innerHTML: '', querySelector: () => null,
    addEventListener: (name, fn) => { rootEvents[name] = fn; }, scrollIntoView() {} };
  vm.runInNewContext(script, {
    document: { getElementById: () => root, addEventListener: (name, fn) => { documentEvents[name] = fn; } },
    location: { hash: '' },
    localStorage: { getItem: name => storage.get(name) ?? null, setItem: (name, value) => storage.set(name, value) },
    setTimeout: fn => { timers.set(++id, fn); return id; }, clearTimeout: value => timers.delete(value),
    FormData: class { constructor(form) { this.form = form; } get(name) { return this.form.values[name] ?? null; } },
  });
  function click(attribute, value) {
    rootEvents.click({ target: { closest: selector => selector === `[${attribute}]` ? { getAttribute: () => value } : null } });
  }
  return {
    html: () => root.innerHTML, saved: () => JSON.parse(storage.get(key)),
    answer: value => click('data-ans', value), action: value => click('data-act', value),
    tick() { for (const [timer, fn] of [...timers]) { timers.delete(timer); fn(); } },
    key(value, repeat = false) { documentEvents.keydown({ key: value, repeat, target: { matches: () => false } }); },
    submit(kind, values) { rootEvents.submit({ preventDefault() {}, target: { dataset: { form: kind }, values, checkValidity: () => true } }); },
  };
}

const state = (extra = {}) => ({ step: 'q', profile: { ...profile }, answers: {}, i: 0, lead: {}, ...extra });

test('rapid double answers record one answer and advance exactly one question', () => {
  const app = assessment(state());
  app.answer('3'); app.answer('0'); app.tick();
  assert.equal(app.saved().i, 1);
  assert.deepEqual(app.saved().answers, { 0: 3 });
  assert.match(app.html(), /Question 2 of 21/);
});

test('Back cancels a pending answer transition and preserves the intended question', () => {
  const app = assessment(state({ i: 1, answers: { 0: 2 } }));
  app.answer('3'); app.action('back'); app.tick();
  assert.equal(app.saved().i, 0);
  assert.match(app.html(), /Question 1 of 21/);
});

test('holding a numeric key does not answer following questions', () => {
  const app = assessment(state());
  app.key('4'); app.tick(); app.key('4', true); app.tick();
  assert.equal(app.saved().i, 1);
});

for (const [org, count] of [['bank', 21], ['company', 20]]) {
  for (const [value, expected] of [[0, 'Initial'], [3, 'Leading'], ['ns', 'Initial']]) {
    test(`${org}: ${count} answers of ${value} produce the expected maturity`, () => {
      const app = assessment(state({ profile: { ...profile, org } }));
      assert.match(app.html(), new RegExp(`Question 1 of ${count}`));
      for (let i = 0; i < count; i++) { app.answer(String(value)); app.tick(); }
      assert.equal(app.saved().step, 'result');
      assert.match(app.html(), new RegExp(expected));
      assert.equal(Object.keys(app.saved().answers).length, count);
      if (value === 3) {
        assert.match(app.html(), /100%/);
        assert.match(app.html(), /Review the evidence/);
        assert.doesNotMatch(app.html(), /Name one executive owner|Strongest in/);
      }
      if (value === 'ns') assert.match(app.html(), new RegExp(`${count} “Not sure”`));
    });
  }
}

test('an incomplete saved result resumes at the first unanswered question', () => {
  const app = assessment(state({ step: 'result', i: 20, answers: { 0: 3 } }));
  assert.equal(app.saved().step, 'q');
  assert.equal(app.saved().i, 1);
  assert.doesNotMatch(app.html(), /Your result/);
});

test('damaged storage cannot crash the assessment', () => {
  for (const saved of ['{invalid', 'null', '{}', state({ profile: null }), state({ answers: [] })]) {
    assert.match(assessment(saved).html(), /Start the assessment/);
  }
});

test('changing the audience resets answers instead of mixing question sets', () => {
  const app = assessment(state({ answers: { 0: 3 }, i: 10 }));
  app.submit('profile', { ...profile, org: 'company' });
  assert.equal(app.saved().i, 0);
  assert.deepEqual(app.saved().answers, {});
  assert.match(app.html(), /Question 1 of 20/);
});

test('report previews do not claim email delivery or persist personal details', () => {
  const answers = Object.fromEntries(Array.from({ length: 21 }, (_,i) => [i, 3]));
  const app = assessment(state({ step: 'report', answers }));
  app.submit('report', { name: 'Test Person', email: 'test@example.com', company: 'Test Org', c_report: 'on' });
  assert.match(app.html(), /No email has been sent/);
  assert.doesNotMatch(JSON.stringify(app.saved()), /test@example.com|Test Person|Test Org/);
  assert.equal(app.saved().step, 'result');
});
