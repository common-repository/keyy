jQuery(document).ready(function($) {
	
	var i = 0;
	
	// Inject into any relevant forms.
	$.each(keyy.hook_forms, function(index, data) {
		var selector = data.selector;
		
		if ($(selector).length < 1) { return; }

		$(selector).each(function(j) {
			keyy_inject_code_into_form(this, i + 1);
			i++;
			$("#login p#nav>a").hide();
		});
		
		if (!keyy.is_disabled && data.hasOwnProperty('hide') && data.hide) {
			$(data.hide).hide();
		}

		if (!keyy.is_disabled && data.hasOwnProperty('styles')) {
			$.each(data.styles, function(classes, rule) {
				var style = rule.split(",");
				 $(classes).css(style[0], style[1]);
			 });
		}
	});


	$('form').on('click', '.keyy_qrcode_container .keyy_wave_to_qrcode, .keyy_qrcode_container .qr-label', function(e) {
		e.preventDefault();
		
		Keyy.set_keyy_code_type('qr');
		var qr_url = keyy.qr_url + '?context=' + keyy.context + '&token=' + keyy.login_token.value;

		$('.keyy_wave_container').each(function() {
			Keyy.render_keyy_code($(this).empty(), qr_url, Keyy.get_keyy_code_type());
		})
		
		$('.keyy_wave_to_qrcode').removeClass('keyy_wave_to_qrcode').addClass('keyy_qrcode_to_wave');
		$('.keyy-switch-checkbox').prop('checked', false);
	});

	$('form').on('click', '.keyy_qrcode_container .keyy_qrcode_to_wave, .keyy_qrcode_container .wave-label', function(e) {
		e.preventDefault();
		
		Keyy.set_keyy_code_type('wave');
		var qr_url = keyy.qr_url + '?context=' + keyy.context + '&token=' + keyy.login_token.value;

		$('.keyy_wave_container').each(function() {
			Keyy.render_keyy_code($(this).empty(), qr_url, Keyy.get_keyy_code_type());
		});
		
		$('.keyy_qrcode_to_wave').removeClass('keyy_qrcode_to_wave').addClass('keyy_wave_to_qrcode');
		$('.keyy-switch-checkbox').prop('checked', true);
	});

	$('form').on('click', '.keyy_qrcode_container .keyy_learn_more', function(e) {
		e.preventDefault();
		$(this).closest('.keyy-login-wrapper').find('.learn-more-slide-container').fadeIn('slow').show();
		$(this).closest('.keyy-login-wrapper').find('.keyy_qrcode_container').fadeOut('slow').hide();
		$(this).closest('.keyy-login-wrapper').find('.default-wp-form').fadeOut('slow').hide();
		Keyy.lazy_load_graphics($(this).closest('.keyy-login-wrapper').find('.keyy-intro-gif'));
	});

	$('form').on('click', '.keyy-back', function(e) {
		e.preventDefault();
		$(this).closest('.keyy-login-wrapper').find('.learn-more-slide-container').fadeOut('slow').hide();
		$(this).closest('.keyy-login-wrapper').find('.keyy_qrcode_container').fadeIn('slow').show();
		$(this).closest('.keyy-login-wrapper').find('.default-wp-form').fadeIn('slow').show();
	});

	if (keyy.stealth_mode) {
		$('body').focus().blur();
		
		var keypress_handler = function(event) {
			if (event.hasOwnProperty('which') && 107 == event.which) {
				var tag = event.target.tagName.toLowerCase();
				if ('input' != tag && 'textarea' != tag) {
					$('.keyy_qrcode_container').fadeToggle('slow');
					$('body').off('keypress', keypress_handler);
				}
			}
		};
		
		$('body').keypress(keypress_handler);
	}
});


/**
 * Inject a QR code into a login form
 *
 * @param String form_selector   - the form a single selector only whether a string or jQuery object it must only identify one DOM object
 * @param String form_identifier - a unique across the page identifier fo use for this QR code. This is for the purposes of distinguishing which form was scanned in the case of multiple being on the same page.
 * @param String selector 	     - where to inject after If unspecified it will be added at the start of the form. Not yet supported i.e. do not specify.
 *
 * @return void
 */
function keyy_inject_code_into_form(form_selector, form_identifier, selector) {
	
	var $ = jQuery;
	
	// Useful keys: value, expiry_date, expires_after (autologin, password_policy).
	var login_token = Keyy.get_login_token();
	
	// Create the div for the QR code.
	if ('undefined' === typeof selector) {
		if (keyy.is_disabled) {
			$(form_selector).prepend('<div class="keyy_qrcode_container_disabled">'+keyy.disabled+'</div>');
			
			if (Keyy.parse_query_string.hasOwnProperty('keyy_disable')) {
			
				$(form_selector).prepend('<input type="hidden" name="keyy_disable" value="'+Keyy.parse_query_string.keyy_disable+'">');
				
			}
			
			return;
		}

		var toggle = '<span class="keyy-switch-text-label qr-label">' + keyy.switch_to_wave +'</span>';
		toggle += '<div class="keyy-switch keyy_wave_to_qrcode">';
		toggle += '<input type="checkbox" name="keyy-switch" class="keyy-switch-checkbox" id="keyy-switch" checked>';
		toggle += '<label class="keyy-switch-label" for="keyy-switch">';
		toggle += '<span class="keyy-switch-inner"></span>';
		toggle += '<span class="keyy-switch-toggle"></span>';
		toggle += '</label>';
		toggle += '</div>';
		toggle += '<span class="keyy-switch-text-label wave-label">' + keyy.switch_to_qr +'</span>';

		
		var prepend_this = '<div class="keyy_qrcode_container"';

		if (keyy.stealth_mode) {
			// We do this directly, because we can't rely on the style sheet having been loaded.
			prepend_this += ' style="display:none;"';
		}

		prepend_this += '><img class="keyy-login-logo" src="' + keyy.keyy_logo_icon + '">';
		prepend_this += '<div class="keyy_qrcode" data-keyy_form_identifier="'+form_identifier+'"></div>';

		if (keyy.use_wave) { prepend_this += '<div class="keyy_qrcode_switch">'+toggle+'</div>'; }
		
		prepend_this += '<div> <h4 class="keyy_qrcode_wp_login_header">'+ keyy.wp_form_header;
		prepend_this += ' / <span class="keyy_learn_more">' + keyy.login_form_learn_more +'</span></h4></div>';
		prepend_this += '</div>' + keyy.learn_more_template;
		
		$(form_selector).wrapInner('<div class="default-wp-form"></div>');
		$(form_selector).prepend(prepend_this);
		
		$(form_selector).wrapInner('<div class="keyy-login-wrapper"></div>');
		$(form_selector).find('.learn-more-slide-container').hide();

	}
	
	// Display the QR code in the div.
	var qr_selector = $(form_selector).find('.keyy_qrcode');
	
	Keyy.show_keyy_code(qr_selector, 'login');

	if (login_token.hasOwnProperty('origin') && 'autologin' == login_token.origin && login_token.hasOwnProperty('password_policy') && 'required' == login_token.password_policy) {
		if (login_token.hasOwnProperty('user_login')) {
			var $login_form = $('form .keyy_qrcode').first().closest('form');
			
			$login_form.find('input[name="log"], input[name="username"], input[name="affwp_user_login"]').val(login_token.user_login).prop('readonly', true);
			
			$login_form.find('.keyy_qrcode').html(keyy.authorised_needs_password);
		}
		
		console.log("Keyy: user policy requires a password. Will not auto-submit form.");
		
		return;
	}
	
	// Set up a listener.
	Keyy.register_listener('login', function(response, login_token) {
		
		if (response.hasOwnProperty('state') && 'claimed' == response.state) {
			var $login_form;
			
			if (response.hasOwnProperty('form_id') && response.form_id) {
				$login_form = $('.keyy_qrcode[data-keyy_form_identifier="'+response.form_id+'"]').closest('form');
			} else {
				$login_form = $('form .keyy_qrcode').first().closest('form');
			}
			
			if ($login_form.length < 1) {
				alert(keyy.login_form_not_found);
				console.log(response);
				return;
			}
			
			$login_form.find('input[name="log"], input[name="username"], input[name="affwp_user_login"]').val(response.user_login).prop('readonly', true);
			$login_form.find('input[type="checkbox"][name="rememberme"]').prop('checked', true);
			$login_form.prepend('<input type="hidden" name="keyy_token_id" value="'+login_token.value+'">');
			
			if (response.hasOwnProperty('password_policy') && 'required' == response.password_policy) {
				
				var password = $login_form.find('input[name="pwd"], input[name="password"], input[name="pass"]');
				
				if (password.length && !password.val()) {
				
					console.log("Keyy: user policy (user: "+response.user_login+") requires a password, and none yet entered. Will not auto-submit form.");
					
					$login_form.find('.keyy_qrcode').html(keyy.authorised_needs_password);
					
					// Stop polling
					return true;
					
				}
			}
			
			console.log("Keyy: submitting: token_id="+login_token.value+" user_login="+response.user_login);
			
			// In the wild, we've seen a security module that blocked logins if the submit button text was not present.
			if ($login_form.find('input[type="submit"]').length > 0) {
				var $submit_button = $login_form.find('input[type="submit"]:first');
				var name = $submit_button.attr('name');
				if ($login_form.find('input[type!="submit"][name="'+name+'"]').length < 1) {
					$login_form.prepend('<input type="hidden" name="'+name+'" value="'+$submit_button.val()+'">');
				}
			}

			Keyy.exit_animation();
			
			if ('wave' == Keyy.get_keyy_code_type()) {
				setTimeout(function() {
					$login_form.submit()
				}, 1200);
			} else {
				$login_form.submit();
			}



			
			// Stop polling.
			return true;
		} else {
			if (!response.hasOwnProperty('state') || 'unused' != response.state) {
				// The app should show them a problem if there is one, so no need to also show in the UI here.
				console.log("Keyy: login listener responded, but not for a login:");
				console.log(response);
			}
		}
	});
	
}