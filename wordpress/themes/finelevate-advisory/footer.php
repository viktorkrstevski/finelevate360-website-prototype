<footer class="site-footer section-shell">
  <div class="footer-main"><a class="brand" href="<?php echo esc_url(home_url('/')); ?>"><img src="<?php echo esc_url(fe_asset('brand/Finelevate_monochrome_approved.svg')); ?>" width="250" height="48" alt="Finelevate360"></a><p><?php echo esc_html(fe_value('footer_tagline')); ?></p><button class="text-link footer-contact" data-contact-open><?php echo esc_html(fe_value('contact_label')); ?></button></div>
  <nav aria-label="<?php esc_attr_e('Footer links', 'finelevate-advisory'); ?>"><?php fe_navigation('footer'); ?></nav>
</footer>
<dialog id="contact-dialog" class="contact-dialog" aria-labelledby="contact-title" aria-describedby="contact-description">
  <div class="dialog-top"><h2 id="contact-title"><?php echo esc_html(fe_value('contact_title')); ?></h2><button class="dialog-close" data-dialog-close aria-label="<?php esc_attr_e('Close', 'finelevate-advisory'); ?>">×</button></div>
  <p id="contact-description"><?php echo esc_html(fe_value('contact_description')); ?></p>
  <form id="partner-form" method="dialog" data-staging-contact>
    <label for="partner-name"><?php esc_html_e('Your name', 'finelevate-advisory'); ?></label><input id="partner-name" name="your-name" autocomplete="name" required maxlength="120">
    <label for="partner-email"><?php esc_html_e('Your email', 'finelevate-advisory'); ?></label><input id="partner-email" name="your-email" type="email" autocomplete="email" required maxlength="254">
    <label for="partner-subject"><?php esc_html_e('Subject', 'finelevate-advisory'); ?></label><input id="partner-subject" name="your-subject" required maxlength="200">
    <label for="partner-message"><?php esc_html_e('Your message', 'finelevate-advisory'); ?></label><textarea id="partner-message" name="your-message" rows="4" required maxlength="5000"></textarea>
    <button class="button contact" type="submit"><?php esc_html_e('Submit', 'finelevate-advisory'); ?></button>
    <p class="form-status" role="status" data-form-status data-message="<?php esc_attr_e('The form is valid. This staging preview does not send inquiries.', 'finelevate-advisory'); ?>"></p>
  </form>
</dialog>
<?php wp_footer(); ?>
</body></html>
