<?php
/**
 * Plugin Name: Finelevate - Title/Text/Image Widget
 * Description: Widget with Title, Text, Image (media upload), Link, and custom class injected into before_widget.
 * Version: 1.2.0
 * Author: Finelevate
 * License: GPLv2 or later
 * Text Domain: finelevate-tti-widget
 */

if (!defined('ABSPATH')) exit;

class Finelevate_Title_Text_Image_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'finelevate_title_text_image',
			__('Title / Text / Image (Custom)', 'finelevate-tti-widget'),
			['description' => __('Widget with title, text, image upload, link, and custom class.', 'finelevate-tti-widget')]
		);

		add_action('admin_enqueue_scripts', [$this, 'enqueue_media']);
	}

	/**
	 * Allow multiple classes separated by spaces.
	 */
	private function sanitize_classes($classes) {
		$classes = trim((string) $classes);
		if ($classes === '') return '';

		$tokens = preg_split('/\s+/', $classes);
		$tokens = array_filter(array_map('sanitize_html_class', $tokens));

		return trim(implode(' ', $tokens));
	}

	public function enqueue_media($hook) {
		if (!in_array($hook, ['widgets.php', 'customize.php'], true)) return;

		wp_enqueue_media();

		$handle = 'finelevate-tti-widget-admin';
		wp_register_script($handle, false, ['jquery'], '1.2.0', true);
		wp_enqueue_script($handle);

		wp_add_inline_script($handle, <<<'JS'
jQuery(function($){

	$(document).on('click', '.finelevate-upload-btn', function(e){
		e.preventDefault();

		var btn = $(this);

		var frame = wp.media({
			title: 'Select image',
			button: { text: 'Use this image' },
			multiple: false
		});

		frame.on('select', function(){
			var attachment = frame.state().get('selection').first().toJSON();
			var wrap = btn.closest('.finelevate-media-field');

			wrap.find('input.finelevate-image-id').val(attachment.id).trigger('change');
			wrap.find('img.finelevate-preview').attr('src', attachment.url).show();
			wrap.find('.finelevate-remove-btn').show();
		});

		frame.open();
	});

	$(document).on('click', '.finelevate-remove-btn', function(e){
		e.preventDefault();

		var wrap = $(this).closest('.finelevate-media-field');
		wrap.find('input.finelevate-image-id').val('').trigger('change');
		wrap.find('img.finelevate-preview').attr('src', '').hide();
		$(this).hide();
	});

});
JS
		);
	}

public function widget($args, $instance) {
	$title   = !empty($instance['title']) ? $instance['title'] : '';
	$text    = !empty($instance['text']) ? $instance['text'] : '';
	$imageId = !empty($instance['image_id']) ? (int) $instance['image_id'] : 0;

	$imageUrl = $imageId ? wp_get_attachment_image_url($imageId, 'large') : '';

	echo $args['before_widget'];
	?>

	<?php if ($title || $text) : ?>
		<div class="finelevate-widget-content">
			<?php if ($title) : ?>
				<?php echo $args['before_title'] . esc_html($title) . $args['after_title']; ?>
			<?php endif; ?>

			<?php if ($text) : ?>
				<div class="finelevate-widget-text">
					<?php echo wp_kses_post(wpautop($text)); ?>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ($imageUrl) : ?>
		<div class="finelevate-widget-image">
			<img src="<?php echo esc_url($imageUrl); ?>" alt="<?php echo esc_attr($title); ?>">
		</div>
	<?php endif; ?>

	<?php
	echo $args['after_widget'];
}


	public function form($instance) {
		$title   = $instance['title'] ?? '';
		$text    = $instance['text'] ?? '';
		$imageId = $instance['image_id'] ?? '';
		$link_id = isset($instance['link_id']) ? (int) $instance['link_id'] : 0;
		$class   = $instance['class'] ?? '';

		$preview = $imageId ? wp_get_attachment_image_url((int) $imageId, 'medium') : '';
		?>

		<p>
			<label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Title:', 'finelevate-tti-widget'); ?></label>
			<input class="widefat"
				id="<?php echo esc_attr($this->get_field_id('title')); ?>"
				name="<?php echo esc_attr($this->get_field_name('title')); ?>"
				type="text"
				value="<?php echo esc_attr($title); ?>">
		</p>

		<p>
			<label for="<?php echo esc_attr($this->get_field_id('text')); ?>"><?php _e('Text:', 'finelevate-tti-widget'); ?></label>
			<textarea class="widefat" rows="5"
				id="<?php echo esc_attr($this->get_field_id('text')); ?>"
				name="<?php echo esc_attr($this->get_field_name('text')); ?>"><?php echo esc_textarea($text); ?></textarea>
		</p>

		<?php
		$items = get_posts([
			'post_type'      => ['page', 'post'],
			'post_status'    => 'publish',
			'posts_per_page' => 200,
			'orderby'        => 'title',
			'order'          => 'ASC',
		]);
		?>

		<p>
			<label for="<?php echo esc_attr($this->get_field_id('link_id')); ?>">
				<?php _e('Link to (Page/Post):', 'finelevate-tti-widget'); ?>
			</label>

			<select class="widefat"
				id="<?php echo esc_attr($this->get_field_id('link_id')); ?>"
				name="<?php echo esc_attr($this->get_field_name('link_id')); ?>">

				<option value="0"><?php _e('— No link —', 'finelevate-tti-widget'); ?></option>

				<?php
				$pages = array_filter($items, fn($p) => $p->post_type === 'page');
				$posts = array_filter($items, fn($p) => $p->post_type === 'post');

				if ($pages) {
					echo '<optgroup label="' . esc_attr__('Pages', 'finelevate-tti-widget') . '">';
					foreach ($pages as $p) {
						printf(
							'<option value="%d" %s>%s</option>',
							(int) $p->ID,
							selected($link_id, (int) $p->ID, false),
							esc_html($p->post_title)
						);
					}
					echo '</optgroup>';
				}

				if ($posts) {
					echo '<optgroup label="' . esc_attr__('Posts', 'finelevate-tti-widget') . '">';
					foreach ($posts as $p) {
						printf(
							'<option value="%d" %s>%s</option>',
							(int) $p->ID,
							selected($link_id, (int) $p->ID, false),
							esc_html($p->post_title)
						);
					}
					echo '</optgroup>';
				}
				?>
			</select>

			<small style="display:block;margin-top:6px;opacity:.8;">
				<?php _e('Select a published page/post. (Shows up to 200 items.)', 'finelevate-tti-widget'); ?>
			</small>
		</p>


		<p>
			<label for="<?php echo esc_attr($this->get_field_id('class')); ?>"><?php _e('Custom CSS class(es):', 'finelevate-tti-widget'); ?></label>
			<input class="widefat"
				id="<?php echo esc_attr($this->get_field_id('class')); ?>"
				name="<?php echo esc_attr($this->get_field_name('class')); ?>"
				type="text"
				value="<?php echo esc_attr($class); ?>"
				placeholder="hero-widget dark align-center">
		</p>

		<div class="finelevate-media-field" style="margin:12px 0;">
			<label><?php _e('Image:', 'finelevate-tti-widget'); ?></label><br>

			<input class="finelevate-image-id"
				id="<?php echo esc_attr($this->get_field_id('image_id')); ?>"
				name="<?php echo esc_attr($this->get_field_name('image_id')); ?>"
				type="hidden"
				value="<?php echo esc_attr($imageId); ?>">

			<img class="finelevate-preview"
				src="<?php echo esc_url($preview); ?>"
				style="max-width:100%; height:auto; display:<?php echo $preview ? 'block' : 'none'; ?>; margin:8px 0;">

			<p style="margin:0;">
				<button type="button" class="button finelevate-upload-btn"><?php _e('Upload / Select Image', 'finelevate-tti-widget'); ?></button>
				<button type="button" class="button finelevate-remove-btn" style="display:<?php echo $preview ? 'inline-block' : 'none'; ?>;">
					<?php _e('Remove', 'finelevate-tti-widget'); ?>
				</button>
			</p>
		</div>

		<?php
	}

	public function update($new_instance, $old_instance) {
		$instance = [];
		$instance['title']    = sanitize_text_field($new_instance['title'] ?? '');
		$instance['text']     = wp_kses_post($new_instance['text'] ?? '');
		$instance['image_id'] = isset($new_instance['image_id']) ? (int) $new_instance['image_id'] : 0;
		$instance['link_id']  = isset($new_instance['link_id']) ? (int) $new_instance['link_id'] : 0;
		$instance['class']    = $this->sanitize_classes($new_instance['class'] ?? '');
		return $instance;
	}
}

/**
 * ✅ Inject widget instance "class" into sidebar wrapper (before_widget).
 * Result:
 * <div class="finelevate-home-widget YOUR-CLASS-HERE"> ... </div>
 */
add_filter('dynamic_sidebar_params', function ($params) {

	if (empty($params[0]['widget_id'])) return $params;

	global $wp_registered_widgets;

	$widget_id = $params[0]['widget_id'];

	if (empty($wp_registered_widgets[$widget_id]['callback'])) return $params;

	$cb = $wp_registered_widgets[$widget_id]['callback'];

	// callback: [ $widget_object, 'widget' ]
	if (!is_array($cb) || !is_object($cb[0])) return $params;

	$widget_obj = $cb[0];

	// Only apply to OUR widget
	if (!($widget_obj instanceof Finelevate_Title_Text_Image_Widget)) return $params;

	// instance number from "finelevate_title_text_image-2"
	if (!preg_match('/-(\d+)$/', $widget_id, $m)) return $params;
	$number = (int) $m[1];

	$all_instances = get_option($widget_obj->option_name);
	if (empty($all_instances) || empty($all_instances[$number])) return $params;

	$instance = $all_instances[$number];

	// Ensure before_widget has class=""
	if (strpos($params[0]['before_widget'], 'class=') === false) {
		$params[0]['before_widget'] = preg_replace('/^<(\w+)/', '<$1 class=""', $params[0]['before_widget'], 1);
	}

	// 1) Inject custom class into before_widget
	if (!empty($instance['class'])) {
		$custom = esc_attr(trim((string) $instance['class']));
		if ($custom !== '') {
			$params[0]['before_widget'] = preg_replace(
				'/class="([^"]*)"/',
				'class="$1 ' . $custom . '"',
				$params[0]['before_widget'],
				1
			);
		}
	}

	// 2) ALWAYS add <a> wrapper
	$link_id = !empty($instance['link_id']) ? (int) $instance['link_id'] : 0;
	$link    = $link_id ? get_permalink($link_id) : '';
	$link    = $link ? esc_url($link) : '';


	if ($link) {
		$a = '<a class="finelevate-widget-link" href="' . $link . '">';
	} else {
		// "Disabled" link (still an <a>, but not focusable/clickable)
		$a = '<a class="finelevate-widget-link is-disabled" href="#" aria-disabled="true" tabindex="-1">';
	}

	$params[0]['before_widget'] .= $a;
	$params[0]['after_widget']  = '</a>' . $params[0]['after_widget'];

	return $params;
}, 20);



add_action('widgets_init', function () {
	register_widget('Finelevate_Title_Text_Image_Widget');
});
