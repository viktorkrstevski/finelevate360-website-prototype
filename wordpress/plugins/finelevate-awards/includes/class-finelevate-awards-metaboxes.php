<?php
/**
 * Finelevate_Awards_Metaboxes
 *
 * Registers and renders all metaboxes on the Awards page.
 * All labels pass through the finelevate-awards text domain for Loco Translate.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Finelevate_Awards_Metaboxes {

	public static function init() {
		add_action( 'add_meta_boxes', [ __CLASS__, 'register' ] );
		add_action( 'save_post_page', [ __CLASS__, 'save' ], 10, 2 );
	}

	/* -----------------------------------------------------------------------
	 * Register metaboxes – show on ANY page that uses the Awards template,
	 * not just the auto-created page. This fixes the "new page" case.
	 * -------------------------------------------------------------------- */
	public static function register( $post_type ) {
		if ( $post_type !== 'page' ) {
			return;
		}

		global $post;
		if ( ! $post ) {
			return;
		}

		// Show metaboxes when the awards template is selected OR when this is
		// the seeded awards page (covers both manual and auto-created cases).
		$page_template  = get_post_meta( $post->ID, '_wp_page_template', true );
		$awards_page_id = (int) get_option( 'finelevate_awards_page_id' );
		$is_awards_tpl  = ( $page_template === 'finelevate-awards-page-template.php' );
		$is_awards_page = ( $awards_page_id && (int) $post->ID === $awards_page_id );

		if ( ! $is_awards_tpl && ! $is_awards_page ) {
			return;
		}

		$boxes = [
			[
				'id'       => 'finelevate-awards-hero',
				'title'    => __( '🏆 Hero Section', 'finelevate-awards' ),
				'callback' => [ __CLASS__, 'render_hero' ],
			],
			[
				'id'       => 'finelevate-awards-sponsor',
				'title'    => __( '🤝 Sponsor', 'finelevate-awards' ),
				'callback' => [ __CLASS__, 'render_sponsor' ],
			],
			[
				'id'       => 'finelevate-awards-pillars',
				'title'    => __( '🏛️ Pillars & Awards', 'finelevate-awards' ),
				'callback' => [ __CLASS__, 'render_pillars' ],
			],
			[
				'id'       => 'finelevate-awards-winners-note',
				'title'    => __( '🥇 Winners Note', 'finelevate-awards' ),
				'callback' => [ __CLASS__, 'render_winners_note' ],
			],
			[
				'id'       => 'finelevate-awards-methodology',
				'title'    => __( '📊 Methodology', 'finelevate-awards' ),
				'callback' => [ __CLASS__, 'render_methodology' ],
			],
		];

		foreach ( $boxes as $box ) {
			add_meta_box(
				$box['id'],
				$box['title'],
				$box['callback'],
				'page',
				'normal',
				'high'
			);
		}
	}

	/* -----------------------------------------------------------------------
	 * Helper: text field row
	 * -------------------------------------------------------------------- */
	private static function text_row( $label, $name, $value, $wide = false ) {
		$class = $wide ? 'finelevate-awards-input finelevate-awards-input--wide' : 'finelevate-awards-input';
		echo '<tr class="finelevate-awards-row">';
		echo '<th><label for="' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label></th>';
		echo '<td><input type="text" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="' . esc_attr( $class ) . '"></td>';
		echo '</tr>';
	}

	/* -----------------------------------------------------------------------
	 * Helper: textarea row
	 * -------------------------------------------------------------------- */
	private static function textarea_row( $label, $name, $value ) {
		echo '<tr class="finelevate-awards-row">';
		echo '<th><label for="' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label></th>';
		echo '<td><textarea id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" rows="3" class="finelevate-awards-input finelevate-awards-input--wide">' . esc_textarea( $value ) . '</textarea></td>';
		echo '</tr>';
	}

	/* -----------------------------------------------------------------------
	 * Render: Hero
	 * -------------------------------------------------------------------- */
	public static function render_hero( $post ) {
		wp_nonce_field( 'finelevate_awards_save', 'finelevate_awards_nonce' );

		$year     = get_post_meta( $post->ID, '_fa_hero_year',     true );
		$title_en = get_post_meta( $post->ID, '_fa_hero_title_en', true );
		$title_mk = get_post_meta( $post->ID, '_fa_hero_title_mk', true );
		$desc_en  = get_post_meta( $post->ID, '_fa_hero_desc_en',  true );
		$desc_mk  = get_post_meta( $post->ID, '_fa_hero_desc_mk',  true );
		$note_en  = get_post_meta( $post->ID, '_fa_hero_note_en',  true );
		$note_mk  = get_post_meta( $post->ID, '_fa_hero_note_mk',  true );

		echo '<table class="finelevate-awards-table"><tbody>';
		self::text_row( __( 'Year', 'finelevate-awards' ),             '_fa_hero_year',     $year );
		self::text_row( __( 'Title (EN)', 'finelevate-awards' ),       '_fa_hero_title_en', $title_en, true );
		self::text_row( __( 'Title (MK)', 'finelevate-awards' ),       '_fa_hero_title_mk', $title_mk, true );
		self::textarea_row( __( 'Description (EN)', 'finelevate-awards' ), '_fa_hero_desc_en', $desc_en );
		self::textarea_row( __( 'Description (MK)', 'finelevate-awards' ), '_fa_hero_desc_mk', $desc_mk );
		self::text_row( __( 'Note (EN)', 'finelevate-awards' ),        '_fa_hero_note_en',  $note_en, true );
		self::text_row( __( 'Note (MK)', 'finelevate-awards' ),        '_fa_hero_note_mk',  $note_mk, true );
		echo '</tbody></table>';
	}

	/* -----------------------------------------------------------------------
	 * Render: Sponsor
	 * -------------------------------------------------------------------- */
	public static function render_sponsor( $post ) {
		$label_en = get_post_meta( $post->ID, '_fa_sponsor_label_en', true );
		$label_mk = get_post_meta( $post->ID, '_fa_sponsor_label_mk', true );
		$text_en  = get_post_meta( $post->ID, '_fa_sponsor_text_en',  true );
		$text_mk  = get_post_meta( $post->ID, '_fa_sponsor_text_mk',  true );
		$logo     = get_post_meta( $post->ID, '_fa_sponsor_logo',     true );

		echo '<table class="finelevate-awards-table"><tbody>';
		self::text_row( __( 'Section Label (EN)', 'finelevate-awards' ), '_fa_sponsor_label_en', $label_en, true );
		self::text_row( __( 'Section Label (MK)', 'finelevate-awards' ), '_fa_sponsor_label_mk', $label_mk, true );
		self::text_row( __( 'Sponsor Text (EN)', 'finelevate-awards' ),  '_fa_sponsor_text_en',  $text_en,  true );
		self::text_row( __( 'Sponsor Text (MK)', 'finelevate-awards' ),  '_fa_sponsor_text_mk',  $text_mk,  true );
		echo '<tr class="finelevate-awards-row">';
		echo '<th><label for="_fa_sponsor_logo">' . esc_html__( 'Sponsor Logo URL', 'finelevate-awards' ) . '</label></th>';
		echo '<td>';
		echo '<input type="text" id="_fa_sponsor_logo" name="_fa_sponsor_logo" value="' . esc_attr( $logo ) . '" class="finelevate-awards-input finelevate-awards-input--wide">';
		echo '<button type="button" class="button finelevate-awards-media-btn" data-target="_fa_sponsor_logo">' . esc_html__( 'Choose Image', 'finelevate-awards' ) . '</button>';
		if ( $logo ) {
			echo '<br><img src="' . esc_url( $logo ) . '" style="max-height:60px;margin-top:6px;">';
		}
		echo '</td></tr>';
		echo '</tbody></table>';
	}

	/* -----------------------------------------------------------------------
	 * Render: Pillars & Awards
	 * -------------------------------------------------------------------- */
	public static function render_pillars( $post ) {
		$pillars = get_post_meta( $post->ID, '_fa_pillars', true );
		if ( ! is_array( $pillars ) ) {
			$pillars = [];
		}

		echo '<div id="finelevate-awards-pillars-wrap">';

		foreach ( $pillars as $pi => $pillar ) {
			self::render_pillar_block( $pi, $pillar );
		}

		echo '</div>';

		echo '<p><button type="button" class="button finelevate-awards-add-pillar">' . esc_html__( '+ Add Pillar', 'finelevate-awards' ) . '</button></p>';

		// Hidden template for JS cloning
		echo '<script type="text/html" id="finelevate-awards-pillar-tpl">';
		self::render_pillar_block( '{{PI}}', [
			'title_en'  => '',
			'title_mk'  => '',
			'desc_en'   => '',
			'desc_mk'   => '',
			'badge_url' => '',
			'slug'      => '',
			'awards'    => array_fill( 0, 3, [
				'title_en'  => '',
				'title_mk'  => '',
				'desc_en'   => '',
				'desc_mk'   => '',
				'badge_url' => '',
				'winner'    => '',
			] ),
		] );
		echo '</script>';
	}

	/* -----------------------------------------------------------------------
	 * Render a single pillar block
	 * -------------------------------------------------------------------- */
	private static function render_pillar_block( $pi, $pillar ) {
		$awards    = isset( $pillar['awards'] ) && is_array( $pillar['awards'] ) ? $pillar['awards'] : [];
		$title_en  = isset( $pillar['title_en'] )  ? $pillar['title_en']  : '';
		$title_mk  = isset( $pillar['title_mk'] )  ? $pillar['title_mk']  : '';
		$desc_en   = isset( $pillar['desc_en'] )   ? $pillar['desc_en']   : '';
		$desc_mk   = isset( $pillar['desc_mk'] )   ? $pillar['desc_mk']   : '';
		$badge_url = isset( $pillar['badge_url'] ) ? $pillar['badge_url'] : '';

		echo '<div class="finelevate-awards-pillar-block" data-pi="' . esc_attr( $pi ) . '">';
		echo '<div class="finelevate-awards-pillar-header">';
		echo '<strong>' . esc_html__( 'Pillar', 'finelevate-awards' ) . ' ' . ( is_numeric( $pi ) ? (int)$pi + 1 : '#' ) . '</strong>';
		echo '<button type="button" class="finelevate-awards-remove-pillar button-link-delete">' . esc_html__( 'Remove Pillar', 'finelevate-awards' ) . '</button>';
		echo '</div>';

		$n = "_fa_pillars[{$pi}]";

		echo '<table class="finelevate-awards-table finelevate-awards-table--inner"><tbody>';
		self::text_row( __( 'Pillar Title (EN)', 'finelevate-awards' ), "{$n}[title_en]", $title_en, true );
		self::text_row( __( 'Pillar Title (MK)', 'finelevate-awards' ), "{$n}[title_mk]", $title_mk, true );
		self::textarea_row( __( 'Pillar Description (EN)', 'finelevate-awards' ), "{$n}[desc_en]", $desc_en );
		self::textarea_row( __( 'Pillar Description (MK)', 'finelevate-awards' ), "{$n}[desc_mk]", $desc_mk );
		self::text_row( __( 'Pillar Badge URL', 'finelevate-awards' ), "{$n}[badge_url]", $badge_url, true );
		echo '</tbody></table>';

		// Awards within pillar
		echo '<div class="finelevate-awards-awards-wrap">';
		echo '<p class="finelevate-awards-section-label"><strong>' . esc_html__( 'Awards', 'finelevate-awards' ) . '</strong></p>';

		foreach ( $awards as $ai => $award ) {
			self::render_award_block( $pi, $ai, $award );
		}

		echo '<button type="button" class="button finelevate-awards-add-award" data-pi="' . esc_attr( $pi ) . '">' . esc_html__( '+ Add Award', 'finelevate-awards' ) . '</button>';
		echo '</div>'; // .finelevate-awards-awards-wrap

		echo '</div>'; // .finelevate-awards-pillar-block
	}

	/* -----------------------------------------------------------------------
	 * Render a single award block inside a pillar
	 * -------------------------------------------------------------------- */
	private static function render_award_block( $pi, $ai, $award ) {
		$title_en  = isset( $award['title_en'] )  ? $award['title_en']  : '';
		$title_mk  = isset( $award['title_mk'] )  ? $award['title_mk']  : '';
		$desc_en   = isset( $award['desc_en'] )   ? $award['desc_en']   : '';
		$desc_mk   = isset( $award['desc_mk'] )   ? $award['desc_mk']   : '';
		$badge_url = isset( $award['badge_url'] ) ? $award['badge_url'] : '';
		$winner    = isset( $award['winner'] )    ? $award['winner']    : '';

		echo '<div class="finelevate-awards-award-block" data-ai="' . esc_attr( $ai ) . '">';
		echo '<div class="finelevate-awards-award-header">';
		echo '<em>' . esc_html__( 'Award', 'finelevate-awards' ) . ' ' . ( is_numeric( $ai ) ? (int)$ai + 1 : '#' ) . '</em>';
		echo '<button type="button" class="finelevate-awards-remove-award button-link-delete">' . esc_html__( 'Remove', 'finelevate-awards' ) . '</button>';
		echo '</div>';

		$n = "_fa_pillars[{$pi}][awards][{$ai}]";

		echo '<table class="finelevate-awards-table finelevate-awards-table--award"><tbody>';
		self::text_row( __( 'Award Title (EN)', 'finelevate-awards' ),   "{$n}[title_en]",  $title_en,  true );
		self::text_row( __( 'Award Title (MK)', 'finelevate-awards' ),   "{$n}[title_mk]",  $title_mk,  true );
		self::textarea_row( __( 'Award Desc (EN)', 'finelevate-awards' ), "{$n}[desc_en]",  $desc_en );
		self::textarea_row( __( 'Award Desc (MK)', 'finelevate-awards' ), "{$n}[desc_mk]",  $desc_mk );
		self::text_row( __( 'Badge URL', 'finelevate-awards' ),          "{$n}[badge_url]", $badge_url, true );
		self::text_row( __( 'Winner (company name)', 'finelevate-awards' ), "{$n}[winner]", $winner,    true );
		echo '</tbody></table>';

		echo '</div>'; // .finelevate-awards-award-block
	}

	/* -----------------------------------------------------------------------
	 * Render: Winners Note
	 * -------------------------------------------------------------------- */
	public static function render_winners_note( $post ) {
		$note_en = get_post_meta( $post->ID, '_fa_winners_note_en', true );
		$note_mk = get_post_meta( $post->ID, '_fa_winners_note_mk', true );

		echo '<table class="finelevate-awards-table"><tbody>';
		self::text_row( __( 'Winners Note (EN)', 'finelevate-awards' ), '_fa_winners_note_en', $note_en, true );
		self::text_row( __( 'Winners Note (MK)', 'finelevate-awards' ), '_fa_winners_note_mk', $note_mk, true );
		echo '</tbody></table>';
	}

	/* -----------------------------------------------------------------------
	 * Render: Methodology
	 * -------------------------------------------------------------------- */
	public static function render_methodology( $post ) {
		$title_en    = get_post_meta( $post->ID, '_fa_method_title_en',    true );
		$title_mk    = get_post_meta( $post->ID, '_fa_method_title_mk',    true );
		$sub_en      = get_post_meta( $post->ID, '_fa_method_subtitle_en', true );
		$sub_mk      = get_post_meta( $post->ID, '_fa_method_subtitle_mk', true );
		$steps       = get_post_meta( $post->ID, '_fa_method_steps',       true );
		$blocks      = get_post_meta( $post->ID, '_fa_method_blocks',      true );

		if ( ! is_array( $steps ) )  { $steps  = []; }
		if ( ! is_array( $blocks ) ) { $blocks = []; }

		echo '<table class="finelevate-awards-table"><tbody>';
		self::text_row( __( 'Section Title (EN)', 'finelevate-awards' ),    '_fa_method_title_en',    $title_en, true );
		self::text_row( __( 'Section Title (MK)', 'finelevate-awards' ),    '_fa_method_title_mk',    $title_mk, true );
		self::text_row( __( 'Subtitle (EN)', 'finelevate-awards' ),         '_fa_method_subtitle_en', $sub_en,   true );
		self::text_row( __( 'Subtitle (MK)', 'finelevate-awards' ),         '_fa_method_subtitle_mk', $sub_mk,   true );
		echo '</tbody></table>';

		// Steps (pipeline)
		echo '<p class="finelevate-awards-section-label"><strong>' . esc_html__( 'Pipeline Steps', 'finelevate-awards' ) . '</strong></p>';
		echo '<div id="finelevate-awards-steps-wrap">';
		foreach ( $steps as $si => $step ) {
			echo '<div class="finelevate-awards-step-row">';
			echo '<input type="text" name="_fa_method_steps[' . $si . '][en]" value="' . esc_attr( $step['en'] ) . '" placeholder="' . esc_attr__( 'Step (EN)', 'finelevate-awards' ) . '" class="finelevate-awards-input">';
			echo '<input type="text" name="_fa_method_steps[' . $si . '][mk]" value="' . esc_attr( $step['mk'] ) . '" placeholder="' . esc_attr__( 'Step (MK)', 'finelevate-awards' ) . '" class="finelevate-awards-input">';
			echo '<button type="button" class="finelevate-awards-remove-step button-link-delete">✕</button>';
			echo '</div>';
		}
		echo '</div>';
		echo '<p><button type="button" class="button finelevate-awards-add-step">' . esc_html__( '+ Add Step', 'finelevate-awards' ) . '</button></p>';

		// Blocks
		echo '<p class="finelevate-awards-section-label"><strong>' . esc_html__( 'Methodology Blocks', 'finelevate-awards' ) . '</strong></p>';
		echo '<div id="finelevate-awards-blocks-wrap">';
		foreach ( $blocks as $bi => $block ) {
			$bt_en = isset( $block['title_en'] ) ? $block['title_en'] : '';
			$bt_mk = isset( $block['title_mk'] ) ? $block['title_mk'] : '';
			$bd_en = isset( $block['desc_en'] )  ? $block['desc_en']  : '';
			$bd_mk = isset( $block['desc_mk'] )  ? $block['desc_mk']  : '';

			echo '<div class="finelevate-awards-method-block">';
			echo '<table class="finelevate-awards-table finelevate-awards-table--inner"><tbody>';
			self::text_row( __( 'Block Title (EN)', 'finelevate-awards' ),  "_fa_method_blocks[{$bi}][title_en]", $bt_en, true );
			self::text_row( __( 'Block Title (MK)', 'finelevate-awards' ),  "_fa_method_blocks[{$bi}][title_mk]", $bt_mk, true );
			self::textarea_row( __( 'Block Desc (EN)', 'finelevate-awards' ), "_fa_method_blocks[{$bi}][desc_en]", $bd_en );
			self::textarea_row( __( 'Block Desc (MK)', 'finelevate-awards' ), "_fa_method_blocks[{$bi}][desc_mk]", $bd_mk );
			echo '</tbody></table>';
			echo '<button type="button" class="finelevate-awards-remove-block button-link-delete">' . esc_html__( 'Remove Block', 'finelevate-awards' ) . '</button>';
			echo '</div>';
		}
		echo '</div>';
		echo '<p><button type="button" class="button finelevate-awards-add-block">' . esc_html__( '+ Add Block', 'finelevate-awards' ) . '</button></p>';
	}

	/* -----------------------------------------------------------------------
	 * Save
	 * -------------------------------------------------------------------- */
	public static function save( $post_id, $post ) {
		// Nonce check
		if (
			! isset( $_POST['finelevate_awards_nonce'] ) ||
			! wp_verify_nonce( $_POST['finelevate_awards_nonce'], 'finelevate_awards_save' )
		) {
			return;
		}

		// Autosave / capability
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( ! current_user_can( 'edit_page', $post_id ) ) return;

		// Allow saving on any page with the awards template
		$page_template  = get_post_meta( $post_id, '_wp_page_template', true );
		$awards_page_id = (int) get_option( 'finelevate_awards_page_id' );
		$is_awards_tpl  = ( $page_template === 'finelevate-awards-page-template.php' );
		$is_awards_page = ( $awards_page_id && $awards_page_id === $post_id );

		if ( ! $is_awards_tpl && ! $is_awards_page ) {
			return;
		}

		// If no page ID stored yet, store this one
		if ( ! $awards_page_id ) {
			update_option( 'finelevate_awards_page_id', $post_id );
		}

		$simple_text_fields = [
			'_fa_hero_year', '_fa_hero_title_en', '_fa_hero_title_mk',
			'_fa_hero_desc_en', '_fa_hero_desc_mk', '_fa_hero_note_en', '_fa_hero_note_mk',
			'_fa_sponsor_label_en', '_fa_sponsor_label_mk',
			'_fa_sponsor_text_en', '_fa_sponsor_text_mk', '_fa_sponsor_logo',
			'_fa_winners_note_en', '_fa_winners_note_mk',
			'_fa_method_title_en', '_fa_method_title_mk',
			'_fa_method_subtitle_en', '_fa_method_subtitle_mk',
		];

		foreach ( $simple_text_fields as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				update_post_meta( $post_id, $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
			}
		}

		// Pillars (nested array)
		if ( isset( $_POST['_fa_pillars'] ) && is_array( $_POST['_fa_pillars'] ) ) {
			$pillars = [];
			foreach ( $_POST['_fa_pillars'] as $pillar ) {
				$clean_awards = [];
				if ( isset( $pillar['awards'] ) && is_array( $pillar['awards'] ) ) {
					foreach ( $pillar['awards'] as $award ) {
						$clean_awards[] = [
							'title_en'  => sanitize_text_field( wp_unslash( $award['title_en']  ?? '' ) ),
							'title_mk'  => sanitize_text_field( wp_unslash( $award['title_mk']  ?? '' ) ),
							'desc_en'   => sanitize_textarea_field( wp_unslash( $award['desc_en']   ?? '' ) ),
							'desc_mk'   => sanitize_textarea_field( wp_unslash( $award['desc_mk']   ?? '' ) ),
							'badge_url' => esc_url_raw( wp_unslash( $award['badge_url'] ?? '' ) ),
							'winner'    => sanitize_text_field( wp_unslash( $award['winner']    ?? '' ) ),
						];
					}
				}
				$pillars[] = [
					'title_en'  => sanitize_text_field( wp_unslash( $pillar['title_en']  ?? '' ) ),
					'title_mk'  => sanitize_text_field( wp_unslash( $pillar['title_mk']  ?? '' ) ),
					'desc_en'   => sanitize_textarea_field( wp_unslash( $pillar['desc_en']   ?? '' ) ),
					'desc_mk'   => sanitize_textarea_field( wp_unslash( $pillar['desc_mk']   ?? '' ) ),
					'badge_url' => esc_url_raw( wp_unslash( $pillar['badge_url'] ?? '' ) ),
					'awards'    => $clean_awards,
				];
			}
			update_post_meta( $post_id, '_fa_pillars', $pillars );
		}

		// Methodology steps
		if ( isset( $_POST['_fa_method_steps'] ) && is_array( $_POST['_fa_method_steps'] ) ) {
			$steps = [];
			foreach ( $_POST['_fa_method_steps'] as $step ) {
				$steps[] = [
					'en' => sanitize_text_field( wp_unslash( $step['en'] ?? '' ) ),
					'mk' => sanitize_text_field( wp_unslash( $step['mk'] ?? '' ) ),
				];
			}
			update_post_meta( $post_id, '_fa_method_steps', $steps );
		}

		// Methodology blocks
		if ( isset( $_POST['_fa_method_blocks'] ) && is_array( $_POST['_fa_method_blocks'] ) ) {
			$blocks = [];
			foreach ( $_POST['_fa_method_blocks'] as $block ) {
				$blocks[] = [
					'title_en' => sanitize_text_field( wp_unslash( $block['title_en'] ?? '' ) ),
					'title_mk' => sanitize_text_field( wp_unslash( $block['title_mk'] ?? '' ) ),
					'desc_en'  => sanitize_textarea_field( wp_unslash( $block['desc_en']  ?? '' ) ),
					'desc_mk'  => sanitize_textarea_field( wp_unslash( $block['desc_mk']  ?? '' ) ),
				];
			}
			update_post_meta( $post_id, '_fa_method_blocks', $blocks );
		}
	}
}
