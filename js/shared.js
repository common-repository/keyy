/**
 * Send an action via admin-ajax.php
 *
 * @param {string} action - the action to send
 * @param * data - data to send
 * @param Callback [callback] - will be called with the results
 * @param {boolean} [json_parse=true] - JSON parse the results
 * @param {int} [timeout=30] - the amout of time before the ajax call times out
 * @param {Callback} [error_callback] - an optional error callback
 */

var keyy_send_command_admin_ajax = function(action, data, callback, json_parse, timeout, error_callback) {
	
	timeout = ('undefined' === typeof timeout) ? 30 : timeout;
	json_parse = ('undefined' === typeof json_parse) ? true : json_parse;
	
	var ajax_options = {
		type: 'POST',
		url: keyy.ajax_url,
		timeout: (timeout * 1000), // In ms
		data: {
			action: 'keyy_ajax',
			subaction: action,
			nonce: keyy.ajax_nonce,
			data: data
		},
		success: function(response) {
			if (json_parse) {
				try {
					var resp = JSON.parse(response);
				} catch (e) {
					console.log(e);
					console.log(response);
					alert(keyy.error_unexpected_response);
					return;
				}
				if ('undefined' !== typeof callback) callback(resp);
			} else {
				if ('undefined' !== typeof callback) callback(response);
			}
		},
		error: function(response, status, error_code) {
			if ('function' == typeof error_callback) {
				error_callback(response, status, error_code);
			} else {
				console.log("keyy_send_command ("+action+"): error: "+status+" ("+error_code+")");
				console.log(response);
			}
		}
	}

	jQuery.ajax(ajax_options);
}

var js_date = new Date();
var keyy_connection_token_zero_time_at = Math.round(js_date.getTime() / 1000);
var keyy_login_token_zero_time_at = keyy_connection_token_zero_time_at;

if (typeof document.hidden !== 'undefined') {
	document.addEventListener('visibilitychange', function() {
		Keyy.handle_page_visibility_change();
	}, false);
}


/**
 * Function for sending communications
 *
 * @param {string} action - the action to send
 * @param * data - data to send
 * @param Callback [callback] - will be called with the results
 * @param {boolean} [json_parse=true] - JSON parse the results
 *
 * @callable sendcommandCallable
 */

jQuery(document).ready(function($) {
	
	Keyy = Keyy(keyy_send_command_admin_ajax);
	
	if (keyy.hasOwnProperty('connection_token')) {
		Keyy.set_connection_token(keyy.connection_token);
	}
	// Look for any pre-indicated token.
	if (Keyy.parse_query_string.keyy_token_id) {
		var initial_token = {value: Keyy.parse_query_string.keyy_token_id, expires_after: 285, origin: 'autologin'}
		if (Keyy.parse_query_string.hasOwnProperty('keyy_password') && 'required' == Keyy.parse_query_string.keyy_password) {
			initial_token.password_policy = 'required';
			if (Keyy.parse_query_string.hasOwnProperty('keyy_user_login')) {
				initial_token.user_login = Keyy.parse_query_string.keyy_user_login;
			}
		}
		Keyy.set_login_token(initial_token);
	} else if (keyy.hasOwnProperty('login_token')) {
		Keyy.set_login_token(keyy.login_token);
	}
	
	if (keyy.hasOwnProperty('connection_result')) {
		Keyy.set_connection_result(keyy.connection_result);
	}
	
	if (keyy.hasOwnProperty('connected')) {
		Keyy.set_connection_status(keyy.connected);
	}
	
	// N.B. More than one on the page is not supported
	if ($('#keyy_connect_frontend_qrcode').length > 0) {
		Keyy.setup_connection_code_area('#keyy-connect-frontend-container', '#keyy_connect_frontend_qrcode', 'get_frontend_connection_code');
	}
	
	
});

/**
 * Main Keyy object
 *
 * @param {sendcommandCallable} send_command - function for sending remote communications via
 */
var Keyy = function(send_command) {
	
	var $ = jQuery;
	var connection_token;
	var login_token;
	var connection_result = null;
	var connection_status = -1;
	var last_context = keyy.context;

	var is_screen_visible = 1;
	var screen_visibility_timer;


	var connection_timeout_replacement_timer = null;
	var login_timeout_replacement_timer = null;
	
	var connection_status_timer = null;
	var login_status_timer = null;

	var keyy_code_type;
	
	// http://stackoverflow.com/questions/979975/how-to-get-the-value-from-the-get-parameters.
	this.parse_query_string = function() {
		var query_string = {};
		var query = window.location.search.substring(1);
		var vars = query.split("&");
		var var_length = vars.length;
		
		for (var i = 0; i < var_length; i++) {
			var pair = vars[i].split("=");
			// If first entry with this name.
			if ("undefined" === typeof query_string[pair[0]]) {
				query_string[pair[0]] = decodeURIComponent(pair[1]);
				// If second entry with this name.
			} else if ("string" === typeof query_string[pair[0]]) {
				var arr = [ query_string[pair[0]],decodeURIComponent(pair[1]) ];
				query_string[pair[0]] = arr;
				// If third or later entry with this name.
			} else {
				query_string[pair[0]].push(decodeURIComponent(pair[1]));
			}
		}
		return query_string;
	}();
	
	/**
	 * Save the chosen settings.
	 *
	 * @param String where_from - the selector for where to find the settings (gets passed to gather_settings)
	 * @param String [spinner] - the selector for the spinner to show whilst saving
	 * @param String [done] - the selector of something to temporarily show after saving
	 * @param String [type="user"] - the type of settings being saved. This is passed to the back-end.
	 *
	 * @uses temporarily_display_notice()
	 * @uses gather_settings()
	 */
	this.save_settings = function(where_from, spinner, done, type) {
		
		type = ('undefined' === typeof type) ? 'user' : type;
		
		if ('undefined' !== typeof spinner) { $(spinner).show(); }
		
		var form_data = Keyy.gather_settings(where_from);
		
		var data = { type: type, data: form_data };
		
		if (type == 'user-via-site-admin') {
			data.user_id = $('.keyy_user_settings').data('user_id');
		}
		
		send_command('save_settings', data, function(response) {
			
			if ('undefined' !== typeof spinner) { $(spinner).hide(); }
			
			if ('undefined' !== typeof done) { $(done).show().delay(5000).fadeOut(); }
			
			if (response && response.hasOwnProperty('errors')) {
				for (var i = 0, len = response.errors.length; i < len; i++) {
					var new_html = '<div class="error">'+response.errors[i]+'</div>';
					
					var display_at = ($('#keuy-admin-body-container').length > 0) ? '#keyy-admin-body-container' : (($('#keyy-admin-settings').length >0) ? '#keyy-admin-settings' : '.keyy_user_settings_container');
					
					if ('user-via-site-admin' == type) {
						display_at = '.keyy_user_results .keyy-site-admin-user-settings';
					}
					
					Keyy.temporarily_display_notice(new_html, display_at);
				}
				console.log(response.errors);
			}
		});
	}
	
	/**
	 * Gathers the settings from the settings section
	 *
	 * @param String where_from - the selector for where to find the settings
	 *
	 * @returns (string) - serialized settings
	 */
	this.gather_settings = function(where_from) {
		
		// Excluding the unnecessary 'action' input avoids triggering a very mis-conceived mod_security rule seen on one user's site.
		var form_data = $(where_from+" input[name!='action'], "+where_from+" textarea, "+where_from+" select").serialize();
		// include unchecked checkboxes. user filter to only include unchecked boxes.
		$.each($(where_from+' input[type=checkbox]')
			.filter(function(idx) {
				return $(this).prop('checked') == false
			}),
			function(idx, el) {
				// attach matched element names to the form_data with chosen value.
				var empty_val = '0';
				form_data += '&' + $(el).attr('name') + '=' + empty_val;
			}
		);
		
		return form_data;
	}
	
	/**
	 * Temporarily show a dashboard notice, and then remove it. The HTML will be prepended to the #keyy-admin-body-container element.
	 *
	 * @param {string} html_contents - HTML to display
	 * @param {string} where - CSS selector of where to prepend the HTML to
	 * @param {number} [delay=15] - the number of seconds to wait before removing the message
	 */
	this.temporarily_display_notice = function(html_contents, where, delay) {
		where = ('undefined' === typeof where) ? '#keyy-admin-body-container' : where;
		delay = ('undefined' === typeof delay) ? 15 : delay;
		$(html_contents).hide().prependTo(where).slideDown('slow').delay(delay * 1000).slideUp('slow', function() {
			$(this).remove();
		});;
	}
	
	/**
	 * Stop polling for logins
	 */
	this.stop_login_polling = function() {
		if (null !== login_status_timer) {
			clearTimeout(login_status_timer);
			login_status_timer = null;
		}
	}
	
	/**
	 * New connection result
	 *
	 * @param {String} connection_result - the new connection result. This can have keys code, data, time
	 */
	this.set_connection_result = function(new_connection_result) {
		connection_result = new_connection_result;
	}
	
	/**
	 * New connection status
	 *
	 * @param {Boolean} connection_status - the new connection status
	 */
	this.set_connection_status = function(new_status) {
		connection_status = new_status;
	}
	
	/**
	 * Returns connection status
	 *
	 * @returns Boolean
	 */
	this.get_connection_status = function() {
		return connection_status;
	}
	
	/**
	 * Store the connection token
	 *
	 * @param {String} new_connection_token - the new connection token, which must at least have attributes 'value' and 'expires_after'
	 */
	this.set_connection_token = function(new_connection_token) {
		
		connection_token = new_connection_token;
		var replace_after = (connection_token.expires_after - 5);
		if (replace_after < 1) { replace_after = 1; }
		
		// Cancel any previous timer.
		if (null !== connection_timeout_replacement_timer) {
			clearTimeout(connection_timeout_replacement_timer);
		}
		
		connection_timeout_replacement_timer = setTimeout(
			function() {
				connection_timeout_replacement_timer = null;
				$('.keyy_qrcode').html(keyy.replacing);
				Keyy.replace_connection_token();
			},
			(replace_after * 1000)
		);
	}
	
	/**
	 * Get the current login token
	 *
	 * @returns Object - the login token
	 */
	this.get_login_token = function() {
		return login_token;
	}
	
	/**
	 * Store the login token
	 *
	 * @param {String} new_login_token - the new login token, which must at least have attributes 'value' and 'expires_after'
	 */
	this.set_login_token = function(new_login_token) {
		
		login_token = new_login_token;
		var replace_after = (login_token.expires_after - 5);
		if (replace_after < 1) { replace_after = 1; }
		
		// Cancel any previous timer.
		if (null !== login_timeout_replacement_timer) {
			clearTimeout(login_timeout_replacement_timer);
		}
		
		login_timeout_replacement_timer = setTimeout(
			function() {
				login_timeout_replacement_timer = null;
				$('.keyy_qrcode').html(keyy.replacing);
				Keyy.replace_login_token();
			},
			(replace_after * 1000)
		);
	}
	
	/**
	 * Get a new connection token
	 */
	this.replace_connection_token = function() {
		
		send_command('get_connection_token', { force_refresh: 1 }, function(response) {

			if (response.hasOwnProperty('value')) {
				var js_date = new Date();
				keyy_connection_token_zero_time_at = Math.round(js_date.getTime() / 1000);
				Keyy.set_connection_token(response);
				Keyy.show_keyy_code('.keyy_qrcode', last_context);
			} else if (false !== response) {
				console.log("Keyy: get_connection_token: unexpected result");
				console.log(response);
			}
		}, true, 30, function(response, status, error_code) {
			$('.keyy_qrcode').html(keyy.refresh);
			console.log(response);
		});
	}
	
	/**
	 * Get a new login token
	 */
	this.replace_login_token = function() {
		
		send_command('get_fresh_login_token', null, function(response) {
			
			if (response.hasOwnProperty('value')) {
				var js_date = new Date();
				keyy_login_token_zero_time_at = Math.round(js_date.getTime() / 1000);
				Keyy.set_login_token(response);
				Keyy.show_keyy_code('.keyy_qrcode', last_context);
			} else {
				console.log("Keyy: get_fresh_login_token: unexpected result");
				console.log(response);
			}
		}, true, 30, function(response, status, error_code) {
			$('.keyy_qrcode').html(keyy.refresh);
			console.log(response);
		});
	}

	/**
	 * Get the type of code to render
	 */

	this.get_keyy_code_type = function() {

		if ('undefined' === typeof keyy_code_type) {
			keyy_code_type = (keyy.use_wave) ? 'wave' : 'qr';
		}

		return keyy_code_type;
	}

	/**
	 * Sets the type of code to render, use this when changing the type
	 *
	 * @param {String} type - the type of code to render
	 */

	this.set_keyy_code_type = function(type) {

		keyy_code_type = ('wave' === type) ? 'wave' : 'qr';
	}

	/**
	 * Sets the correct value to the 'is_screen_visible' variable which can used else where
	 */

	this.handle_page_visibility_change = function() {
		if ('hidden' == document['visibilityState']) {
			screen_visibility_timer = window.setTimeout(function() {
				is_screen_visible = 0;
			  }, 10000);
		} else {
			is_screen_visible = 1;
			if ('undefined' !== typeof screen_visibility_timer) {
				window.clearTimeout(screen_visibility_timer);
			}
		}
	}
	
	/**
	 * Function for sending communications
	 *
	 * @param {Object} response - the result
	 * @param {Object} [token] - the login token, if it was a login listener
	 *
	 * @callable listenerCallable
	 *
	 * @returns Void|Boolean - for a login listener, returning true indicates that polling should be stopped
	 */
	
	/**
	 * Performs a one-time poll
	 *
	 * @param {String} context - either 'connect' or 'login'
	 * @param listenerCallable callback - a callback function
	 * @param {Integer} poll_for - how the back-end should keep polling for. This is a trade-off between how often to send an HTTP request (which causes the whole WP stack to load), and how long to keep the PHP process running (which may cause policy limits to be hit on some hosting companies).
	 *
	 * @return Boolean - whether there was any scan detected (whether successful or not)
	 */
	this.poll_for_result = function(context, callback, poll_for) {
		
		var poll_for = ('undefined' === typeof poll_for) ? 10 : poll_for;
		
		var params = { }
		if (poll_for > 0) { params.wait = poll_for; }
		
		if ('login' == context) {
			params.token_id = login_token.value;
			
			send_command('get_token_state', params, function(response) {
				
				if (response.hasOwnProperty('state')) {
					if ('undefined' !== typeof callback) {
						var stop_polling = callback(response, login_token);
						if (stop_polling) { Keyy.stop_login_polling(); }
					}
				} else if (false !== response) {
					console.log("Keyy: get_token_state: unexpected result:");
					console.log(response);
				}
			});
		} else {
			var command = connection_status ? 'disconnect' : 'connect';
			
			if (connection_result && connection_result.hasOwnProperty('time')) {
				params.since_time = connection_result.time;
			}

			send_command('get_'+command+'ion_result', params, function(response) {
				
				if (response && response.hasOwnProperty('connection_token')) {
					Keyy.set_connection_token(response.connection_token);
				}
				
				// Expected attributes: code, data, time.
				if (!connection_status) {
					// Invalid response, or nothing happened.
					if (!response.hasOwnProperty('time')) return;
						
					// Nothing has happened since the page load.
						 if (connection_result && connection_result.hasOwnProperty('time') && response.time <= connection_result.time) return;
					
						 if (response.hasOwnProperty('connected')) {
						Keyy.set_connection_status(response.connected);
						}
						
					Keyy.set_connection_result(response);
					
					if ('undefined' !== typeof callback) callback(response);
				} else {
					if (response.hasOwnProperty('connected') && !response.connected) {
						Keyy.set_connection_status(false);
						if ('undefined' !== typeof callback) callback(response);
					}
				}
			});
		}
	}
	
	/**
	 * Decide how long to poll for and how long in between polls, based upon how many times we have polled already
	 *
	 * @param {Integer} seconds_passed - how long the scan has been waiting thus far
	 *
	 * @return {Object} - how long to poll for (poll_for property) and when to poll (next_poll_at)
	 */
	var get_poll_timings = function(seconds_passed) {
		
		// On the timings below, the polls happen at these times: 4, 11, 17, 23, 29, 34, 39 ...
		// And, PHP processes are running (almost all sleeping, of course) from 4-10, 11-14, 17-20, 23-26, 29-32 and then it sleeps just for 1 second until 2 minutes; then it will die immediately.
		// So, In the first 32 seconds, PHP is around for up to 18 seconds.
		
		// First poll after 4 seconds. You'll have to be pretty fast to scan and get bored before then. This takes us up to 10 seconds. (And remember to have a cushion until the next).
		if (seconds_passed <= 4) {
			return { poll_for: 6, next_poll_at: 4 }
		}
		
		// Then, from 11 seconds, poll for 3 seconds, every 6 seconds
		if (seconds_passed <= 30) {
			var next_poll_at = Math.max((6 + seconds_passed - (seconds_passed % 6)) - 1, 11);
			return { poll_for: 3, next_poll_at: next_poll_at }
		}
		
		// Every 6 seconds
		var next_poll_at = (6 + seconds_passed - (seconds_passed % 6)) - 1;
		
		if (seconds_passed <= 120) {
			// Wait a second and perform a second check
			return { poll_for: 2, next_poll_at: next_poll_at }
		}
		
		// Seems like the user's just leaving the page open. Now we'll die immediately.
		return { poll_for: 0, next_poll_at: next_poll_at }
		
	}
	
	/**
	 * Registers a listener for scanning events, and calls back when one is detected
	 *
	 * @param {String} context - one of 'connect' or 'login'
	 * @param listenerCallable callback - a callback function
	 */
	this.register_listener = function(context, callback) {
		
		var counter = 0;
		
		// This gets reset when a new listener is set up - i.e. when connecting/disconnecting on the 'connect' page
		var seconds_passed = 0;
		
		var poll_timings;
		
		if ('connect' == context) {
			connection_status_timer = setInterval(function() {
				seconds_passed++;
				poll_timings = get_poll_timings(seconds_passed);
				if (seconds_passed >= poll_timings.next_poll_at && is_screen_visible) {
					Keyy.poll_for_result(context, callback, poll_timings.poll_for);
				}
			}, 1000);
		} else if ('login' == context) {
			
			// We do not need multiple listeners on the page.
			if (login_status_timer) {
				if (keyy.debug) {
					console.log("Keyy: login listener already active; will not set up another (presumably the page has multiple scan areas)");
				}
				return;
			}
			
			login_status_timer = setInterval(function() {
				seconds_passed++;
				poll_timings = get_poll_timings(seconds_passed);
				if (seconds_passed >= poll_timings.next_poll_at && is_screen_visible) {
					Keyy.poll_for_result(context, callback, poll_timings.poll_for);
				}
			}, 1000);
		}
			
	}

	/**
	 * Start lazy loading images
	 *
	 * @param String selector - the selector, a string or jQuery object
	 *
	 * @return void
	 */

	this.lazy_load_graphics = function(selector) {
		$(selector).each(function() {
			if ('undefined' !== typeof $(this).attr('data-src')) {
				$(this).attr('src', $(this).attr('data-src'));
			}
		});
	}

	/**
	 * Triggers the exit animation, once a scan is successful
	 *
	 * @return void
	 */

	this.exit_animation = function() {
		// Scale down
		$('div.keyy_qrcode.keyy_wave_container').css('transition', 'all .8s ease-in-out');
		$('div.keyy_qrcode.keyy_wave_container').css('transform', 'scale(.5)');

		// Stop dancing
		if ('wave' == Keyy.get_keyy_code_type()) {
			setTimeout(function() {
				$('rect').each(function() {
					$(this).on('animationiteration webkitAnimationIteration', function() {
						$(this).css('transition', 'all .8s ease-in-out');
						$(this).removeClass('anim')
					});
				});
			}, 250);

		}
		
		// Surround with a circle
		var svg = $('svg.keyy-wave');
		var circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
		$(circle).attr('cx', '150');
		$(circle).attr('cy', '100');
		$(circle).attr('r', '200');
		$(circle).css({
			'stroke': '#ec7f52',
			'strokeWidth': '10px',
			'fill': 'none',
			'animation': 'encircle .9s linear alternate forwards',
			'stroke-dasharray': '1329',
			'stroke-dashoffset': '1329'
		});

		$(svg).append(circle);

		// Rotate out the wave
		if ('wave' == Keyy.get_keyy_code_type()) {
			setTimeout(function() {
				$('.keyy-wave g').css('animation', 'rotateOut 500ms').fadeOut("slow");

			}, 1600);
		} else {
			$('.keyy_qrcode.keyy_wave_container img').css('animation', 'rotateOut 500ms').fadeOut("slow").hide();
		}

		// Rotate in the thumbs up

		if ('wave' == Keyy.get_keyy_code_type()) {
			setTimeout(function() {
				$('.keyy_qrcode.keyy_wave_container')
					.prepend('<object class="keyy-thumbs-up" type="image/svg+xml" data="'+keyy.keyy_thumbs_up+'"></object>');
				$('.keyy-thumbs-up').css('animation', 'rotateIn 200ms').fadeIn("fast");
			}, 1800);
		} else {
			$('.keyy_qrcode.keyy_wave_container img')
				.replaceWith('<object class="keyy-thumbs-up" type="image/svg+xml" data="'+keyy.keyy_thumbs_up+'"></object>');
			$('.keyy-thumbs-up').css('animation', 'rotateIn 200ms').css('left', '40px').fadeIn("fast");
		}

	}

	/**
	 * Render the indicated URL as a QR code or a Keyy Wave in the place indicated.
	 * This is called by show_keyy_code, which is a higher-level method
	 *
	 * @param {String|Object} selector - the DOM identifer, or jQuery object, for where to add the QR code.
	 * @param {String} qr_url - the URL to render as a QR code
	 * @param {String} use_wave - qr | wave , the type of code to render
	 */
	this.render_keyy_code = function(selector, qr_url, use_wave) {
		
		$('#login').css('min-width', '450px').css('padding-top', '3%');
		
		if ('wave' == use_wave && 'login' == keyy.context) {
			var wave = keyy.site_hash + login_token.value;

			if ($(selector).data('keyy_form_identifier')) {
				wave += $(selector).data('keyy_form_identifier');
			}

			console.log("Keyy Wave: " + wave);
			$(selector).addClass('keyy_wave_container').data('wave', wave)
				.empty().append('<svg class="keyy-wave"></svg>');

			$(selector).find(".keyy-wave").each(function(index) {
				JsBarcode(this, wave, {
					lineColor: keyy.wave_colour,
					width:2,
					height:180,
					displayValue: false
				});

				$(this).find("rect").each(function () {
					$(this).addClass("anim");
				});

				$("rect.anim").each(function () {
					$(this).css("animation-duration",
						// The dance formula
					((Math.random() * (9 - 5) + 5)*.15).toString().substring(0,4) + "s");
				});
			});
		} else {
			console.log("Keyy: QR URL: " +qr_url);
			var el = kjua({
				render: 'image',
				text: qr_url,
				ecLevel: 'H',
				fill: '#da521b',
				crisp: false
			});
			
			$(selector).addClass('keyy_qrcode').data('qrcode', qr_url).empty();

			if ('string' === typeof selector) {
				var qs = document.querySelector(selector);
				if (qs) { qs.appendChild(el); }
			} else {
				$(selector).append(el);
			}
		}

		if (keyy.debug) {
			$(selector).append('<div class="keyy-debug">'+qr_url+'</div>');
		}
	}
	
	/**
	 * Show a QR code in the place indicated
	 *
	 * @param {String|Object} selector			- the DOM identifer or jQuery object for where to add the QR code.
	 * @param {String}		  [context='login'] - the context (either 'login' or 'connect')
	 */
	this.show_keyy_code = function(selector, context) {
		context = ('undefined' === typeof context) ? 'login' : context;
		
		last_context = context;
		
		var base_qr_url = keyy.qr_url + '?context=' + context;
		
		var js_date = new Date();
		var seconds_now = Math.round(js_date.getTime() / 1000);
		
		var token_age = (seconds_now - keyy_connection_token_zero_time_at);

		var expires_after = ('connect' == context) ? connection_token.expires_after : login_token.expires_after;
		
		var get_token_command = ('connect' == context) ? 'get_connection_token' : 'get_fresh_login_token';
		
		var get_options = ('connect' == context) ? { force_refresh : 1 } : null;
		
		if (token_age + 2 > expires_after) {
			send_command(get_token_command, get_options, function(response) {
				
				if (response.hasOwnProperty('value')) {
					var js_date = new Date();
					
					if ('connect' == context) {
						keyy_connection_token_zero_time_at = Math.round(js_date.getTime() / 1000);
						Keyy.set_connection_token(response);
					} else {
						keyy_login_token_zero_time_at = Math.round(js_date.getTime() / 1000);
						Keyy.set_login_token(response);
					}

					var token = ('connect' == context) ? connection_token : login_token;

					base_qr_url = base_qr_url + '&token=' + token.value;
					$(selector).each(function(index) {
						if ($(this).data('keyy_form_identifier')) {
							var qr_url = base_qr_url + '&form_id=' + $(this).data('keyy_form_identifier');
							Keyy.render_keyy_code(this, qr_url, Keyy.get_keyy_code_type());
						} else {
							Keyy.render_keyy_code(this, base_qr_url, Keyy.get_keyy_code_type());
						}
					});
				}
			});
		} else {

			var token = ('connect' == context) ? connection_token : login_token;

			base_qr_url = base_qr_url + '&token=' + token.value;
			$(selector).each(function(index) {
				if ($(this).data('keyy_form_identifier')) {
					var qr_url = base_qr_url + '&form_id=' + $(this).data('keyy_form_identifier');
					Keyy.render_keyy_code(this, qr_url, Keyy.get_keyy_code_type());
				} else {
					Keyy.render_keyy_code(this, base_qr_url, Keyy.get_keyy_code_type());
				}
			});
		}
	}
	
	/**
	 * Initial set up of connection code area - display the QR code and listen for events
	 *
	 * @param {String} message_area - the CSS identifier of where to place messages
	 * @param {String} connect_qr_code_area - where to put the QR code
	 * @param {String} command_for_refresh - the command to run to get the refresh contents
	 *
	 * @return void
	 */
	this.setup_connection_code_area = function(message_area, connect_qr_code_area, command_for_refresh) {
		
		if (!Keyy.get_connection_status()) {
			Keyy.show_keyy_code(connect_qr_code_area, 'connect');
		}

		Keyy.register_listener('connect', function(resp) {
			
			console.log("Keyy: remote command received:");
			console.log(resp);
			
			if (!resp.hasOwnProperty('code') || !resp.hasOwnProperty('data') || !resp.data || !resp.data.hasOwnProperty('message')) {
				alert(keyy.error_unexpected_response);
				return;
			}
			
			if (resp && resp.hasOwnProperty('connection_token')) {
				Keyy.set_connection_token(resp.connection_token);
			}
			
			var code = resp.code;
			var message = resp.data.message;
			
			$(message_area).after('<div class="keyy_connected_message">'+message+'</div>');
			
			send_command(command_for_refresh, null, function(response) {
				
				if (response && response.hasOwnProperty('html')) {
					$(message_area).fadeOut('slow', function() {
						$(this).replaceWith(response.html);
						if ($(connect_qr_code_area).length > 0) {
							Keyy.show_keyy_code(connect_qr_code_area, 'connect');
						}
					});
					
					setTimeout(function() {
						$(message_area).siblings('.keyy_connected_message').slideUp('slow', function() {
							$(this).remove();
						});
					}, 5000);
				} else {
					console.log("Keyy: "+command_for_refresh+" failed");
					console.log(response);
				}
			});
		});
	}
	
	return this;
};
