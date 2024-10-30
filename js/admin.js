jQuery(document).ready(function($) {
	
	var send_command = keyy_send_command_admin_ajax;
	
	$('#keyy-admin-body-container').on('change', 'input', function() {
		Keyy.save_settings('#keyy-admin-body', '#keyy_save_spinner', '#keyy_save_done');
	});

	$('.keyy-ua-notice').on('click', '.notice-dismiss', function() {
		send_command('dismiss_ua_notice');
	});

	Keyy.setup_connection_code_area('#keyy-admin-body', '#keyy_connect_qrcode', 'get_dashboard_page');
	
	// Handle clicks on the 'disconnect' button.
	$('#keyy-admin-body-container').on('click', '#keyy_disconnect', function() {
		
		$('#keyy_disconnect').prop('disabled', true);
		
		send_command('disconnect', null, function(response) {
			
			$('#keyy_disconnect').prop('disabled', false);
			
			if (response && response.hasOwnProperty('connection_token')) {
				Keyy.set_connection_token(response.connection_token);
			}
			
			if (response && response.hasOwnProperty('dashboard_html')) {
				$('#keyy-admin-body').replaceWith(response.dashboard_html);
				Keyy.set_connection_status(false);
				Keyy.show_keyy_code('#keyy_connect_qrcode', 'connect');
			} else {
				console.log("Keyy: disconnect: unexpected response:");
				console.log(response);
			}
		});
	});
});
