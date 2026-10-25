/* Finelevate Awards – Admin JS */
(function ($) {
	'use strict';

	/* ── Pillar counter ─────────────────────────────────────────────── */
	function nextPi() {
		var max = -1;
		$('#fa-pillars-wrap .fa-pillar-block').each(function () {
			var v = parseInt($(this).data('pi'), 10);
			if (!isNaN(v) && v > max) max = v;
		});
		return max + 1;
	}

	function nextAi($pillar) {
		var max = -1;
		$pillar.find('.fa-award-block').each(function () {
			var v = parseInt($(this).data('ai'), 10);
			if (!isNaN(v) && v > max) max = v;
		});
		return max + 1;
	}

	/* ── Add Pillar ─────────────────────────────────────────────────── */
	$(document).on('click', '.fa-add-pillar', function () {
		var pi = nextPi();
		var n  = '_fa_pillars[' + pi + ']';

		var $block = $('<div>', { class: 'fa-pillar-block', 'data-pi': pi });

		var $header = $('<div>', { class: 'fa-block-header' }).append(
			$('<strong>').html(faL10n.labelPillar + ' <span class="fa-pi-num">' + (pi + 1) + '</span>'),
			$('<button>', { type: 'button', class: 'fa-remove-pillar button-link', text: faL10n.labelRemovePillar })
		);

		var $body = $('<div>', { class: 'fa-block-body' }).append(
			field_p(faL10n.labelPillarTitle, n + '[title]', ''),
			field_ta(faL10n.labelPillarDesc,  n + '[desc]',  ''),
			field_p(faL10n.labelBadgeUrl,     n + '[badge_url]', ''),
			$('<div>', { class: 'fa-awards-wrap', 'data-pi': pi }).append(
				$('<p>', { class: 'fa-sublabel', text: faL10n.labelAddAward }),
				$('<button>', { type: 'button', class: 'button fa-add-award', 'data-pi': pi,
				                text: '+ ' + faL10n.labelAddAward })
			)
		);

		$block.append($header, $body);
		$('#fa-pillars-wrap').append($block);
	});

	/* ── Remove Pillar ──────────────────────────────────────────────── */
	$(document).on('click', '.fa-remove-pillar', function () {
		if (!confirm(faL10n.confirmRemovePillar)) return;
		$(this).closest('.fa-pillar-block').remove();
		reindexPillars();
	});

	function reindexPillars() {
		$('#fa-pillars-wrap .fa-pillar-block').each(function (i) {
			$(this).attr('data-pi', i);
			$(this).find('.fa-pi-num').text(i + 1);
			$(this).find('[name]').each(function () {
				$(this).attr('name', $(this).attr('name').replace(/_fa_pillars\[\d+\]/, '_fa_pillars[' + i + ']'));
			});
			$(this).find('.fa-awards-wrap').attr('data-pi', i);
			$(this).find('.fa-add-award').attr('data-pi', i);
		});
	}

	/* ── Add Award ──────────────────────────────────────────────────── */
	$(document).on('click', '.fa-add-award', function () {
		var pi      = $(this).data('pi');
		var $pillar = $(this).closest('.fa-pillar-block');
		var ai      = nextAi($pillar);
		var n       = '_fa_pillars[' + pi + '][awards][' + ai + ']';
		var label   = faL10n.labelAward + ' <span class="fa-ai-num">' + (ai + 1) + '</span>';

		var $award = $('<div>', { class: 'fa-award-block', 'data-ai': ai }).append(
			$('<div>', { class: 'fa-award-header' }).append(
				$('<em>').html(label),
				$('<button>', { type: 'button', class: 'button-link fa-remove-award', text: faL10n.labelRemove })
			),
			field_p(faL10n.labelAwardTitle,  n + '[title]',     ''),
			field_ta(faL10n.labelAwardDesc,   n + '[desc]',      ''),
			field_p(faL10n.labelBadgeUrl,     n + '[badge_url]', ''),
			field_p(faL10n.labelWinner,       n + '[winner]',    '')
		);

		$(this).before($award);
	});

	/* ── Remove Award ───────────────────────────────────────────────── */
	$(document).on('click', '.fa-remove-award', function () {
		$(this).closest('.fa-award-block').remove();
	});

	/* ── Add Step ───────────────────────────────────────────────────── */
	$(document).on('click', '.fa-add-step', function () {
		var idx = $('#fa-steps-wrap .fa-step-row').length;
		$('#fa-steps-wrap').append(
			$('<div>', { class: 'fa-step-row' }).append(
				$('<input>', { type: 'text', name: '_fa_meth_steps[' + idx + ']',
				               class: 'finelevate-awards-input widefat',
				               placeholder: faL10n.labelStepPlaceholder }),
				$('<button>', { type: 'button', class: 'button-link fa-remove-step',
				                style: 'margin-left:6px;', text: '✕' })
			)
		);
	});

	$(document).on('click', '.fa-remove-step', function () {
		$(this).closest('.fa-step-row').remove();
		reindexSteps();
	});

	function reindexSteps() {
		$('#fa-steps-wrap .fa-step-row input').each(function (i) {
			$(this).attr('name', '_fa_meth_steps[' + i + ']');
		});
	}

	/* ── Add Block ──────────────────────────────────────────────────── */
	$(document).on('click', '.fa-add-block', function () {
		var bi = $('#fa-blocks-wrap .fa-meth-block').length;
		var n  = '_fa_meth_blocks[' + bi + ']';
		$('#fa-blocks-wrap').append(
			$('<div>', { class: 'fa-meth-block' }).append(
				field_p(faL10n.labelBlockTitle,  n + '[title]', ''),
				field_ta(faL10n.labelBlockDesc,  n + '[desc]',  ''),
				$('<p>').append(
					$('<button>', { type: 'button', class: 'button-link fa-remove-block',
					                text: faL10n.labelRemoveBlock })
				),
				$('<hr>')
			)
		);
	});

	$(document).on('click', '.fa-remove-block', function () {
		$(this).closest('.fa-meth-block').remove();
	});

	/* ── Media uploader ─────────────────────────────────────────────── */
	$(document).on('click', '.fa-media-btn', function (e) {
		e.preventDefault();
		var targetId = $(this).data('target');
		var $input   = $('#' + targetId);
		var frame = wp.media({ title: faL10n.mediaTitle, button: { text: faL10n.mediaButton }, multiple: false });
		frame.on('select', function () {
			var att = frame.state().get('selection').first().toJSON();
			$input.val(att.url);
			$input.siblings('img').remove();
			$input.after('<br><img src="' + att.url + '" style="max-height:50px;margin-top:6px;">');
		});
		frame.open();
	});

	/* ── DOM helpers ────────────────────────────────────────────────── */
	function field_p(label, name, value) {
		return $('<p>').append(
			$('<label>').append(
				$('<strong>').text(label),
				$('<br>'),
				$('<input>', { type: 'text', name: name, value: value, class: 'finelevate-awards-input widefat' })
			)
		);
	}

	function field_ta(label, name, value) {
		return $('<p>').append(
			$('<label>').append(
				$('<strong>').text(label),
				$('<br>'),
				$('<textarea>', { name: name, rows: 3, class: 'finelevate-awards-input widefat' }).text(value)
			)
		);
	}

}(jQuery));
