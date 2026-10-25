<?php
/**
 * Finelevate Awards – content partial
 * All values come 100% from post meta – no hardcoded fallbacks.
 * @var WP_Post $post
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$pid = $post->ID;

$hero_eyebrow  = get_post_meta( $pid, '_fa_hero_eyebrow',  true );
$hero_title    = get_post_meta( $pid, '_fa_hero_title',    true );
$hero_desc     = get_post_meta( $pid, '_fa_hero_desc',     true );
$hero_note     = get_post_meta( $pid, '_fa_hero_note',     true );

$spon_label    = get_post_meta( $pid, '_fa_spon_label',    true );
$spon_text     = get_post_meta( $pid, '_fa_spon_text',     true );
$spon_logo     = get_post_meta( $pid, '_fa_spon_logo',     true );

$pillars       = get_post_meta( $pid, '_fa_pillars',       true );

$winners_note  = get_post_meta( $pid, '_fa_winners_note',  true );

$meth_title    = get_post_meta( $pid, '_fa_meth_title',    true );
$meth_sub      = get_post_meta( $pid, '_fa_meth_sub',      true );
$meth_steps    = get_post_meta( $pid, '_fa_meth_steps',    true );
$meth_blocks   = get_post_meta( $pid, '_fa_meth_blocks',   true );

if ( ! is_array( $pillars ) )    $pillars    = [];
if ( ! is_array( $meth_steps ) ) $meth_steps = [];
if ( ! is_array( $meth_blocks )) $meth_blocks = [];
?>

<div class="finelevate-awards-wrap">

	<?php if ( $hero_eyebrow || $hero_title || $hero_desc ) : ?>
	<section class="finelevate-awards-hero">
		<div class="finelevate-container">
			<div class="finelevate-row">
				<div class="finelevate-awards-container">
					<?php if ( $hero_eyebrow ) : ?>
						<p class="finelevate-awards-eyebrow"><?php echo esc_html( $hero_eyebrow ); ?></p>
					<?php endif; ?>
					<?php if ( $hero_title ) : ?>
						<h1 class="finelevate-awards-hero__title"><?php echo esc_html( $hero_title ); ?></h1>
					<?php endif; ?>
					<?php if ( $hero_desc ) : ?>
						<p class="finelevate-awards-hero__desc"><?php echo esc_html( $hero_desc ); ?></p>
					<?php endif; ?>
					<?php if ( $hero_note ) : ?>
						<p class="finelevate-awards-hero__note"><?php echo esc_html( $hero_note ); ?></p>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<?php if ( $spon_label || $spon_text || $spon_logo ) : ?>
	<section class="finelevate-awards-sponsor">
		<div class="finelevate-container">
			<div class="finelevate-row">
				<div class="finelevate-awards-container">
					<?php if ( $spon_label ) : ?>
						<p class="finelevate-awards-sponsor__label"><?php echo esc_html( $spon_label ); ?></p>
					<?php endif; ?>
					<div class="finelevate-awards-sponsor__body">
						<?php if ( $spon_logo ) : ?>
							<img src="<?php echo esc_url( $spon_logo ); ?>"
								alt="<?php echo esc_attr( $spon_text ?: __( 'Sponsor', 'finelevate-awards' ) ); ?>"
								class="finelevate-awards-sponsor__logo">
						<?php endif; ?>
						<?php if ( $spon_text ) : ?>
							<span class="finelevate-awards-sponsor__text"><?php echo esc_html( $spon_text ); ?></span>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<?php if ( ! empty( $pillars ) ) : ?>
	<section class="finelevate-awards-pillars">
				<div class="finelevate-container">
			<div class="finelevate-row">
		<div class="finelevate-awards-container">
			<?php foreach ( $pillars as $pillar ) :
				$p_awards    = isset( $pillar['awards'] ) && is_array( $pillar['awards'] ) ? $pillar['awards'] : [];
				$p_title     = $pillar['title']     ?? '';
				$p_desc      = $pillar['desc']      ?? '';
				$p_badge     = $pillar['badge_url'] ?? '';
			?>
			<div class="finelevate-awards-pillar">

				<div class="finelevate-awards-pillar__header">
					<?php if ( $p_badge ) : ?>
						<img src="<?php echo esc_url( $p_badge ); ?>"
						     alt="<?php echo esc_attr( $p_title ); ?>"
						     class="finelevate-awards-pillar__badge">
					<?php endif; ?>
					<?php if ( $p_title ) : ?>
						<h2 class="finelevate-awards-pillar__title"><?php echo esc_html( $p_title ); ?></h2>
					<?php endif; ?>
					<?php if ( $p_desc ) : ?>
						<p class="finelevate-awards-pillar__desc"><?php echo esc_html( $p_desc ); ?></p>
					<?php endif; ?>
				</div>

				<?php if ( ! empty( $p_awards ) ) : ?>
				<div class="finelevate-awards-pillar__awards">
					<?php foreach ( $p_awards as $award ) :
						$a_title  = $award['title']     ?? '';
						$a_desc   = $award['desc']      ?? '';
						$a_badge  = $award['badge_url'] ?? '';
						$a_winner = trim( $award['winner'] ?? '' );
					?>
					<div class="finelevate-awards-award<?php echo $a_winner ? ' finelevate-awards-award--winner' : ''; ?>">
						<?php if ( $a_badge ) : ?>
							<img src="<?php echo esc_url( $a_badge ); ?>"
							     alt="<?php echo esc_attr( $a_title ); ?>"
							     class="finelevate-awards-award__badge">
						<?php endif; ?>
						<?php if ( $a_title ) : ?>
							<h3 class="finelevate-awards-award__title"><?php echo esc_html( $a_title ); ?></h3>
						<?php endif; ?>
						<?php if ( $a_desc ) : ?>
							<p class="finelevate-awards-award__desc"><?php echo esc_html( $a_desc ); ?></p>
						<?php endif; ?>
						<?php if ( $a_winner ) : ?>
							<div class="finelevate-awards-award__winner-box">
								<span class="finelevate-awards-award__winner-label">
									<?php esc_html_e( 'Winner', 'finelevate-awards' ); ?>
								</span>
								<span class="finelevate-awards-award__winner-name">
									<?php echo esc_html( $a_winner ); ?>
								</span>
							</div>
						<?php endif; ?>
					</div>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>

			</div>
			<?php endforeach; ?>
		</div>
		</div>
		</div>
	</section>
	<?php endif; ?>

	<?php if ( $winners_note ) : ?>
	<div class="finelevate-awards-winners-note">
		<div class="finelevate-awards-container">
			<p><?php echo esc_html( $winners_note ); ?></p>
		</div>
	</div>
	<?php endif; ?>

	<?php if ( $meth_title || $meth_sub || $meth_steps || $meth_blocks ) : ?>
	<section class="finelevate-awards-methodology">

					<div class="finelevate-container">
			<div class="finelevate-row">
		<div class="finelevate-awards-container">

			<?php if ( $meth_title ) : ?>
				<h2 class="finelevate-awards-methodology__title"><?php echo esc_html( $meth_title ); ?></h2>
			<?php endif; ?>
			<?php if ( $meth_sub ) : ?>
				<p class="finelevate-awards-methodology__sub"><?php echo esc_html( $meth_sub ); ?></p>
			<?php endif; ?>

			<?php if ( $meth_steps ) : ?>
			<div class="finelevate-awards-pipeline">
				<?php foreach ( $meth_steps as $step ) : ?>
					<div class="finelevate-awards-pipeline__step">
						<?php echo esc_html( $step ); ?>
					</div>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>

			<?php if ( $meth_blocks ) : ?>
			<div class="finelevate-awards-meth-blocks">
				<?php foreach ( $meth_blocks as $block ) :
					$b_title = $block['title'] ?? '';
					$b_desc  = $block['desc']  ?? '';
				?>
				<div class="finelevate-awards-meth-block">
					<?php if ( $b_title ) : ?>
						<h3 class="finelevate-awards-meth-block__title"><?php echo esc_html( $b_title ); ?></h3>
					<?php endif; ?>
					<?php if ( $b_desc ) : ?>
						<p class="finelevate-awards-meth-block__desc"><?php echo esc_html( $b_desc ); ?></p>
					<?php endif; ?>
				</div>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>

		</div>
		</div>
		</div>
	</section>
	<?php endif; ?>

</div>
