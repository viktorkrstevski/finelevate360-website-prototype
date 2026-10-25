/**
 * Finelevate Awards – Admin JS
 *
 * Handles:
 *   - Add / Remove Pillar blocks
 *   - Add / Remove Award blocks within a pillar
 *   - Add / Remove Methodology pipeline steps
 *   - Add / Remove Methodology blocks
 *   - WordPress media uploader for logo fields
 */
(function ($) {
	'use strict';

	/* -----------------------------------------------------------------------
	   Pillar counter (for unique indexes when adding new pillars)
	   ----------------------------------------------------------------------- */
	function nextPillarIndex() {
		var max = -1;
		$('#finelevate-awards-pillars-wrap .finelevate-awards-pillar-block').each(function () {
			var pi = parseInt($(this).data('pi'), 10);
			if (!isNaN(pi) && pi > max) max = pi;
		});
		return max + 1;
	}

	function nextAwardIndex($pillarBlock) {
		var max = -1;
		$pillarBlock.find('.finelevate-awards-award-block').each(function () {
			var ai = parseInt($(this).data('ai'), 10);
			if (!isNaN(ai) && ai > max) max = ai;
		});
		return max + 1;
	}

	/* -----------------------------------------------------------------------
	   Add Pillar
	   ----------------------------------------------------------------------- */
	$(document).on('click', '.finelevate-awards-add-pillar', function () {
		var tpl  = $('#finelevate-awards-pillar-tpl').html();
		var pi   = nextPillarIndex();

		// Replace placeholder index
		tpl = tpl.replace(/\{\{PI\}\}/g, pi);

		var $block = $(tpl);
		$block.attr('data-pi', pi);
		$block.find('.finelevate-awards-pillar-header strong').text(
			$block.find('.finelevate-awards-pillar-header strong').text().replace('#', pi + 1)
		);

		$('#finelevate-awards-pillars-wrap').append($block);
	});

	/* -----------------------------------------------------------------------
	   Remove Pillar
	   ----------------------------------------------------------------------- */
	$(document).on('click', '.finelevate-awards-remove-pillar', function () {
		if (!confirm(faAdmin.confirmRemovePillar)) return;
		$(this).closest('.finelevate-awards-pillar-block').remove();
		reindexPillars();
	});

	function reindexPillars() {
		$('#finelevate-awards-pillars-wrap .finelevate-awards-pillar-block').each(function (i) {
			$(this).attr('data-pi', i);
			$(this).find('.finelevate-awards-pillar-header strong').text('Pillar ' + (i + 1));
			// Re-index all name attributes within this pillar
			$(this).find('[name]').each(function () {
				var name = $(this).attr('name');
				name = name.replace(/_fa_pillars\[\d+\]/, '_fa_pillars[' + i + ']');
				$(this).attr('name', name);
			});
		});
	}

	/* -----------------------------------------------------------------------
	   Add Award
	   ----------------------------------------------------------------------- */
	$(document).on('click', '.finelevate-awards-add-award', function () {
		var $pillar = $(this).closest('.finelevate-awards-pillar-block');
		var pi      = $pillar.data('pi');
		var ai      = nextAwardIndex($pillar);

		var $award = buildAwardBlock(pi, ai);
		$(this).before($award);
	});

	function buildAwardBlock(pi, ai) {
		var n = '_fa_pillars[' + pi + '][awards][' + ai + ']';
		var label = faAdmin.labelAward + ' ' + (ai + 1);

		return $('<div>', { class: 'finelevate-awards-award-block', 'data-ai': ai }).append(
			$('<div>', { class: 'finelevate-awards-award-header' }).append(
				$('<em>').text(label),
				$('<button>', {
					type: 'button',
					class: 'finelevate-awards-remove-award button-link-delete',
					text: faAdmin.labelRemove
				})
			),
			$('<table>', { class: 'finelevate-awards-table finelevate-awards-table--award' }).append(
				$('<tbody>').append(
					textRow(faAdmin.labelAwardTitleEn, n + '[title_en]', ''),
					textRow(faAdmin.labelAwardTitleMk, n + '[title_mk]', ''),
					textareaRow(faAdmin.labelAwardDescEn,  n + '[desc_en]', ''),
					textareaRow(faAdmin.labelAwardDescMk,  n + '[desc_mk]', ''),
					textRow(faAdmin.labelBadgeUrl,         n + '[badge_url]', ''),
					textRow(faAdmin.labelWinner,           n + '[winner]', '')
				)
			)
		);
	}

	/* -----------------------------------------------------------------------
	   Remove Award
	   ----------------------------------------------------------------------- */
	$(document).on('click', '.finelevate-awards-remove-award', function () {
		$(this).closest('.finelevate-awards-award-block').remove();
	});

	/* -----------------------------------------------------------------------
	   Add Methodology Step
	   ----------------------------------------------------------------------- */
	$(document).on('click', '.finelevate-awards-add-step', function () {
		var idx = $('#finelevate-awards-steps-wrap .finelevate-awards-step-row').length;
		var $row = $('<div>', { class: 'finelevate-awards-step-row' }).append(
			$('<input>', {
				type: 'text',
				name: '_fa_method_steps[' + idx + '][en]',
				placeholder: faAdmin.labelStepEn,
				class: 'finelevate-awards-input'
			}),
			$('<input>', {
				type: 'text',
				name: '_fa_method_steps[' + idx + '][mk]',
				placeholder: faAdmin.labelStepMk,
				class: 'finelevate-awards-input'
			}),
			$('<button>', {
				type: 'button',
				class: 'finelevate-awards-remove-step button-link-delete',
				text: '✕'
			})
		);
		$('#finelevate-awards-steps-wrap').append($row);
	});

	/* -----------------------------------------------------------------------
	   Remove Methodology Step
	   ----------------------------------------------------------------------- */
	$(document).on('click', '.finelevate-awards-remove-step', function () {
		$(this).closest('.finelevate-awards-step-row').remove();
		reindexSteps();
	});

	function reindexSteps() {
		$('#finelevate-awards-steps-wrap .finelevate-awards-step-row').each(function (i) {
			$(this).find('input').each(function () {
				var name = $(this).attr('name');
				$(this).attr('name', name.replace(/_fa_method_steps\[\d+\]/, '_fa_method_steps[' + i + ']'));
			});
		});
	}

	/* -----------------------------------------------------------------------
	   Add Methodology Block
	   ----------------------------------------------------------------------- */
	$(document).on('click', '.finelevate-awards-add-block', function () {
		var bi  = $('#finelevate-awards-blocks-wrap .finelevate-awards-method-block').length;
		var n   = '_fa_method_blocks[' + bi + ']';

		var $block = $('<div>', { class: 'finelevate-awards-method-block' }).append(
			$('<table>', { class: 'finelevate-awards-table finelevate-awards-table--inner' }).append(
				$('<tbody>').append(
					textRow(faAdmin.labelBlockTitleEn, n + '[title_en]', '', true),
					textRow(faAdmin.labelBlockTitleMk, n + '[title_mk]', '', true),
					textareaRow(faAdmin.labelBlockDescEn, n + '[desc_en]', ''),
					textareaRow(faAdmin.labelBlockDescMk, n + '[desc_mk]', '')
				)
			),
			$('<button>', {
				type: 'button',
				class: 'finelevate-awards-remove-block button-link-delete',
				text: faAdmin.labelRemoveBlock
			})
		);
		$('#finelevate-awards-blocks-wrap').append($block);
	});

	/* -----------------------------------------------------------------------
	   Remove Methodology Block
	   ----------------------------------------------------------------------- */
	$(document).on('click', '.finelevate-awards-remove-block', function () {
		$(this).closest('.finelevate-awards-method-block').remove();
	});

	/* -----------------------------------------------------------------------
	   Media Uploader
	   ----------------------------------------------------------------------- */
	$(document).on('click', '.finelevate-awards-media-btn', function (e) {
		e.preventDefault();
		var targetId = $(this).data('target');
		var $input   = $('#' + targetId);

		var frame = wp.media({
			title:    faAdmin.mediaTitle,
			button:   { text: faAdmin.mediaButton },
			multiple: false
		});

		frame.on('select', function () {
			var attachment = frame.state().get('selection').first().toJSON();
			$input.val(attachment.url);
			// Update preview
			$input.siblings('img').remove();
			$input.after('<br><img src="' + attachment.url + '" style="max-height:60px;margin-top:6px;">');
		});

		frame.open();
	});

	/* -----------------------------------------------------------------------
	   DOM helpers
	   ----------------------------------------------------------------------- */
	function textRow(label, name, value, wide) {
		var cls = 'finelevate-awards-input' + (wide ? ' finelevate-awards-input--wide' : '');
		return $('<tr>', { class: 'finelevate-awards-row' }).append(
			$('<th>').append($('<label>').text(label)),
			$('<td>').append($('<input>', { type: 'text', name: name, value: value, class: cls }))
		);
	}

	function textareaRow(label, name, value) {
		return $('<tr>', { class: 'finelevate-awards-row' }).append(
			$('<th>').append($('<label>').text(label)),
			$('<td>').append($('<textarea>', {
				name: name,
				rows: 3,
				class: 'finelevate-awards-input finelevate-awards-input--wide',
				text: value
			}))
		);
	}

}(jQuery));
