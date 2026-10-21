<!doctype html>
<html <?php language_attributes(); ?>>
<head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width, initial-scale=1"><?php wp_head(); ?></head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main"><?php esc_html_e('Skip to content', 'finelevate-advisory'); ?></a>
<header class="site-header">
  <div class="header-inner">
    <a class="brand" href="<?php echo esc_url(home_url('/')); ?>"><img src="<?php echo esc_url(fe_asset('brand/Finelevate_monochrome_approved.svg')); ?>" width="250" height="48" alt="Finelevate360"></a>
    <nav class="desktop-nav" aria-label="<?php esc_attr_e('Primary navigation', 'finelevate-advisory'); ?>"><?php fe_navigation(); ?></nav>
    <button class="button contact header-contact" data-contact-open><?php echo esc_html(fe_value('contact_label')); ?></button>
    <button class="menu-toggle" data-menu-open aria-haspopup="dialog" aria-controls="mobile-menu"><?php esc_html_e('Menu', 'finelevate-advisory'); ?></button>
  </div>
</header>
<dialog id="mobile-menu" class="menu-dialog" aria-labelledby="mobile-menu-title">
  <div class="dialog-top"><h2 id="mobile-menu-title"><?php esc_html_e('Menu', 'finelevate-advisory'); ?></h2><button class="dialog-close" data-dialog-close aria-label="<?php esc_attr_e('Close menu', 'finelevate-advisory'); ?>">×</button></div>
  <nav aria-label="<?php esc_attr_e('Mobile navigation', 'finelevate-advisory'); ?>"><?php fe_navigation('primary', 'mobile'); ?></nav>
  <button class="button contact" data-contact-open><?php echo esc_html(fe_value('contact_label')); ?></button>
</dialog>
