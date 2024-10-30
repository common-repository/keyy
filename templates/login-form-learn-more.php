<?php if (!defined('KEYY_DIR')) die('No direct access.'); ?>

<div class="learn-more-slide-container">
	<h4 class="keyy-learn-more-text-header"><?php echo __('To set up the Keyy app', 'keyy'); ?></h4>
	<img class="keyy-intro-gif" src="<?php echo KEYY_URL.'/images/loading.gif'; ?>" data-src="<?php echo KEYY_URL.'/images/keyy-intro.gif'; ?>">
			<ul class="keyy-learn-more-text-list part-one">
			<li><?php echo __('Download and install the app on your phone', 'keyy'); ?></li>
		</ul>
		<div class="keyy-app-icon-container">
			<a href="<?php echo $android_app; ?>">
				<img class="keyy-app-store-image" src="<?php echo KEYY_URL.'/images/play-store-button.png'; ?>" width="120" height="40">
			</a>
			
			<a href="<?php echo $ios_app; ?>">
				<img class="keyy-app-store-image" src="<?php echo KEYY_URL.'/images/apple-store-button.png'; ?>" width="120" height="40">
			</a>
		</div> 
		<ul class="keyy-learn-more-text-list part-two">
			<li><?php echo __('Login to the site and go to the Keyy settings screen', 'keyy'); ?></li>
			<li><?php echo __('Scan the code to connect this site and use your phone for future logins', 'keyy'); ?></li>
		</ul>
		<div class="keyy-back-button">
			<p class="keyy-back"><?php echo __('Back', 'keyy'); ?></p>
		</div>
</div>
<?php

do_action('keyy_login_form_after_learn_more');