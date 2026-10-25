<?php
/**
 * Plugin Name: Events Custom Post Type
 * Description: Кастом пост тип за евенти со метаполиња и shortcode за почетна страница
 * Version: 1.0
 * Author: Custom Dev
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ============================================================
// 1. РЕГИСТРИРАЊЕ НА КАСТОМ POST TYPE
// ============================================================

function events_register_post_type() {
    $labels = array(
        'name'               => 'Events',
        'singular_name'      => 'Event',
        'menu_name'          => 'Events',
        'add_new'            => 'Додај нов',
        'add_new_item'       => 'Додај нов евент',
        'edit_item'          => 'Уреди евент',
        'new_item'           => 'Нов евент',
        'view_item'          => 'Погледај евент',
        'search_items'       => 'Пребарај евенти',
        'not_found'          => 'Нема пронајдени евенти',
        'not_found_in_trash' => 'Нема евенти во корпа',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => true,
        'rewrite'            => array( 'slug' => 'events' ),
        'menu_icon'          => 'dashicons-calendar-alt',
        'menu_position'      => 5,
        'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'show_in_rest'       => true,
    );

    register_post_type( 'event', $args );
}
add_action( 'init', 'events_register_post_type' );


// ============================================================
// 2. META BOXES — полиња за евентот
// ============================================================

function events_add_meta_boxes() {
    add_meta_box(
        'event_details',
        'Детали за евентот',
        'events_meta_box_callback',
        'event',
        'normal',
        'high'
    );

    add_meta_box(
        'event_agenda',
        'Агенда на настанот',
        'events_agenda_meta_box_callback',
        'event',
        'normal',
        'default'
    );

    add_meta_box(
        'event_sponsors',
        'Спонзори',
        'events_sponsors_meta_box_callback',
        'event',
        'side',
        'default'
    );
}
add_action( 'add_meta_boxes', 'events_add_meta_boxes' );


// ---- Callback: Детали за евентот ----
function events_meta_box_callback( $post ) {
    wp_nonce_field( 'events_save_meta', 'events_nonce' );

    $day      = get_post_meta( $post->ID, '_event_day',      true );
    $month    = get_post_meta( $post->ID, '_event_month',    true );
    $year     = get_post_meta( $post->ID, '_event_year',     true );
    $location = get_post_meta( $post->ID, '_event_location', true );
    $short_desc = get_post_meta( $post->ID, '_event_short_desc', true );

    $months = array(
        '01' => 'Јануари', '02' => 'Февруари', '03' => 'Март',
        '04' => 'Април',   '05' => 'Мај',      '06' => 'Јуни',
        '07' => 'Јули',    '08' => 'Август',   '09' => 'Септември',
        '10' => 'Октомври','11' => 'Ноември',  '12' => 'Декември',
    );
    ?>
    <style>
        .event-meta-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 15px; }
        .event-meta-field label { display: block; font-weight: 600; margin-bottom: 4px; }
        .event-meta-field input,
        .event-meta-field select,
        .event-meta-field textarea { width: 100%; }
        .event-meta-full { margin-bottom: 15px; }
        .event-meta-full label { display: block; font-weight: 600; margin-bottom: 4px; }
    </style>

    <div class="event-meta-grid">
        <div class="event-meta-field">
            <label for="event_day">Ден</label>
            <input type="number" id="event_day" name="event_day"
                   value="<?php echo esc_attr( $day ); ?>"
                   min="1" max="31" placeholder="пр. 15" class="widefat">
        </div>
        <div class="event-meta-field">
            <label for="event_month">Месец</label>
            <select id="event_month" name="event_month" class="widefat">
                <option value="">-- Избери месец --</option>
                <?php foreach ( $months as $val => $label ) : ?>
                    <option value="<?php echo $val; ?>" <?php selected( $month, $val ); ?>>
                        <?php echo $label; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="event-meta-field">
            <label for="event_year">Година</label>
            <input type="number" id="event_year" name="event_year"
                   value="<?php echo esc_attr( $year ); ?>"
                   min="2000" max="2099" placeholder="пр. 2025" class="widefat">
        </div>
    </div>

    <div class="event-meta-full">
        <label for="event_location">📍 Локација</label>
        <input type="text" id="event_location" name="event_location"
               value="<?php echo esc_attr( $location ); ?>"
               placeholder="пр. Скопје, МКЦ" class="widefat">
    </div>

    <div class="event-meta-full">
        <label for="event_short_desc">Краток опис (за shortcode на почетна)</label>
        <textarea id="event_short_desc" name="event_short_desc"
                  rows="3" class="widefat"
                  placeholder="Краток опис кој ќе се прикажува на почетната страница..."><?php echo esc_textarea( $short_desc ); ?></textarea>
    </div>
    <?php
}


// ---- Callback: Агенда ----
function events_agenda_meta_box_callback( $post ) {
    $agenda_items = get_post_meta( $post->ID, '_event_agenda', true );
    if ( ! is_array( $agenda_items ) ) {
        $agenda_items = array();
    }
    ?>
    <style>
        #agenda-list .agenda-row { display: flex; gap: 10px; margin-bottom: 8px; align-items: center; }
        #agenda-list .agenda-row input { flex: 1; }
        #agenda-list .agenda-time { max-width: 100px; }
        .remove-agenda { background: #dc3232; color: #fff; border: none; cursor: pointer; border-radius: 3px; padding: 4px 8px; }
        #add-agenda-row { margin-top: 8px; }
    </style>

    <p><strong>Додај точки во агендата (час + опис):</strong></p>
    <div id="agenda-list">
        <?php if ( ! empty( $agenda_items ) ) : ?>
            <?php foreach ( $agenda_items as $item ) : ?>
            <div class="agenda-row">
                <input type="text" name="agenda_time[]"
                       value="<?php echo esc_attr( $item['time'] ); ?>"
                       placeholder="09:00" class="agenda-time">
                <input type="text" name="agenda_title[]"
                       value="<?php echo esc_attr( $item['title'] ); ?>"
                       placeholder="Опис на активноста...">
                <button type="button" class="remove-agenda">✕</button>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <button type="button" id="add-agenda-row" class="button">+ Додај ред во агенда</button>

    <script>
    document.getElementById('add-agenda-row').addEventListener('click', function() {
        var list = document.getElementById('agenda-list');
        var row = document.createElement('div');
        row.className = 'agenda-row';
        row.innerHTML = '<input type="text" name="agenda_time[]" placeholder="09:00" class="agenda-time">' +
                        '<input type="text" name="agenda_title[]" placeholder="Опис на активноста...">' +
                        '<button type="button" class="remove-agenda">✕</button>';
        list.appendChild(row);
    });
    document.getElementById('agenda-list').addEventListener('click', function(e) {
        if ( e.target.classList.contains('remove-agenda') ) {
            e.target.parentElement.remove();
        }
    });
    </script>
    <?php
}


// ---- Callback: Спонзори ----
function events_sponsors_meta_box_callback( $post ) {
    $sponsors = get_post_meta( $post->ID, '_event_sponsors', true );
    if ( ! is_array( $sponsors ) ) {
        $sponsors = array();
    }
    ?>
    <style>
        .sponsor-row { margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid #eee; }
        .sponsor-row label { display: block; font-size: 11px; color: #666; margin-bottom: 3px; }
        .sponsor-row input { width: 100%; margin-bottom: 4px; }
        .sponsor-preview img { max-width: 100%; height: 50px; object-fit: contain; display: block; margin-top: 4px; }
        .remove-sponsor { background: #dc3232; color: #fff; border: none; cursor: pointer; border-radius: 3px; padding: 3px 7px; font-size: 11px; }
        #add-sponsor { margin-top: 8px; width: 100%; }
    </style>

    <div id="sponsor-list">
        <?php if ( ! empty( $sponsors ) ) : ?>
            <?php foreach ( $sponsors as $i => $sp ) : ?>
            <div class="sponsor-row">
                <label>Назив на спонзор</label>
                <input type="text" name="sponsor_name[]"
                       value="<?php echo esc_attr( $sp['name'] ); ?>"
                       placeholder="Назив на спонзор">
                <label>URL на логото (слика)</label>
                <input type="text" name="sponsor_logo[]"
                       value="<?php echo esc_attr( $sp['logo'] ); ?>"
                       placeholder="https://...">
                <?php if ( ! empty( $sp['logo'] ) ) : ?>
                <div class="sponsor-preview">
                    <img src="<?php echo esc_url( $sp['logo'] ); ?>" alt="">
                </div>
                <?php endif; ?>
                <label>Линк на спонзорот</label>
                <input type="text" name="sponsor_url[]"
                       value="<?php echo esc_attr( $sp['url'] ); ?>"
                       placeholder="https://...">
                <button type="button" class="remove-sponsor">Отстрани</button>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <button type="button" id="add-sponsor" class="button">+ Додај спонзор</button>

    <script>
    document.getElementById('add-sponsor').addEventListener('click', function() {
        var list = document.getElementById('sponsor-list');
        var row = document.createElement('div');
        row.className = 'sponsor-row';
        row.innerHTML = '<label>Назив на спонзор</label>' +
                        '<input type="text" name="sponsor_name[]" placeholder="Назив на спонзор">' +
                        '<label>URL на логото (слика)</label>' +
                        '<input type="text" name="sponsor_logo[]" placeholder="https://...">' +
                        '<label>Линк на спонзорот</label>' +
                        '<input type="text" name="sponsor_url[]" placeholder="https://...">' +
                        '<button type="button" class="remove-sponsor">Отстрани</button>';
        list.appendChild(row);
    });
    document.getElementById('sponsor-list').addEventListener('click', function(e) {
        if ( e.target.classList.contains('remove-sponsor') ) {
            e.target.parentElement.remove();
        }
    });
    </script>
    <?php
}


// ============================================================
// 3. ЗАЧУВУВАЊЕ НА META ПОДАТОЦИТЕ
// ============================================================

function events_save_meta( $post_id ) {
    // Провери nonce
    if ( ! isset( $_POST['events_nonce'] ) ||
         ! wp_verify_nonce( $_POST['events_nonce'], 'events_save_meta' ) ) {
        return;
    }

    // Автосними – прескокни
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

    // Провери привилегии
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    // --- Детали ---
    $fields = array( 'event_day', 'event_month', 'event_year', 'event_location', 'event_short_desc' );
    foreach ( $fields as $field ) {
        if ( isset( $_POST[ $field ] ) ) {
            update_post_meta( $post_id, '_' . $field, sanitize_text_field( $_POST[ $field ] ) );
        }
    }

    // --- Агенда ---
    $agenda = array();
    if ( isset( $_POST['agenda_time'] ) && is_array( $_POST['agenda_time'] ) ) {
        foreach ( $_POST['agenda_time'] as $i => $time ) {
            $title = isset( $_POST['agenda_title'][ $i ] ) ? $_POST['agenda_title'][ $i ] : '';
            if ( ! empty( $time ) || ! empty( $title ) ) {
                $agenda[] = array(
                    'time'  => sanitize_text_field( $time ),
                    'title' => sanitize_text_field( $title ),
                );
            }
        }
    }
    update_post_meta( $post_id, '_event_agenda', $agenda );

    // --- Спонзори ---
    $sponsors = array();
    if ( isset( $_POST['sponsor_name'] ) && is_array( $_POST['sponsor_name'] ) ) {
        foreach ( $_POST['sponsor_name'] as $i => $name ) {
            $logo = isset( $_POST['sponsor_logo'][ $i ] ) ? $_POST['sponsor_logo'][ $i ] : '';
            $url  = isset( $_POST['sponsor_url'][ $i ] )  ? $_POST['sponsor_url'][ $i ]  : '';
            if ( ! empty( $name ) ) {
                $sponsors[] = array(
                    'name' => sanitize_text_field( $name ),
                    'logo' => esc_url_raw( $logo ),
                    'url'  => esc_url_raw( $url ),
                );
            }
        }
    }
    update_post_meta( $post_id, '_event_sponsors', $sponsors );
}
add_action( 'save_post_event', 'events_save_meta' );


// ============================================================
// 4. SHORTCODE — [upcoming_events] за почетна страница
// ============================================================

/**
 * Употреба: [upcoming_events count="3" show_sponsors="yes"]
 *
 * Параметри:
 *   count          — број на евенти (default: 3)
 *   show_sponsors  — yes/no (default: no)
 */
function events_upcoming_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'count'         => 3,
        'show_sponsors' => 'no',
    ), $atts, 'upcoming_events' );

    $query = new WP_Query( array(
        'post_type'      => 'event',
        'posts_per_page' => intval( $atts['count'] ),
        'post_status'    => 'publish',
        'meta_key'       => '_event_year',
        'orderby'        => 'meta_value_num',
        'order'          => 'ASC',
    ) );

    if ( ! $query->have_posts() ) {
        return '<p class="no-events">Нема претстојни евенти.</p>';
    }

    $months_mk = array(
        '01' => __('Jan', 'events-cpt'),
        '02' => __('Feb', 'events-cpt'),
        '03' => __('Mar', 'events-cpt'),
        '04' => __('Apr', 'events-cpt'),
        '05' => __('May', 'events-cpt'),
        '06' => __('Jun', 'events-cpt'),
        '07' => __('Jul', 'events-cpt'),
        '08' => __('Aug', 'events-cpt'),
        '09' => __('Sep', 'events-cpt'),
        '10' => __('Oct', 'events-cpt'),
        '11' => __('Nov', 'events-cpt'),
        '12' => __('Dec', 'events-cpt'),
    );


    ob_start();
    ?>
    <div class="finelevate-upcoming-events">
        <?php while ( $query->have_posts() ) : $query->the_post(); ?>
            <?php
            $day       = get_post_meta( get_the_ID(), '_event_day',       true );
            $month     = get_post_meta( get_the_ID(), '_event_month',     true );
            $year      = get_post_meta( get_the_ID(), '_event_year',      true );
            $location  = get_post_meta( get_the_ID(), '_event_location',  true );
            $short_desc = get_post_meta( get_the_ID(), '_event_short_desc', true );
            $sponsors  = get_post_meta( get_the_ID(), '_event_sponsors',  true );
            $month_label = isset( $months_mk[ $month ] ) ? $months_mk[ $month ] : '';
            ?>
            <div class="finelevate-event-item">
<?php /* if ( has_post_thumbnail() ) : ?>
<div class="finelevate-event-item-thumb">
    <a href="<?php the_permalink(); ?>">
        <?php the_post_thumbnail( 'medium' ); ?>
    </a>
</div>
<?php endif; */ ?>

                <div class="finelevate-event-item-wrap">
                    <div class="finelevate-event-item-description">
                        <h3 class="finelevate-event-item-description-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h3>
                    
                        <?php if ( $short_desc ) : ?>
                        <p class="finelevate-event-item-description-content"><?php echo esc_html( $short_desc ); ?></p>
                        <?php endif; ?>
                    </div>

                    <?php if ( $day || $month_label || $year ) : ?>
                    <div class="finelevate-event-item-date-location">
                        
                        <div class="finelevate-event-item-date-location-container">
                            <div class="finelevate-event-item-date-location-icon">
                                <span class="material-symbols-outlined">calendar_month</span>
                            </div>

                            <div class="finelevate-event-date-title">
                                <span class="finelevate-event-subtitle"><?php echo esc_html__( 'When', 'events-cpt' ); ?></span>

                                <div class="finelevate-event-date-thedate">
                                    <?php if ( $day ) echo '<span class="event-day">' . esc_html( $day ) . '</span>'; ?>
                                    <?php if ( $month_label ) echo '<span class="event-month">' . esc_html( $month_label ) . '</span>'; ?>
                                    <?php if ( $year ) echo '<span class="event-year">' . esc_html( $year ) . '</span>'; ?>
                                </div>
                            </div>
                        </div>
                    <?php if ( $location ) : ?>
                    <div class="finelevate-event-item-date-location-container"> 
                        <div class="finelevate-event-item-date-location-icon">
                            <span class="material-symbols-outlined">move_location</span>
                        </div>
                        <div class="finelevate-event-location-title">
                            <span class="finelevate-event-subtitle"><?php echo esc_html__( 'Where', 'events-cpt' ); ?></span>
                            <span><?php echo esc_html( $location ); ?></span>
                        </div>

                    </div>
                    <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    



                    <!-- <a href="<?php the_permalink(); ?>" class="finelevate-event-item__btn">Повеќе →</a> -->

                </div><!-- .finelevate-event-item-wrap -->
                <?php if ( $atts['show_sponsors'] === 'yes' && ! empty( $sponsors ) ) : ?>
                    <div class="finelevate-event-sponsors">
                        <!-- <span class="finelevate-sponsors-label"><?php echo esc_html__('Главен спонзор:', 'finelevate'); ?></span> -->
                        <?php foreach ( $sponsors as $sp ) : ?>
                            <?php if ( ! empty( $sp['logo'] ) ) : ?>
                            <a href="<?php echo esc_url( $sp['url'] ); ?>" target="_blank" rel="noopener" title="<?php echo esc_attr( $sp['name'] ); ?>">
                                <img src="<?php echo esc_url( $sp['logo'] ); ?>" alt="<?php echo esc_attr( $sp['name'] ); ?>">
                            </a>
                            <?php else : ?>
                            <span class="finelevate-sponsor-text-name"><?php echo esc_html( $sp['name'] ); ?></span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
				<a href="<?php echo esc_url( get_permalink() ); ?>" class="event-details-button">
    <?php esc_html_e( 'More info', 'finelevate' ); ?>
</a>
                   
				
            </div><!-- .finelevate-event-item -->
        <?php endwhile; wp_reset_postdata(); ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'upcoming_events', 'events_upcoming_shortcode' );





// ============================================================
// 6. ARCHIVE + SINGLE PAGE TEMPLATES
// ============================================================

function events_archive_template( $template ) {
    if ( is_post_type_archive( 'event' ) ) {
        $theme_template = locate_template( 'archive-event.php' );
        if ( $theme_template ) return $theme_template;
        $plugin_template = plugin_dir_path( __FILE__ ) . 'templates/archive-event.php';
        if ( file_exists( $plugin_template ) ) return $plugin_template;
    }
    return $template;
}
add_filter( 'template_include', 'events_archive_template' );

/**
 * Користи plugin-based template за single event.
 * Ако темата има свој single-event.php, тој има приоритет.
 */
function events_single_template( $template ) {
    if ( is_singular( 'event' ) ) {
        $theme_template = locate_template( 'single-event.php' );
        if ( $theme_template ) {
            return $theme_template;
        }
        $plugin_template = plugin_dir_path( __FILE__ ) . 'templates/single-event.php';
        if ( file_exists( $plugin_template ) ) {
            return $plugin_template;
        }
    }
    return $template;
}
add_filter( 'template_include', 'events_single_template' );


// ============================================================
// 7. CSS ЗА SINGLE PAGE (inline)
// ============================================================




// ============================================================
// 8. FLUSH REWRITE RULES при активација
// ============================================================

function events_activate() {
    events_register_post_type();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'events_activate' );

function events_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'events_deactivate' );


// ============================================================
// 9. КРЕИРАЈ ГО TEMPLATES ДИРЕКТОРИУМОТ при активација
// ============================================================

function events_create_template_dir() {
    $dir = plugin_dir_path( __FILE__ ) . 'templates';
    if ( ! file_exists( $dir ) ) {
        wp_mkdir_p( $dir );
    }
}
add_action( 'init', 'events_create_template_dir' );