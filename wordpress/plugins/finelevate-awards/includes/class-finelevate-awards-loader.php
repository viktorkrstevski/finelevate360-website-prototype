<?php
/**
 * Finelevate_Awards_Loader
 *
 * Handles asset enqueueing and one-time page creation on activation.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Finelevate_Awards_Loader {

	public static function init() {
		add_action( 'wp_enqueue_scripts',    [ __CLASS__, 'enqueue_frontend' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin' ] );

		register_activation_hook( FINELEVATE_AWARDS_DIR . 'finelevate-awards.php', [ __CLASS__, 'create_awards_page' ] );
	}

	/* -----------------------------------------------------------------------
	 * Frontend assets
	 * -------------------------------------------------------------------- */
	public static function enqueue_frontend() {
		if ( ! self::is_awards_page() ) {
			return;
		}

		wp_enqueue_style(
			'finelevate-awards-style',
			FINELEVATE_AWARDS_URL . 'assets/css/finelevate-awards.css',
			[],
			FINELEVATE_AWARDS_VERSION
		);

		wp_enqueue_script(
			'finelevate-awards-script',
			FINELEVATE_AWARDS_URL . 'assets/js/finelevate-awards.js',
			[],
			FINELEVATE_AWARDS_VERSION,
			true
		);
	}

	/* -----------------------------------------------------------------------
	 * Admin assets (metabox JS/CSS)
	 * -------------------------------------------------------------------- */
	public static function enqueue_admin( $hook ) {
		if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}

		global $post;
		if ( ! $post || $post->post_type !== 'page' ) {
			return;
		}

		wp_enqueue_style(
			'finelevate-awards-admin',
			FINELEVATE_AWARDS_URL . 'assets/css/finelevate-awards-admin.css',
			[],
			FINELEVATE_AWARDS_VERSION
		);

		wp_enqueue_script(
			'finelevate-awards-admin',
			FINELEVATE_AWARDS_URL . 'assets/js/finelevate-awards-admin.js',
			[ 'jquery' ],
			FINELEVATE_AWARDS_VERSION,
			true
		);

		// Pass translated strings to JS (Loco Translate will translate these)
		wp_localize_script( 'finelevate-awards-admin', 'faAdmin', [
			'confirmRemovePillar' => __( 'Remove this pillar and all its awards?', 'finelevate-awards' ),
			'labelAward'          => __( 'Award', 'finelevate-awards' ),
			'labelRemove'         => __( 'Remove', 'finelevate-awards' ),
			'labelRemoveBlock'    => __( 'Remove Block', 'finelevate-awards' ),
			'labelAwardTitleEn'   => __( 'Award Title (EN)', 'finelevate-awards' ),
			'labelAwardTitleMk'   => __( 'Award Title (MK)', 'finelevate-awards' ),
			'labelAwardDescEn'    => __( 'Award Desc (EN)', 'finelevate-awards' ),
			'labelAwardDescMk'    => __( 'Award Desc (MK)', 'finelevate-awards' ),
			'labelBadgeUrl'       => __( 'Badge URL', 'finelevate-awards' ),
			'labelWinner'         => __( 'Winner (company name)', 'finelevate-awards' ),
			'labelStepEn'         => __( 'Step (EN)', 'finelevate-awards' ),
			'labelStepMk'         => __( 'Step (MK)', 'finelevate-awards' ),
			'labelBlockTitleEn'   => __( 'Block Title (EN)', 'finelevate-awards' ),
			'labelBlockTitleMk'   => __( 'Block Title (MK)', 'finelevate-awards' ),
			'labelBlockDescEn'    => __( 'Block Desc (EN)', 'finelevate-awards' ),
			'labelBlockDescMk'    => __( 'Block Desc (MK)', 'finelevate-awards' ),
			'mediaTitle'          => __( 'Choose Sponsor Logo', 'finelevate-awards' ),
			'mediaButton'         => __( 'Use this image', 'finelevate-awards' ),
		] );
	}

	/* -----------------------------------------------------------------------
	 * Helper – are we on the Awards page?
	 * -------------------------------------------------------------------- */
	public static function is_awards_page() {
		if ( ! is_page() ) {
			return false;
		}

		global $post;
		$page_id = get_option( 'finelevate_awards_page_id' );

		return $post && ( (int) $post->ID === (int) $page_id || $post->post_name === 'awards' );
	}

	/* -----------------------------------------------------------------------
	 * Create the Awards page on plugin activation (runs once)
	 * -------------------------------------------------------------------- */
	public static function create_awards_page() {
		$existing = get_option( 'finelevate_awards_page_id' );
		if ( $existing && get_post( $existing ) ) {
			return;
		}

		$page_id = wp_insert_post( [
			'post_title'   => __( 'Awards', 'finelevate-awards' ),
			'post_name'    => 'awards',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => '',
			'meta_input'   => [
				'_wp_page_template' => 'finelevate-awards-page-template.php',
			],
		] );

		if ( $page_id && ! is_wp_error( $page_id ) ) {
			update_option( 'finelevate_awards_page_id', $page_id );

			// Seed default meta values
			self::seed_default_meta( $page_id );
		}
	}

	/* -----------------------------------------------------------------------
	 * Seed default content (mirrors finelevate360.com/awards)
	 * -------------------------------------------------------------------- */
	private static function seed_default_meta( $page_id ) {

		// Hero
		update_post_meta( $page_id, '_fa_hero_year',        '2026' );
		update_post_meta( $page_id, '_fa_hero_title_en',    '2026 Finelevate Awards for Digital Excellence' );
		update_post_meta( $page_id, '_fa_hero_title_mk',    '2026 Finelevate Награди за дигитална извонредност' );
		update_post_meta( $page_id, '_fa_hero_desc_en',     'Finelevate identifies category leaders in banking, insurance, and fintech using measurable signals: digital capability, user experience, reliability, and public customer voice.' );
		update_post_meta( $page_id, '_fa_hero_desc_mk',     'Finelevate ги идентификува лидерите во категории за банкарство, осигурување и финтек преку мерливи сигнали: дигитални функционалности, корисничко искуство, доверливост и гласот на корисниците.' );
		update_post_meta( $page_id, '_fa_hero_note_en',     'No paid positioning. No influence.' );
		update_post_meta( $page_id, '_fa_hero_note_mk',     'Нема платено позиционирање. Нема влијание.' );

		// Sponsor
		update_post_meta( $page_id, '_fa_sponsor_label_en', 'FINELEVATE 2026 AWARDS SUPPORT' );
		update_post_meta( $page_id, '_fa_sponsor_label_mk', 'ПОДДРШКА ЗА FINELEVATE НАГРАДИТЕ 2026' );
		update_post_meta( $page_id, '_fa_sponsor_text_en',  'General Sponsor to be announced' );
		update_post_meta( $page_id, '_fa_sponsor_text_mk',  'Генерален спонзор ќе биде објавен наскоро' );
		update_post_meta( $page_id, '_fa_sponsor_logo',     '' );

		// Pillars
		$badge_base = 'https://www.finelevate360.com/assets/icons/badges/';
		$pillars = [
			[
				'title_en'  => 'Banking',
				'title_mk'  => 'Банкарство',
				'desc_en'   => 'Retail & business banking digital experience across mobile and web journeys.',
				'desc_mk'   => 'Дигитално искуство во retail и business банкарство преку мобилни и web патеки.',
				'badge_url' => $badge_base . 'BANKING%20-%20DIGITAL_EXCELLENCE%20-%20EN.svg?v=20260215r5',
				'slug'      => 'BANKING',
				'awards'    => [
					[
						'title_en'  => 'Digital Excellence',
						'title_mk'  => 'Дигитална извонредност',
						'desc_en'   => 'Best end-to-end experience across key customer journeys, measured through capability, UX, and reliability signals.',
						'desc_mk'   => 'Најдобро end-to-end искуство низ клучни патеки, мерено преку функционалности, UX и сигнали за доверливост.',
						'badge_url' => $badge_base . 'BANKING%20-%20DIGITAL_EXCELLENCE%20-%20EN.svg?v=20260215r5',
						'winner'    => '',
					],
					[
						'title_en'  => 'Best Innovation',
						'title_mk'  => 'Најдобра иновација',
						'desc_en'   => 'Most meaningful innovation with measurable impact on customer value, efficiency, or differentiation.',
						'desc_mk'   => 'Најзначајна иновација со мерливо влијание врз вредноста за корисници, ефикасност или диференцијација.',
						'badge_url' => $badge_base . 'BANKING%20-%20BEST_INNOVATION%20-%20EN.svg?v=20260215r5',
						'winner'    => '',
					],
					[
						'title_en'  => 'Voice of Customer',
						'title_mk'  => 'Глас на корисниците',
						'desc_en'   => 'Recognition grounded in public customer sentiment and experience signals.',
						'desc_mk'   => 'Признание засновано на јавна корисничка перцепција и сигнали од искуство.',
						'badge_url' => $badge_base . 'BANKING%20-%20VOICE_OF_CUSTOMER%20-%20EN.svg?v=20260215r5',
						'winner'    => '',
					],
				],
			],
			[
				'title_en'  => 'Insurance',
				'title_mk'  => 'Осигурување',
				'desc_en'   => 'Digital product and service experience across insurance journeys, service moments, and trust signals.',
				'desc_mk'   => 'Дигитално искуство низ осигурителни патеки, сервисни моменти и сигнали за доверба.',
				'badge_url' => $badge_base . 'INSURANCE%20-%20DIGITAL_EXCELLENCE%20-%20EN.svg?v=20260215r5',
				'slug'      => 'INSURANCE',
				'awards'    => [
					[
						'title_en'  => 'Digital Excellence',
						'title_mk'  => 'Дигитална извонредност',
						'desc_en'   => 'Best end-to-end experience across key customer journeys, measured through capability, UX, and reliability signals.',
						'desc_mk'   => 'Најдобро end-to-end искуство низ клучни патеки, мерено преку функционалности, UX и сигнали за доверливост.',
						'badge_url' => $badge_base . 'INSURANCE%20-%20DIGITAL_EXCELLENCE%20-%20EN.svg?v=20260215r5',
						'winner'    => '',
					],
					[
						'title_en'  => 'Best Innovation',
						'title_mk'  => 'Најдобра иновација',
						'desc_en'   => 'Most meaningful innovation with measurable impact on customer value, efficiency, or differentiation.',
						'desc_mk'   => 'Најзначајна иновација со мерливо влијание врз вредноста за корисници, ефикасност или диференцијација.',
						'badge_url' => $badge_base . 'INSURANCE%20-%20BEST_INNOVATION%20-%20EN.svg?v=20260215r5',
						'winner'    => '',
					],
					[
						'title_en'  => 'Voice of Customer',
						'title_mk'  => 'Глас на корисниците',
						'desc_en'   => 'Recognition grounded in public customer sentiment and experience signals.',
						'desc_mk'   => 'Признание засновано на јавна корисничка перцепција и сигнали од искуство.',
						'badge_url' => $badge_base . 'INSURANCE%20-%20VOICE_OF_CUSTOMER%20-%20EN.svg?v=20260215r5',
						'winner'    => '',
					],
				],
			],
			[
				'title_en'  => 'Fintech / Quick Loans',
				'title_mk'  => 'Финтек / Брзи кредити',
				'desc_en'   => 'Digital product excellence across fintech journeys including onboarding, servicing, and trust signals.',
				'desc_mk'   => 'Дигитална извонредност низ финтек патеки, вклучувајќи onboarding, сервисирање и сигнали за доверба.',
				'badge_url' => $badge_base . 'FINTECH%20-%20DIGITAL_EXCELLENCE%20-%20EN.svg?v=20260215r5',
				'slug'      => 'FINTECH',
				'awards'    => [
					[
						'title_en'  => 'Digital Excellence',
						'title_mk'  => 'Дигитална извонредност',
						'desc_en'   => 'Best end-to-end experience across key customer journeys, measured through capability, UX, and reliability signals.',
						'desc_mk'   => 'Најдобро end-to-end искуство низ клучни патеки, мерено преку функционалности, UX и сигнали за доверливост.',
						'badge_url' => $badge_base . 'FINTECH%20-%20DIGITAL_EXCELLENCE%20-%20EN.svg?v=20260215r5',
						'winner'    => '',
					],
					[
						'title_en'  => 'Best Innovation',
						'title_mk'  => 'Најдобра иновација',
						'desc_en'   => 'Most meaningful innovation with measurable impact on customer value, efficiency, or differentiation.',
						'desc_mk'   => 'Најзначајна иновација со мерливо влијание врз вредноста за корисници, ефикасност или диференцијација.',
						'badge_url' => $badge_base . 'FINTECH%20-%20BEST_INNOVATION%20-%20EN.svg?v=20260215r5',
						'winner'    => '',
					],
					[
						'title_en'  => 'Voice of Customer',
						'title_mk'  => 'Глас на корисниците',
						'desc_en'   => 'Recognition grounded in public customer sentiment and experience signals.',
						'desc_mk'   => 'Признание засновано на јавна корисничка перцепција и сигнали од искуство.',
						'badge_url' => $badge_base . 'FINTECH%20-%20VOICE_OF_CUSTOMER%20-%20EN.svg?v=20260215r5',
						'winner'    => '',
					],
				],
			],
		];

		update_post_meta( $page_id, '_fa_pillars', $pillars );

		// Winners note
		update_post_meta( $page_id, '_fa_winners_note_en', 'Winner updates will be published here.' );
		update_post_meta( $page_id, '_fa_winners_note_mk', 'Победниците ќе бидат објавени тука.' );

		// Methodology
		update_post_meta( $page_id, '_fa_method_title_en',   'Methodology' );
		update_post_meta( $page_id, '_fa_method_title_mk',   'Методологија' );
		update_post_meta( $page_id, '_fa_method_subtitle_en', 'Consistent scoring across capabilities, journeys, and public signals.' );
		update_post_meta( $page_id, '_fa_method_subtitle_mk', 'Конзистентно оценување преку функционалности, кориснички патеки и јавни сигнали.' );

		$method_steps = [
			[ 'en' => 'Capabilities',      'mk' => 'Функционалности' ],
			[ 'en' => 'Customer Journeys', 'mk' => 'Кориснички патеки' ],
			[ 'en' => 'Public Signals',    'mk' => 'Јавни сигнали' ],
			[ 'en' => 'Composite Score',   'mk' => 'Композитен скор' ],
		];
		update_post_meta( $page_id, '_fa_method_steps', $method_steps );

		$method_blocks = [
			[
				'title_en' => 'Evidence-led',
				'title_mk' => 'Доказно засновано',
				'desc_en'  => 'We combine capability checks, journey UX, and reliability/public feedback signals.',
				'desc_mk'  => 'Комбинираме проверки на функционалности, корисничко искуство и сигнали за доверливост/јавен фидбек.',
			],
			[
				'title_en' => 'Comparable scoring',
				'title_mk' => 'Споредливо оценување',
				'desc_en'  => 'Same rubric, same weighting, consistent thresholds.',
				'desc_mk'  => 'Иста рубрика, исти тежини, конзистентни прагови.',
			],
			[
				'title_en' => 'Update cadence',
				'title_mk' => 'Ажурирање',
				'desc_en'  => 'Snapshots and seasonal updates — transparent change notes.',
				'desc_mk'  => 'Снимки и сезонски ажурирања — транспарентни белешки за промени.',
			],
		];
		update_post_meta( $page_id, '_fa_method_blocks', $method_blocks );
	}
}
