<?php
/**
 * Template: Single Event
 * Location: /wp-content/plugins/events-cpt/templates/single-event.php
 */

get_header();

// Load the current post once.
the_post();

$post_id  = get_the_ID();
$day      = get_post_meta( $post_id, '_event_day', true );
$month    = get_post_meta( $post_id, '_event_month', true );
$year     = get_post_meta( $post_id, '_event_year', true );
$location = get_post_meta( $post_id, '_event_location', true );
$agenda   = get_post_meta( $post_id, '_event_agenda', true );
$sponsors = get_post_meta( $post_id, '_event_sponsors', true );

/**
 * Finelevate Digital Summit event IDs for all language versions.
 */
$summit_event_ids = array(
	255,  // English.
	768,  // Macedonian.
	1661, // Serbian.
	1662, // Croatian.
);

$show_summit_commercial_sections = in_array(
	(int) $post_id,
	array_map( 'intval', $summit_event_ids ),
	true
);

/**
 * Ticket purchase URLs.
 */
$conference_pass_url = 'https://www.karti.com.mk/finelevate-summit-2026.nspx';

$leadership_pass_url = 'https://www.karti.com.mk/finelevate-summit-2026-leadership-circle-pass.nspx';

/**
 * Ticket artwork URLs.
 */
$conference_pass_image_url = 'https://finelevate360.com/wp-content/uploads/2026/07/conference-basic.png';

$leadership_pass_image_url = 'https://finelevate360.com/wp-content/uploads/2026/07/leadership-pass.png';

/**
 * Ticket options.
 *
 * Every visitor-facing label is wrapped in a WordPress translation function
 * using the events-cpt text domain, so it can be detected by Loco Translate.
 */
$ticket_options = array(
	array(
		'name'           => __(
			'Conference Pass',
			'events-cpt'
		),
		'label'          => __(
			'Full Conference Access',
			'events-cpt'
		),
		'price'          => '6,000 MKD',
		'image'          => $conference_pass_image_url,
		'url'            => $conference_pass_url,
		'description'    => __(
			'Full access to the summit programme, expert insights and professional networking.',
			'events-cpt'
		),
		'details_label'  => __(
			'View pass details',
			'events-cpt'
		),
		'purchase_label' => __(
			'Purchase Conference Pass',
			'events-cpt'
		),
		'features'       => array(
			__(
				'Full-day access to the conference programme',
				'events-cpt'
			),
			__(
				'Keynote sessions and panel discussions',
				'events-cpt'
			),
			__(
				'Coffee breaks and networking lunch',
				'events-cpt'
			),
			__(
				'Access to conference networking opportunities',
				'events-cpt'
			),
		),
		'class'          => '',
	),
	array(
		'name'           => __(
			'Leadership Circle Pass',
			'events-cpt'
		),
		'label'          => __(
			'Premium Executive Experience',
			'events-cpt'
		),
		'price'          => '12,000 MKD',
		'image'          => $leadership_pass_image_url,
		'url'            => $leadership_pass_url,
		'description'    => __(
			'A premium summit experience created for senior executives, founders and decision-makers.',
			'events-cpt'
		),
		'details_label'  => __(
			'View pass details',
			'events-cpt'
		),
		'purchase_label' => __(
			'Purchase Leadership Circle Pass',
			'events-cpt'
		),
		'features'       => array(
			__(
				'Full-day access to the conference programme',
				'events-cpt'
			),
			__(
				'Reserved access to the Executive Circle Area',
				'events-cpt'
			),
			__(
				'Premium networking with senior leaders',
				'events-cpt'
			),
			__(
				'Access to the Executive Circle evening event',
				'events-cpt'
			),
		),
		'class'          => 'event-pass-card--leadership',
	),
);

/**
 * Translatable month labels.
 */
$months = array(
	'01' => _x( 'January', 'month name', 'events-cpt' ),
	'02' => _x( 'February', 'month name', 'events-cpt' ),
	'03' => _x( 'March', 'month name', 'events-cpt' ),
	'04' => _x( 'April', 'month name', 'events-cpt' ),
	'05' => _x( 'May', 'month name', 'events-cpt' ),
	'06' => _x( 'June', 'month name', 'events-cpt' ),
	'07' => _x( 'July', 'month name', 'events-cpt' ),
	'08' => _x( 'August', 'month name', 'events-cpt' ),
	'09' => _x( 'September', 'month name', 'events-cpt' ),
	'10' => _x( 'October', 'month name', 'events-cpt' ),
	'11' => _x( 'November', 'month name', 'events-cpt' ),
	'12' => _x( 'December', 'month name', 'events-cpt' ),
);

$month_label = isset( $months[ $month ] ) ? $months[ $month ] : '';
$has_thumb   = has_post_thumbnail();
?>

<article
	id="event-<?php echo esc_attr( $post_id ); ?>"
	<?php post_class( 'event-single' ); ?>
>

	<!-- HERO -->
	<?php if ( $has_thumb ) : ?>

		<div class="event-single__hero">

			<?php the_post_thumbnail( 'full' ); ?>

			<div class="event-single__hero-overlay">
				<h1><?php the_title(); ?></h1>
			</div>

		</div>

	<?php else : ?>

		<h1 class="event-single__title">
			<?php the_title(); ?>
		</h1>

	<?php endif; ?>
	
	<!-- PARTNERS BAR -->
	<?php if ( is_active_sidebar( 'partner-widget' ) ) : ?>
	<div class="finelevate-partner-widget" role="complementary">

		<?php if ( is_active_sidebar( 'partner-premium' ) ) : ?>
			<?php dynamic_sidebar( 'partner-premium' ); ?>
		<?php endif; ?>

		<div class="finelevate-partner-logos">
			<div class="carousel-track">
				<?php dynamic_sidebar( 'partner-widget' ); ?>
			</div>
		</div>

		<div class="finelevate-all-partners">
			<a href="<?php echo esc_url( home_url( '/partners/' ) ); ?>">
				<?php esc_html_e( 'All partners', 'finelevate' ); ?>
			</a>
		</div>

	</div>
<?php endif; ?>
	<!-- META BAR -->
	<div class="event-single__meta">

		<?php if ( $day || $month_label || $year ) : ?>

			<div class="event-single__meta-item">

				<span class="material-symbols-outlined">
					calendar_month
				</span>

				<div>
					<strong>
						<?php
						$date_parts = array();

						if ( $day ) {
							$date_parts[] = esc_html( $day );
						}

						if ( $month_label ) {
							$date_parts[] = esc_html( $month_label );
						}

						if ( $year ) {
							$date_parts[] = esc_html( $year );
						}

						echo implode( ' ', $date_parts );
						?>
					</strong>
				</div>

			</div>

		<?php endif; ?>

		<?php if ( $location ) : ?>

			<div class="event-single__meta-item">

				<span class="material-symbols-outlined">
					move_location
				</span>

				<div>
					<strong>
						<?php echo esc_html( $location ); ?>
					</strong>
				</div>

			</div>

		<?php endif; ?>

	</div>

	<!-- DESCRIPTION -->
	<div class="event-single__description">

		<h2 class="event-single__section-title">
			<?php
			echo esc_html__(
				'About the event',
				'events-cpt'
			);
			?>
		</h2>

		<?php the_content(); ?>

	</div>

	<?php if ( $show_summit_commercial_sections ) : ?>

		<!-- CONFERENCE PASSES -->
		<section
			id="tickets"
			class="event-single__passes"
		>

			<div class="event-section-heading">

				<div>

					<span class="event-section-heading__eyebrow">
						<?php
						echo esc_html__(
							'Attend the Summit',
							'events-cpt'
						);
						?>
					</span>

					<h2 class="event-single__section-title">
						<?php
						echo esc_html__(
							'Choose Your Conference Pass',
							'events-cpt'
						);
						?>
					</h2>

				</div>

				<p>
					<?php
					echo esc_html__(
						'Select the experience that best matches your role and networking objectives.',
						'events-cpt'
					);
					?>
				</p>

			</div>

			<div class="event-pass-grid">

				<?php foreach ( $ticket_options as $ticket ) : ?>

					<article
						class="event-pass-card <?php echo esc_attr( $ticket['class'] ); ?>"
					>

						<div class="event-pass-card__visual">

							<?php if ( ! empty( $ticket['image'] ) ) : ?>

								<img
									src="<?php echo esc_url( $ticket['image'] ); ?>"
									alt="<?php echo esc_attr( $ticket['name'] ); ?>"
									loading="lazy"
									decoding="async"
								>

							<?php else : ?>

								<div class="event-pass-card__image-placeholder">

									<span>
										<?php
										echo esc_html__(
											'Ticket artwork',
											'events-cpt'
										);
										?>
									</span>

									<small>
										<?php
										echo esc_html__(
											'Add the Media Library image URL in the template.',
											'events-cpt'
										);
										?>
									</small>

								</div>

							<?php endif; ?>

						</div>

						<div class="event-pass-card__body">

							<div class="event-pass-card__header">

								<div>

									<span class="event-pass-card__label">
										<?php echo esc_html( $ticket['label'] ); ?>
									</span>

									<h3>
										<?php echo esc_html( $ticket['name'] ); ?>
									</h3>

								</div>

								<div class="event-pass-card__price">
									<?php echo esc_html( $ticket['price'] ); ?>
								</div>

							</div>

							<p class="event-pass-card__description">
								<?php echo esc_html( $ticket['description'] ); ?>
							</p>

							<details class="event-pass-card__details">

								<summary>
									<?php echo esc_html( $ticket['details_label'] ); ?>
								</summary>

								<div class="event-pass-card__details-content">

									<ul class="event-pass-card__features">

										<?php foreach ( $ticket['features'] as $feature ) : ?>

											<li>
												<?php echo esc_html( $feature ); ?>
											</li>

										<?php endforeach; ?>

									</ul>

								</div>

							</details>

							<a
								class="event-pass-card__purchase"
								href="<?php echo esc_url( $ticket['url'] ); ?>"
								target="_blank"
								rel="noopener noreferrer"
							>
								<?php echo esc_html( $ticket['purchase_label'] ); ?>
							</a>

						</div>

					</article>

				<?php endforeach; ?>

			</div>

		</section>

	<?php endif; ?>
	


	<!-- AGENDA -->
	<?php if ( ! empty( $agenda ) && is_array( $agenda ) ) : ?>

		<div class="event-single__agenda">

			<h2 class="event-single__section-title">
				<?php
				echo esc_html__(
					'Agenda',
					'events-cpt'
				);
				?>
			</h2>

			<table class="agenda-table">

				<tbody>

					<?php foreach ( $agenda as $item ) : ?>

						<?php
						if (
							empty( $item['time'] ) &&
							empty( $item['title'] )
						) {
							continue;
						}
						?>

						<tr>

							<td class="agenda-col-time">
								<?php
								echo esc_html(
									isset( $item['time'] )
										? $item['time']
										: ''
								);
								?>
							</td>

							<td class="agenda-col-title">
								<?php
								echo esc_html(
									isset( $item['title'] )
										? $item['title']
										: ''
								);
								?>
							</td>

						</tr>

					<?php endforeach; ?>

				</tbody>

			</table>

		</div>

	<?php endif; ?>

	<!-- SPONSORS -->
	<?php if ( ! empty( $sponsors ) && is_array( $sponsors ) ) : ?>

		<div class="event-single__sponsors">

			<h2 class="event-single__section-title">
				<?php
				echo esc_html__(
					'Sponsors',
					'events-cpt'
				);
				?>
			</h2>

			<div class="sponsors-grid">

				<?php foreach ( $sponsors as $sponsor ) : ?>

					<?php
					if ( empty( $sponsor['name'] ) ) {
						continue;
					}

					$has_sponsor_url  = ! empty( $sponsor['url'] );
					$has_sponsor_logo = ! empty( $sponsor['logo'] );
					?>

					<div class="sponsor-item">

						<?php if ( $has_sponsor_url ) : ?>

							<a
								href="<?php echo esc_url( $sponsor['url'] ); ?>"
								target="_blank"
								rel="noopener sponsored"
							>

						<?php endif; ?>

						<?php if ( $has_sponsor_logo ) : ?>

							<img
								src="<?php echo esc_url( $sponsor['logo'] ); ?>"
								alt="<?php echo esc_attr( $sponsor['name'] ); ?>"
								loading="lazy"
								decoding="async"
							>

						<?php else : ?>

							<span class="sponsor-name-text">
								<?php echo esc_html( $sponsor['name'] ); ?>
							</span>

						<?php endif; ?>

						<?php if ( $has_sponsor_url ) : ?>

							</a>

						<?php endif; ?>

						<?php if ( $has_sponsor_logo ) : ?>

							<div class="sponsor-name">
								<?php echo esc_html( $sponsor['name'] ); ?>
							</div>

						<?php endif; ?>

					</div>

				<?php endforeach; ?>

			</div>

		</div>

	<?php endif; ?>

</article>

<?php get_footer();