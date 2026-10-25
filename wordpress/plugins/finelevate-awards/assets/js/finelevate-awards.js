/**
 * Finelevate Awards – Frontend JS
 * Minimal progressive enhancement only.
 */
(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		// Animate pipeline steps into view
		var steps = document.querySelectorAll('.finelevate-awards-methodology__step');
		if (!steps.length || !('IntersectionObserver' in window)) return;

		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (entry.isIntersecting) {
					entry.target.classList.add('fa-visible');
					io.unobserve(entry.target);
				}
			});
		}, { threshold: 0.2 });

		steps.forEach(function (el, i) {
			el.style.transitionDelay = (i * 80) + 'ms';
			io.observe(el);
		});
	});
}());
