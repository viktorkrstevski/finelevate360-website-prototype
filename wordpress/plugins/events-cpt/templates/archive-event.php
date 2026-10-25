<?php
/**
 * Template: Archive Event
 * Место: /wp-content/plugins/events-cpt/templates/archive-event.php
 */

get_header();

$months_mk = array(
    '01' => 'Јан', '02' => 'Фев', '03' => 'Мар',
    '04' => 'Апр', '05' => 'Мај', '06' => 'Јун',
    '07' => 'Јул', '08' => 'Авг', '09' => 'Сеп',
    '10' => 'Окт', '11' => 'Ное', '12' => 'Дек',
);
?>

<div class="events-archive">

    <div class="events-archive__header">
        <h1 class="events-archive__title">
            <?php post_type_archive_title(); ?>
        </h1>
        <?php if ( get_the_archive_description() ) : ?>
        <div class="events-archive__desc"><?php the_archive_description(); ?></div>
        <?php endif; ?>
    </div>

    <?php if ( have_posts() ) : ?>

    <div class="events-archive__grid">
        <?php while ( have_posts() ) : the_post();
            $pid         = get_the_ID();
            $day         = get_post_meta( $pid, '_event_day',      true );
            $month       = get_post_meta( $pid, '_event_month',    true );
            $year        = get_post_meta( $pid, '_event_year',     true );
            $location    = get_post_meta( $pid, '_event_location', true );
            $short_desc  = get_post_meta( $pid, '_event_short_desc', true );
            $sponsors    = get_post_meta( $pid, '_event_sponsors', true );
            $month_label = isset( $months_mk[ $month ] ) ? $months_mk[ $month ] : '';
        ?>
        <article id="event-<?php echo $pid; ?>" class="event-arc-card">

            <!-- Thumbnail -->
            <?php if ( has_post_thumbnail() ) : ?>
            <div class="event-arc-card__thumb">
                <a href="<?php the_permalink(); ?>">
                    <?php the_post_thumbnail( 'medium_large' ); ?>
                </a>
                <?php if ( $day || $month_label || $year ) : ?>
                <div class="event-arc-card__date-badge">
                    <?php if ( $day ) echo '<span class="badge-day">' . esc_html( $day ) . '</span>'; ?>
                    <?php if ( $month_label ) echo '<span class="badge-month">' . esc_html( $month_label ) . '</span>'; ?>
                    <?php if ( $year ) echo '<span class="badge-year">' . esc_html( $year ) . '</span>'; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php else : ?>
            <!-- No image — date block on the left -->
            <?php if ( $day || $month_label || $year ) : ?>
            <div class="event-arc-card__date-block">
                <?php if ( $day ) echo '<span class="block-day">' . esc_html( $day ) . '</span>'; ?>
                <?php if ( $month_label ) echo '<span class="block-month">' . esc_html( $month_label ) . '</span>'; ?>
                <?php if ( $year ) echo '<span class="block-year">' . esc_html( $year ) . '</span>'; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>

            <!-- Body -->
            <div class="event-arc-card__body">
                <h2 class="event-arc-card__title">
                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </h2>

                <?php if ( $location ) : ?>
                <p class="event-arc-card__location">&#128205; <?php echo esc_html( $location ); ?></p>
                <?php endif; ?>

                <?php if ( $short_desc ) : ?>
                <p class="event-arc-card__desc"><?php echo esc_html( $short_desc ); ?></p>
                <?php elseif ( has_excerpt() ) : ?>
                <p class="event-arc-card__desc"><?php echo wp_trim_words( get_the_excerpt(), 20 ); ?></p>
                <?php endif; ?>

                <div class="event-arc-card__footer">
                    <a href="<?php the_permalink(); ?>" class="event-arc-card__btn">Повеќе &#8594;</a>

                    <?php if ( ! empty( $sponsors ) && is_array( $sponsors ) ) : ?>
                    <div class="event-arc-card__sponsors">
                        <?php foreach ( $sponsors as $sp ) :
                            if ( empty( $sp['logo'] ) ) continue;
                        ?>
                        <a href="<?php echo ! empty( $sp['url'] ) ? esc_url( $sp['url'] ) : '#'; ?>"
                           target="_blank" rel="noopener" title="<?php echo esc_attr( $sp['name'] ); ?>">
                            <img src="<?php echo esc_url( $sp['logo'] ); ?>" alt="<?php echo esc_attr( $sp['name'] ); ?>">
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </article>
        <?php endwhile; ?>
    </div><!-- .events-archive__grid -->

    <!-- Pagination -->
    <div class="events-archive__pagination">
        <?php
        the_posts_pagination( array(
            'mid_size'  => 2,
            'prev_text' => '&#8592; Претходна',
            'next_text' => 'Следна &#8594;',
        ) );
        ?>
    </div>

    <?php else : ?>
    <p class="events-archive__empty">Нема евенти за прикажување.</p>
    <?php endif; ?>

</div><!-- .events-archive -->

<?php get_footer(); ?>
