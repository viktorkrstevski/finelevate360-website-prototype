<?php
/**
 * FA_Metaboxes
 * All fields are single-language (no EN/MK duplication).
 * Editor writes whatever language they want.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class FA_Metaboxes {

	const NONCE = 'fa_save_nonce';
	const TPL   = 'finelevate-awards-page-template.php';

	public static function init() {
		add_action( 'add_meta_boxes', [ __CLASS__, 'register' ] );
		add_action( 'save_post_page', [ __CLASS__, 'save' ], 10, 2 );
	}

	/* ── Is this the Awards page? ─────────────────────────────────────── */
	private static function is_awards_page( $post ) {
		if ( ! $post ) return false;
		return get_post_meta( $post->ID, '_wp_page_template', true ) === self::TPL;
	}

	/* ── Register ─────────────────────────────────────────────────────── */
	public static function register( $post_type ) {
		if ( $post_type !== 'page' ) return;
		global $post;
		if ( ! self::is_awards_page( $post ) ) return;

		$boxes = [
			[ 'fa-hero',         __( '🏆 Hero Section',    'finelevate-awards' ), 'render_hero'         ],
			[ 'fa-sponsor',      __( '🤝 Sponsor',         'finelevate-awards' ), 'render_sponsor'      ],
			[ 'fa-pillars',      __( '🏛 Pillars & Awards','finelevate-awards' ), 'render_pillars'      ],
			[ 'fa-winners-note', __( '🥇 Winners Note',    'finelevate-awards' ), 'render_winners_note' ],
			[ 'fa-methodology',  __( '📊 Methodology',     'finelevate-awards' ), 'render_methodology'  ],
		];

		foreach ( $boxes as [ $id, $title, $cb ] ) {
			add_meta_box( $id, $title, [ __CLASS__, $cb ], 'page', 'normal', 'high' );
		}
	}

	/* ── Field helpers ────────────────────────────────────────────────── */
	private static function field_text( $label, $name, $value, $wide = true ) {
		$class = 'finelevate-awards-input' . ( $wide ? ' widefat' : '' );
		echo '<p>';
		echo '<label><strong>' . esc_html( $label ) . '</strong><br>';
		echo '<input type="text" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="' . esc_attr( $class ) . '"></label>';
		echo '</p>';
	}

	private static function field_textarea( $label, $name, $value ) {
		echo '<p>';
		echo '<label><strong>' . esc_html( $label ) . '</strong><br>';
		echo '<textarea name="' . esc_attr( $name ) . '" rows="3" class="finelevate-awards-input widefat">' . esc_textarea( $value ) . '</textarea></label>';
		echo '</p>';
	}

	private static function meta( $post, $key, $default = '' ) {
		$val = get_post_meta( $post->ID, $key, true );
		return ( $val !== '' && $val !== false ) ? $val : $default;
	}

	/* ── HERO ─────────────────────────────────────────────────────────── */
	public static function render_hero( $post ) {
		wp_nonce_field( 'fa_save', self::NONCE );
		self::field_text(     __( 'Eyebrow text (e.g. FINELEVATE AWARDS 2026)', 'finelevate-awards' ), '_fa_hero_eyebrow', self::meta( $post, '_fa_hero_eyebrow' ) );
		self::field_text(     __( 'Main Title', 'finelevate-awards' ),                                  '_fa_hero_title',   self::meta( $post, '_fa_hero_title'   ) );
		self::field_textarea( __( 'Description', 'finelevate-awards' ),                                 '_fa_hero_desc',    self::meta( $post, '_fa_hero_desc'    ) );
		self::field_text(     __( 'Note (small text below description)', 'finelevate-awards' ),          '_fa_hero_note',    self::meta( $post, '_fa_hero_note'    ) );
	}

	/* ── SPONSOR ──────────────────────────────────────────────────────── */
	public static function render_sponsor( $post ) {
		self::field_text( __( 'Section label', 'finelevate-awards' ),  '_fa_spon_label', self::meta( $post, '_fa_spon_label' ) );
		self::field_text( __( 'Sponsor text',  'finelevate-awards' ),  '_fa_spon_text',  self::meta( $post, '_fa_spon_text'  ) );

		$logo = self::meta( $post, '_fa_spon_logo' );
		echo '<p><label><strong>' . esc_html__( 'Sponsor Logo URL', 'finelevate-awards' ) . '</strong><br>';
		echo '<input type="text" id="_fa_spon_logo" name="_fa_spon_logo" value="' . esc_attr( $logo ) . '" class="finelevate-awards-input widefat">';
		echo '</label> <button type="button" class="button fa-media-btn" data-target="_fa_spon_logo">' . esc_html__( 'Choose Image', 'finelevate-awards' ) . '</button>';
		if ( $logo ) echo '<br><img src="' . esc_url( $logo ) . '" style="max-height:50px;margin-top:6px;">';
		echo '</p>';
	}

	/* ── PILLARS ──────────────────────────────────────────────────────── */
	public static function render_pillars( $post ) {
		$pillars = get_post_meta( $post->ID, '_fa_pillars', true );
		if ( ! is_array( $pillars ) ) $pillars = [];

		echo '<div id="fa-pillars-wrap">';
		foreach ( $pillars as $pi => $pillar ) {
			self::pillar_block( $pi, $pillar );
		}
		echo '</div>';
		echo '<p><button type="button" class="button button-primary fa-add-pillar">+ ' . esc_html__( 'Add Pillar', 'finelevate-awards' ) . '</button></p>';

		// JS template
		echo '<script type="text/html" id="fa-pillar-tpl">';
		self::pillar_block( 'PINDEX', [ 'title'=>'','desc'=>'','badge_url'=>'','awards'=>array_fill(0,1,['title'=>'','desc'=>'','badge_url'=>'','winner'=>'']) ] );
		echo '</script>';
	}

	private static function pillar_block( $pi, $p ) {
		$title     = isset($p['title'])     ? $p['title']     : '';
		$desc      = isset($p['desc'])      ? $p['desc']      : '';
		$badge_url = isset($p['badge_url']) ? $p['badge_url'] : '';
		$awards    = isset($p['awards']) && is_array($p['awards']) ? $p['awards'] : [];
		$n         = "_fa_pillars[{$pi}]";

		echo '<div class="fa-pillar-block" data-pi="' . esc_attr($pi) . '">';
		echo '<div class="fa-block-header">';
		echo '<strong>' . esc_html__( 'Pillar', 'finelevate-awards' ) . ' <span class="fa-pi-num">' . ( is_numeric($pi) ? (int)$pi+1 : '?' ) . '</span></strong>';
		echo '<button type="button" class="button-link-delete fa-remove-pillar">' . esc_html__( 'Remove pillar', 'finelevate-awards' ) . '</button>';
		echo '</div>';

		echo '<div class="fa-block-body">';
		self::field_text(     __( 'Pillar title', 'finelevate-awards' ),    "{$n}[title]",     $title );
		self::field_textarea( __( 'Pillar description', 'finelevate-awards' ), "{$n}[desc]",   $desc  );
		self::field_text(     __( 'Badge / icon URL', 'finelevate-awards' ), "{$n}[badge_url]", $badge_url );

		echo '<div class="fa-awards-wrap">';
		echo '<p class="fa-sublabel">' . esc_html__( 'Awards in this pillar:', 'finelevate-awards' ) . '</p>';
		foreach ( $awards as $ai => $award ) {
			self::award_block( $pi, $ai, $award );
		}
		echo '<button type="button" class="button fa-add-award" data-pi="' . esc_attr($pi) . '">+ ' . esc_html__( 'Add Award', 'finelevate-awards' ) . '</button>';
		echo '</div>';
		echo '</div>'; // .fa-block-body
		echo '</div>'; // .fa-pillar-block
	}

	private static function award_block( $pi, $ai, $a ) {
		$title     = isset($a['title'])     ? $a['title']     : '';
		$desc      = isset($a['desc'])      ? $a['desc']      : '';
		$badge_url = isset($a['badge_url']) ? $a['badge_url'] : '';
		$winner    = isset($a['winner'])    ? $a['winner']    : '';
		$n         = "_fa_pillars[{$pi}][awards][{$ai}]";

		echo '<div class="fa-award-block" data-ai="' . esc_attr($ai) . '">';
		echo '<div class="fa-award-header">';
		echo '<em>' . esc_html__( 'Award', 'finelevate-awards' ) . ' <span class="fa-ai-num">' . ( is_numeric($ai) ? (int)$ai+1 : '?' ) . '</span></em>';
		echo '<button type="button" class="button-link-delete fa-remove-award">' . esc_html__( 'Remove', 'finelevate-awards' ) . '</button>';
		echo '</div>';
		self::field_text(     __( 'Title', 'finelevate-awards' ),             "{$n}[title]",     $title );
		self::field_textarea( __( 'Description', 'finelevate-awards' ),       "{$n}[desc]",      $desc );
		self::field_text(     __( 'Badge / icon URL', 'finelevate-awards' ),  "{$n}[badge_url]", $badge_url );
		self::field_text(     __( 'Winner (company name)', 'finelevate-awards' ), "{$n}[winner]", $winner );
		echo '</div>';
	}

	/* ── WINNERS NOTE ─────────────────────────────────────────────────── */
	public static function render_winners_note( $post ) {
		self::field_text( __( 'Winners note text', 'finelevate-awards' ), '_fa_winners_note', self::meta( $post, '_fa_winners_note' ) );
	}

	/* ── METHODOLOGY ──────────────────────────────────────────────────── */
	public static function render_methodology( $post ) {
		self::field_text(     __( 'Section title', 'finelevate-awards' ),    '_fa_meth_title', self::meta( $post, '_fa_meth_title' ) );
		self::field_textarea( __( 'Subtitle / intro', 'finelevate-awards' ), '_fa_meth_sub',   self::meta( $post, '_fa_meth_sub'   ) );

		// Steps
		$steps = get_post_meta( $post->ID, '_fa_meth_steps', true );
		if ( ! is_array( $steps ) ) $steps = [];

		echo '<p class="fa-sublabel"><strong>' . esc_html__( 'Pipeline steps (shown as progress bar)', 'finelevate-awards' ) . '</strong></p>';
		echo '<div id="fa-steps-wrap">';
		foreach ( $steps as $si => $step ) {
			echo '<div class="fa-step-row">';
			echo '<input type="text" name="_fa_meth_steps[' . $si . ']" value="' . esc_attr( $step ) . '" class="finelevate-awards-input widefat" placeholder="' . esc_attr__( 'Step label', 'finelevate-awards' ) . '">';
			echo '<button type="button" class="button-link-delete fa-remove-step" style="margin-left:6px;">✕</button>';
			echo '</div>';
		}
		echo '</div>';
		echo '<p><button type="button" class="button fa-add-step">+ ' . esc_html__( 'Add Step', 'finelevate-awards' ) . '</button></p>';

		// Blocks
		$blocks = get_post_meta( $post->ID, '_fa_meth_blocks', true );
		if ( ! is_array( $blocks ) ) $blocks = [];

		echo '<p class="fa-sublabel"><strong>' . esc_html__( 'Methodology blocks', 'finelevate-awards' ) . '</strong></p>';
		echo '<div id="fa-blocks-wrap">';
		foreach ( $blocks as $bi => $block ) {
			$bt = isset($block['title']) ? $block['title'] : '';
			$bd = isset($block['desc'])  ? $block['desc']  : '';
			echo '<div class="fa-meth-block">';
			self::field_text(     __( 'Block title', 'finelevate-awards' ),       "_fa_meth_blocks[{$bi}][title]", $bt );
			self::field_textarea( __( 'Block description', 'finelevate-awards' ), "_fa_meth_blocks[{$bi}][desc]",  $bd );
			echo '<p><button type="button" class="button-link-delete fa-remove-block">' . esc_html__( 'Remove block', 'finelevate-awards' ) . '</button></p>';
			echo '<hr>';
			echo '</div>';
		}
		echo '</div>';
		echo '<p><button type="button" class="button fa-add-block">+ ' . esc_html__( 'Add Block', 'finelevate-awards' ) . '</button></p>';
	}

	/* ── SAVE ─────────────────────────────────────────────────────────── */
	public static function save( $post_id, $post ) {
		if ( ! isset( $_POST[ self::NONCE ] ) ) return;
		if ( ! wp_verify_nonce( $_POST[ self::NONCE ], 'fa_save' ) ) return;
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( ! current_user_can( 'edit_page', $post_id ) ) return;
		if ( ! self::is_awards_page( $post ) ) return;

		// Simple text fields
		foreach ( [ '_fa_hero_eyebrow','_fa_hero_title','_fa_hero_desc','_fa_hero_note',
		            '_fa_spon_label','_fa_spon_text','_fa_spon_logo',
		            '_fa_winners_note','_fa_meth_title','_fa_meth_sub' ] as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				update_post_meta( $post_id, $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
			}
		}

		// Pillars
		if ( isset( $_POST['_fa_pillars'] ) && is_array( $_POST['_fa_pillars'] ) ) {
			$clean = [];
			foreach ( $_POST['_fa_pillars'] as $p ) {
				$awards = [];
				if ( isset( $p['awards'] ) && is_array( $p['awards'] ) ) {
					foreach ( $p['awards'] as $a ) {
						$awards[] = [
							'title'     => sanitize_text_field( wp_unslash( $a['title']     ?? '' ) ),
							'desc'      => sanitize_textarea_field( wp_unslash( $a['desc']  ?? '' ) ),
							'badge_url' => esc_url_raw( wp_unslash( $a['badge_url']          ?? '' ) ),
							'winner'    => sanitize_text_field( wp_unslash( $a['winner']     ?? '' ) ),
						];
					}
				}
				$clean[] = [
					'title'     => sanitize_text_field( wp_unslash( $p['title']     ?? '' ) ),
					'desc'      => sanitize_textarea_field( wp_unslash( $p['desc']  ?? '' ) ),
					'badge_url' => esc_url_raw( wp_unslash( $p['badge_url']          ?? '' ) ),
					'awards'    => $awards,
				];
			}
			update_post_meta( $post_id, '_fa_pillars', $clean );
		} else {
			update_post_meta( $post_id, '_fa_pillars', [] );
		}

		// Steps
		if ( isset( $_POST['_fa_meth_steps'] ) && is_array( $_POST['_fa_meth_steps'] ) ) {
			update_post_meta( $post_id, '_fa_meth_steps',
				array_map( fn($s) => sanitize_text_field( wp_unslash($s) ), $_POST['_fa_meth_steps'] )
			);
		} else {
			update_post_meta( $post_id, '_fa_meth_steps', [] );
		}

		// Blocks
		if ( isset( $_POST['_fa_meth_blocks'] ) && is_array( $_POST['_fa_meth_blocks'] ) ) {
			$clean = [];
			foreach ( $_POST['_fa_meth_blocks'] as $b ) {
				$clean[] = [
					'title' => sanitize_text_field( wp_unslash( $b['title'] ?? '' ) ),
					'desc'  => sanitize_textarea_field( wp_unslash( $b['desc']  ?? '' ) ),
				];
			}
			update_post_meta( $post_id, '_fa_meth_blocks', $clean );
		} else {
			update_post_meta( $post_id, '_fa_meth_blocks', [] );
		}
	}
}
