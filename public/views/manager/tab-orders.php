<?php if ( ! defined( 'WPINC' ) ) die; ?>
<h2><?php _e('Manage WooCommerce Orders', 'dancestudio-app'); ?></h2>
<p><?php _e('Full order management is handled in the main WordPress admin area for security and full functionality.', 'dancestudio-app'); ?></p>
<p><a href="<?php echo esc_url(admin_url('edit.php?post_type=shop_order')); ?>" class="button button-primary" target="_blank"><?php _e('Go to WooCommerce Orders', 'dancestudio-app'); ?></a></p>