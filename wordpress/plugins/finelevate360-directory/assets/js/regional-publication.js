/* A per-submit marker for Rank Math's request BEFORE the native research save. */
(function () {
  'use strict';
  const form = document.getElementById('post');
  const optIn = document.getElementById('finelevate360_regional_preserve_scores');
  const hash = document.querySelector('[name="finelevate360_regional_expected_hash"]');
  if (!form || !optIn || !hash || !window.wp || !window.wp.apiFetch) return;
  let pending = null;
  let submitted = false;
  form.addEventListener('submit', function () {
    if (submitted || !optIn.checked) return;
    submitted = true;
    pending = {post_id: Number(optIn.dataset.postId), nonce: optIn.dataset.nonce, expected_hash: hash.value};
    // Rank Math calls apiFetch synchronously in its bubbling submit handler.
    // Do not let a later unrelated request inherit an unused submit marker.
    setTimeout(function () { pending = null; }, 0);
  }, true);
  window.wp.apiFetch.use(function (options, next) {
    const data = options.data;
    if (!pending || options.method !== 'POST' || String(options.path || '').replace(/^\//, '') !== 'rankmath/v1/updateMeta'
      || !data || data.objectType !== 'post' || Number(data.objectID) !== pending.post_id) return next(options);
    const context = pending;
    pending = null; // One REST request, independent of the subsequent native POST.
    return next(Object.assign({}, options, {data: Object.assign({}, data, {finelevate360_publication: context})}));
  });
}());
