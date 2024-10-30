<?php if (!defined('KEYY_DIR')) die('No direct access.'); ?>

<div id="keyy-admin-header">

	<h1> <?php echo class_exists('Keyy_Login_Plugin_Premium') ? 'Keyy Premium' : 'Keyy'; ?> <?php if ('site-admin' != $which_page) echo ' - '.htmlspecialchars(__('Simple & secure logins with a wave of your phone', 'keyy')); ?><?php echo 'admin' == $which_page ? '' : ' - '.__('Site administration', 'keyy'); ?> - <?php echo Keyy_Login_Plugin::VERSION; ?></h1>

	<?php echo $home_page; ?> | 
	<a href="<?php echo $keyy_premium_shop;?>"><?php _e('Upgrade to more users', 'keyy'); ?></a> | 
	<a href="https://getkeyy.com/blog/"><?php _e('Blog', 'keyy'); ?></a> | 
	<a href="https://getkeyy.com/faqs/"><?php _e('FAQs', 'keyy'); ?></a> | 
	<a href="<?php echo $support; ?>"><?php _e('Support', 'keyy'); ?></a> | 
	<a href="https://getkeyy.com/my-account/"><?php _e('My Account', 'keyy'); ?></a> | 
	<!--<a href="https://updraftplus.com/newsletter-signup"><?php //_e('Newsletter', 'keyy'); ?></a> | -->
	<a href="https://nex.ist/"><?php _e("Nex.ist", 'keyy'); ?></a> | 
	<!--<a href="<?php /*echo $simba_plugins_landing; ?>"><?php _e('More plugins', 'keyy'); */?></a> | -->
	<a href="https://twitter.com/nexistHQ"><?php _e('Twitter', 'keyy'); ?></a>
	<br>
	
</div>

<?php
	$keyy_notices->do_notice();
	
	do_action('keyy_dashboard_header_after_notice');
