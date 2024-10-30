<?php

if (!defined('KEYY_DIR')) die('Security check');

$common_urls = Keyy_Login_Plugin()->get_common_urls();

return array(
	'role' => __('Role', 'keyy'),
	'delete' => __('Delete', 'keyy'),
	'use_this_policy' => __('use this policy', 'keyy'),
	'error_unexpected_response' => __('An unexpected response was received.', 'keyy'),
	'replacing' => __('The token has expired; a replacement is being retrieved...', 'keyy'),
	'refresh' => __('An error occurred. Please refresh the page and try again.', 'keyy'),
	'login_form_not_found' => __('The login form was not found. Please try again.', 'keyy'),
	'disabled' => __("Keyy has been disabled. Only WordPress's default login mechanisms (and any others from other plugins) are active.", 'keyy'),
	'authorised_needs_password' => __('An authorised login request from your Keyy app has been received. Please now enter your password to complete login.', 'keyy'),
	'choose_valid_user' => __('You must first choose a valid user.', 'keyy'),
	'user_must_provide_password' => __('The user must use a password', 'keyy'),
	'user_must_provide_keyy' => __('The user must scan with Keyy', 'keyy'),
	'user_must_provide_both' => __('The user must both use a password and scan with Keyy', 'keyy'),
	'emails_status' => __('Connection code sent successfully to %s user(s). Sending emails failed for %s user(s).', 'keyy'),
	'emails_continue' => __('The next batch will now be sent.', 'keyy'),
	'emails_complete' => __('All emails in the queue were processed.', 'keyy'),
	'switch_to_qr' => __('Keyy Wave', 'keyy'),
	'switch_to_wave' => __('QR Code', 'keyy'),
	'login_form_learn_more' => __('Learn more', 'keyy'),
	'wp_form_header' => __("Don't have Keyy setup? Login below"),
);
