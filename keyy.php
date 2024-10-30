<?php
// @codingStandardsIgnoreStart
/*
Plugin Name: Keyy Two Factor Authentication
Plugin URI: https://wordpress.org/plugins/keyy/
Description: Easy-to-use WordPress logins
Version: 1.2.3
Text Domain: keyy
Domain Path: /languages
Author: Nex.ist
Author URI: https://nex.ist/
Requires at least: 4.4
Tested up to: 6.6.1
License: MIT

Copyright: 2017- David Anderson
Copyright: October 2019- Nexist

*/
// @codingStandardsIgnoreEnd

if (!defined('ABSPATH')) die('Access denied.');

if (!defined('KEYY_DIR')) define('KEYY_DIR', dirname(__FILE__));
if (!defined('KEYY_URL')) define('KEYY_URL', plugins_url('', __FILE__));

// During development
if (!defined('KEYY_ALLOW_SESSION_SERVER')) define('KEYY_ALLOW_SESSION_SERVER', false);
if (!defined('KEYY_ALLOW_SSO')) define('KEYY_ALLOW_SSO', false);

// Add this to wp-config.php if you do not want the wave
// define('KEYY_USE_WAVE', false);

if (!class_exists('Keyy_Login_Plugin')) :
class Keyy_Login_Plugin {

	const VERSION = '1.2.3';
	
	// This needs bumping whenever the plugin implements something that may need an app upgrade for the app to be able to handle. It represents the plugin version in which something new was added (so should always be less than or equal to the plugin version, and will be less than except on the exact release which adds the new facility).
	const APP_COMPAT_VERSION = '0.6.13';

	// Minimum versions required to run this plugin.
	const PHP_REQUIRED = '5.2.6';
	const WP_REQUIRED = '4.4';

	// This is not used for anything; will be cleared out if/when it is clear it never will be.
	const API_SERVER = 'api.getkeyy.com';

	const META_KEY_USER_AGENTS = 'keyy_user_agents';
	const ANDROID_LATEST = '1.1.19';
	const IOS_LATEST = '1.2.8';
	
	protected static $_instance = null;
	
	protected $_login_instance = null;
	
	private $template_directories = null;

	protected static $_notices_instance = null;

	/**
	 * Constructor. This class handles bootstrapping (including loading of other classes), WP dashboard interaction, and AJAX processing.
	 */
	public function __construct() {
	
		if (is_file(KEYY_DIR.'/premium/loader.php')) include_once(KEYY_DIR.'/premium/loader.php');

		if (version_compare(PHP_VERSION, self::PHP_REQUIRED, '<')) {
			add_action('all_admin_notices', array($this, 'admin_notice_insufficient_php'));
			$abort = true;
		}

		include ABSPATH.WPINC.'/version.php';
		if (version_compare($wp_version, self::WP_REQUIRED, '<')) {// phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable -- Global Wordpress Variable
			add_action('all_admin_notices', array($this, 'admin_notice_insufficient_wp'));
			$abort = true;
		}

		if(is_multisite()){
			add_action('all_admin_notices',array($this,'admin_notice_for_multisite'));
			$abort = true;
		}
		
		if (defined('KEYY_DISABLE') && KEYY_DISABLE) {
			// We don't abort - it's only login-form activity that gets disabled.
			add_action('all_admin_notices', array($this, 'admin_notice_disabled'));
		}
		
		add_action('plugins_loaded', array($this, 'plugins_loaded_even_on_abort'));
		
		if (!empty($abort)) return;
		
		// Dashboard and AJAX actions are conditionally run in plugins_loaded_not_on_abort
		add_action('plugins_loaded', array($this, 'plugins_loaded_not_on_abort'));

		// Check if app is the latest version available, else display notice
		add_action('all_admin_notices', array($this, 'admin_notice_outdated_app'));

		// Adds user settings link to plugin header
		add_filter((is_multisite() ? 'network_admin_' : '') . 'plugin_action_links', array($this, 'plugin_action_links'), 10, 2);
		
		include_once(KEYY_DIR.'/includes/keyy-options.php');
		
		include_once(KEYY_DIR.'/includes/rest.php');

		new Keyy_REST($this);
		
		$this->login_instance();
		
		add_shortcode('keyy_connect', array($this, 'shortcode_keyy_connect'));

		do_action('keyy_loaded', $this);
		
	}
	
	/**
	 * Returns the only instance of this class
	 *
	 * @return Keyy_Login_Plugin
	 */
	public static function instance() {
		if (empty(self::$_instance)) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
	
	/**
	 * Returns the only instance of the login class
	 *
	 * @return Keyy_Login
	 */
	public function login_instance() {
		if (empty($this->_login_instance)) {
			if (!class_exists('Keyy_Login')) include_once(KEYY_DIR.'/includes/login.php');
			$this->_login_instance = new Keyy_Login($this);
		}

		return $this->_login_instance;
	}

	/**
	 * Returns the only instance of the keyy notices class
	 *
	 * @return Keyy_Notices
	 */
	public static function get_notices() {
		if (empty(self::$_notices_instance)) {
			if (!class_exists('Keyy_Notices')) include_once(KEYY_DIR.'/includes/keyy-notices.php');
			self::$_notices_instance = new Keyy_Notices();
		}
		return self::$_notices_instance;
	}
	
	/**
	 * Get the capability required to use the plugin
	 *
	 * @param  String $for - 'user' or 'admin'.
	 * @return String - the capability (a recognised WP capability)
	 */
	public function capability_required($for = 'user') {
		$capability_required = ('user' == $for) ? 'read' : 'create_users';
		return apply_filters('keyy_capability_required', $capability_required, $for);
	}
	
	/**
	 * AJAX handler (i.e. commands via admin-ajax.php) for unprivileged actions
	 */
	public function nopriv_ajax_handler() {
		$this->ajax_handler(false);
	}
	
	/**
	 * AJAX handler (i.e. commands via admin-ajax.php) for privileged actions
	 */
	public function priv_ajax_handler() {
		$this->ajax_handler(true);
	}
	
	/**
	 * AJAX handler (i.e. commands via admin-ajax.php)
	 *
	 * @param Boolean $via_priv - whether the source of this request was via the priv or nopriv route.
	 */
	public function ajax_handler($via_priv = false) {

		$nonce = empty($_POST['nonce']) ? '' : $_POST['nonce'];

		if (empty($_POST['subaction'])) die(json_encode(array('message' => 'Security check (1a)')));
		
		$subaction = $_POST['subaction'];
		
		// Though these happen to have the same values here, they are conceptually separate
		$nopriv_actions = array('get_fresh_login_token', 'get_token_state');
		$nointent_actions = array('get_fresh_login_token', 'get_token_state');

		if (!in_array($subaction, $nointent_actions) && !wp_verify_nonce($nonce, 'keyy-ajax-nonce')) die(json_encode(array('message' => 'Security check (1b)')));
		
		if (!$via_priv && !in_array($subaction, $nopriv_actions)) die('Security check (2)');
		
		$data = isset($_POST['data']) ? $_POST['data'] : null;

		$results = array();

		// Some commands that are available via AJAX only.
		if ('dismiss_page_notice_until' == $subaction) {
			Keyy_Options::update_option('dismiss_page_notice_until', time() + 84 * 86400);
		} elseif ('dismiss_seasonal_notice_until' == $subaction) {
			Keyy_Options::update_option('dismiss_seasonal_notice_until', time() + 84 * 86400);
		} else {
			// Other commands, available for any remote method.
			if (!class_exists('Keyy_Commands')) include_once(KEYY_DIR.'/includes/class-commands.php');
			
			$commands = new Keyy_Commands();
			
			if ($via_priv) {
				$privilege_level_required = $commands->get_privilege_level_required($subaction);
				
				if (!current_user_can($this->capability_required($privilege_level_required))) die('Security check (3)');
			}
			
			if (!is_callable(array($commands, $subaction))) {
				error_log("Keyy: ajax_handler: no such command ($subaction)");
				die(json_encode(array('result' => 'No such command')));
			} else {
				$results = call_user_func(array($commands, $subaction), $data);
				
				if (is_wp_error($results)) {
					$results = array(
						'result' => false,
						'error_code' => $results->get_error_code(),
						'error_message' => $results->get_error_message(),
						'error_data' => $results->get_error_data(),
					);
				}
			}
		}
		echo json_encode($results);
		
		die;
	}

	/**
	 * WordPress action pre_get_users
	 *
	 * @param String $query - the User query
	 */
	public function pre_get_users( $query ) {
		global $pagenow;

		if ('users.php' == $pagenow && isset($_REQUEST['keyy_status']) && !empty($_REQUEST['keyy_status'])) {
			$status = $_REQUEST['keyy_status'];
			
			if ('connected' == $status) {
				$meta_query = array(
					array(
						'key' => Keyy_Login::META_KEY_PUBLIC_KEY,
						'value' => '',
						'compare' => '!='
					),
				);
				$query->set('meta_key', Keyy_Login::META_KEY_PUBLIC_KEY);
			} elseif ('unconnected' == $status) {

				$meta_query = array(
					'relation' => 'OR',
					array(
						'key' => Keyy_Login::META_KEY_PUBLIC_KEY,
						'compare' => 'NOT EXISTS'
					),
					array(
						'key' => Keyy_Login::META_KEY_PUBLIC_KEY,
						'value' => '',
						'compare' => '='
					),
				);
			}
			
			
			$query->set('meta_query', $meta_query);
		}
	}
	
	/**
	 * Shortcode function for the [keyy_connect] shortcode. Parameters according to the WordPress shortcode API.
	 *
	 * @return String - the shortcode output. This should be returned, not echoed.
	 */
	public function shortcode_keyy_connect() {
	
		// Not yet currently suppored for setup (i.e. non-logged-in users)
		if (is_user_logged_in()) {
			$this->enqueue_scripts('shared');
		}
	
		return $this->get_frontend_connection_code();

	}
	
	/**
	 * WordPress action restrict_manage_users
	 *
	 * @param String $which - 'top' or 'bottom'
	 */
	public function restrict_manage_users($which) {
	
		// Only present on WP 4.6+, so in effect, this feature requires WP 4.6+
		if ('top' != $which) return;
	
		echo '<select name="keyy_status" style="float:none;">';
		echo '<option value="">'.__('Keyy status...', 'keyy').'</option>';
		$values = array(
			'connected' => __('Connected', 'keyy'),
			'unconnected' => __('Not connected', 'keyy'),
		);
		foreach ($values as $value => $description) {
			if (!empty($_REQUEST['keyy_status']) && $value == $_REQUEST['keyy_status']) {
				echo '<option value="'.$value.'" selected="selected">'.$description.'</option>';
			} else {
				echo '<option value="'.$value.'">'.$description.'</option>';
			}
		}
		echo '</select>';
		echo '<input id="post-query-submit" type="submit" class="button" value="'.esc_attr(__('Filter', 'keyy')).'" name="">';
	}
	
	/**
	 * Directly render a notice about an insufficient PHP version
	 */
	public function admin_notice_insufficient_php() {
		$this->show_admin_warning('<strong>'.__('Higher PHP version required', 'keyy').'</strong><br> '.sprintf(__('The %s plugin requires %s version %s or higher - your current version is only %s.', 'keyy'), 'Keyy', 'PHP', self::PHP_REQUIRED, PHP_VERSION), 'error');
	}

	public function admin_notice_for_multisite() {
		$this->show_admin_warning('<strong>'.__('Multisite is a Keyy Premium feature', 'keyy').'</strong><br> '.sprintf(__('Did you know that <a href="https://getkeyy.com/premium-features/">Keyy Premium</a> now supports multisite? Multisite is a premium feature only. Please upgrade using <a href="https://getkeyy.com/buy/">this link</a>.', 'keyy')), 'error');
		add_filter((is_multisite() ? 'network_admin_' : '') . 'plugin_action_links', array($this, 'plugin_action_links'), 10, 2);
	}

	/**
	 * Directly render a notice about the plugin being disabled via a constant, and how to re-enable it
	 */
	public function admin_notice_disabled() {
		if (!current_user_can('manage_options')) return;
		$this->show_admin_warning('<strong>'.__('Keyy disabled', 'keyy').'</strong><br> '.sprintf(__('The %s plugin has been disabled via the %s constant - to re-enable it, remove this constant from your WordPress configuration.', 'keyy'), 'Keyy', 'KEYY_DISABLE'), 'error');
	}

	/**
	 * Directly render a notice about the plugin being disabled via REST being disabled, and how to re-enable it
	 */
	public function admin_notice_rest_disabled() {
		if (!current_user_can('manage_options')) return;
		$this->show_admin_warning('<strong>'.__('Keyy disabled', 'keyy').'</strong><br> '.sprintf(__('The %s plugin cannot work, because the REST interface has been explicitly turned off on this site; probably by another plugin. You will need to enable the WordPress REST interface to use %s.', 'keyy'), 'Keyy', 'Keyy'), 'error');
	}

	/**
	 * Directly render a notice about an insufficient WP version
	 */
	public function admin_notice_insufficient_wp() {
		include ABSPATH.WPINC.'/version.php';
		$this->show_admin_warning('<strong>'.__('Higher WordPress version required', 'keyy').'</strong><br> '.sprintf(__('The %s plugin requires %s version %s or higher - your current version is only %s.', 'keyy'), 'Keyy', 'WordPress', self::WP_REQUIRED, $wp_version), 'error');// phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable -- Global Wordpress Variable
	}

	/**
	 * Render a notice if an outdated app version is detected
	 */
	public function admin_notice_outdated_app() {

		$screen = get_current_screen();
		if (!in_array($screen->id, array('settings_page_keyy-admin'))) return;

		$user = wp_get_current_user();
		if (0 == $user->ID) return;

		$outdated = false;
		$now = time();

		$user_agents = $this->get_user_agent($user->ID);
		foreach ($user_agents as $user_agent => $time_seen) {

			// Ignore dismissed notices
			if ($now < $time_seen) continue;

			$outdated = $this->is_user_agent_outdated($user_agent);

			if ($outdated) {

				if (preg_match('/(Android|Linux|Dalvik)(.*)(Keyy\D+)(\d+.\d+.\d+)(.*)$/iu', trim($user_agent), $matches)) {
					$platform = __('Android', 'keyy');
					$version = $matches[4];
					$latest = self::ANDROID_LATEST;
				} elseif (preg_match('/(.*)(\D+)(\d+.\d+.\d+)(\D+)(iOS)/iu', trim($user_agent), $matches)) {
					$platform = __('iOS', 'keyy');
					$version = $matches[3];
					$latest = self::IOS_LATEST;
				} else {
					// We cant parse this UA, break
					break;
				}

				$this->show_admin_warning(sprintf(__('Your requests indicate that you are using an older version of the app. The version of the %s app we recently detected is %s whereas the latest available version is %s. Please update to get the latest features and changes.', 'keyy'), $platform, $version, $latest), 'notice-info keyy-ua-notice');
				
			}

		}
		

	}

	/**
	 * Directly render a notice about mod_security
	 */
	private function admin_notice_mod_security() {
	
		$this->show_admin_warning('<strong>'.sprintf(__('Potentially problematic webserver module installed (%s)', 'keyy'), 'mod_security').'</strong><br> '.__('If you have problems using Keyy, then you should check its configuration and logs, in case it is blocking Keyy.', 'keyy'), 'updated keyy-minor-notice');
	}

	/**
	 * Directly render a notice, according to the parameters
	 *
	 * @param String $message The HTML to echo as the message.
	 * @param String $class   The CSS class to put the surrounding div in.
	 */
	public function show_admin_warning($message, $class = 'updated') {
		echo '<div class="notice is-dismissible keyy_message '.$class.'">'."<p>$message</p></div>";
	}
	
	/**
	 * Whether Keyy wave functionality is enabled or not.
	 *
	 * @return Boolean
	 */
	public function is_wave_enabled() {
	
		$enabled = defined('KEYY_USE_WAVE') ? KEYY_USE_WAVE : true;
	
		return apply_filters('keyy_use_wave', $enabled);
	
	}
	
	/**
	 * The main purpose of this is to try to detect that REST is *disabled*. That can be done in many ways, so the results will not be 100% successful in catching all possibilities.
	 *
	 * @return Boolean
	 */
	private function is_rest_enabled() {
		
		// Disable via filter
		if (!apply_filters('rest_enabled', true)) return false;
		// N.B. The rest_enabled filter is deprecated now, and rest_authentication_errors is the official mechanism... but that invokes code that assumes that a REST call is in progress, which we can't do (and breaks the dashboard if we try).
		
		// Disable via blanking the URL
		if (function_exists('get_rest_url') && class_exists('WP_Rewrite')) {
			global $wp_rewrite;
			if (empty($wp_rewrite)) $wp_rewrite = new WP_Rewrite();
			if ('' == get_rest_url()) return false;
		}
		
		// Disable via removing from the <head> output, and non-default permalinks which mean that a default guess will not work
		if (false === has_action('wp_head', 'rest_output_link_wp_head')) {
			$permalink_structure = get_option('permalink_structure');
			if (!$permalink_structure || false !== strpos($permalink_structure, 'index.php')) {
				return false;
			}
		}
		
		// Plugins which, when active, disable REST. Do not add a plugin which merely has an *option* to disable REST. (For that, we will need further logic, to detect the option setting).
		$plugins = array(
			'disable-permanently-rest-api' => 'Disable Permanently REST API',
		);
		
		$slugs = array_keys($plugins);
		
		$active_plugins = get_option('active_plugins');
		if (!is_array($active_plugins)) $active_plugins = array();
		//if (is_multisite()) $active_plugins = array_merge($active_plugins, array_keys(get_site_option('active_sitewide_plugins', array())));

		// Loops around each plugin available.
		foreach ($active_plugins as $value) {
			if (!preg_match('#^([^/]+)/#', $value, $matches)) continue;
			if (in_array($matches[1], $slugs)) return false;
		}
		
		return true;
	}
	
	/**
	 * Runs on the plugins_loaded WordPress action, as long as the plugin is not (yet) going to abort all other operations (e.g. due to missing requirements)
	 */
	public function plugins_loaded_not_on_abort() {
	
		if (!$this->is_rest_enabled()) {
			add_action('all_admin_notices', array($this, 'admin_notice_rest_disabled'));
			return;
		}

		if(!is_multisite()){		
			add_action('admin_menu', array($this, 'admin_menu'));
			add_action('admin_bar_menu', array($this, 'admin_bar_menu'), 1);
		}

		//add_action('network_admin_menu', array($this, 'admin_menu'));
		
		add_action('wp_ajax_nopriv_keyy_ajax', array($this, 'nopriv_ajax_handler'));
		add_action('wp_ajax_keyy_ajax', array($this, 'priv_ajax_handler'));
		
		add_action('restrict_manage_users', array($this, 'restrict_manage_users'));
		add_action('pre_get_users', array($this, 'pre_get_users'));
		
		add_action('keyy_dashboard_header_after_notice', array($this, 'keyy_dashboard_header_after_notice'));
	}

	/**
	 * Runs on the keyy_dashboard_header_after_notice WP action
	 */
	public function keyy_dashboard_header_after_notice() {
	
		if (!function_exists('apache_get_modules')) return;
		
		$apache_modules = apache_get_modules();
		if (is_array($apache_modules)) {
			if (in_array('mod_security2', $apache_modules)) {
				$this->admin_notice_mod_security();
			}
		}
		

	}
	
	/**
	 * Runs on the plugins_loaded WordPress action, even if the plugin is going to abort all other operations (e.g. due to missing requirements)
	 */
	public function plugins_loaded_even_on_abort() {
		load_plugin_textdomain('keyy', false, KEYY_DIR.'/languages');
	}
	
	/**
	 * Given the home URL of a WP site, return its REST URL. If it cannot be found, a guess (based on the default setup) will be returned.
	 *
	 * @param String  $home_url - the WP site's home-page (though, any front-end page will work)
	 * @param Integer $timeout	- timeout, in seconds
	 *
	 * @return String - the REST URL
	 */
	public function get_rest_url($home_url, $timeout = 7) {
	
		$home_page = wp_remote_get($home_url, array('timeout' => $timeout));
	
		if (!is_wp_error($home_page)) {
		
			$body = wp_remote_retrieve_body($home_page);
			
			if (is_string($body) && preg_match_all('#<link\s+(href|rel)=[\'"](.+)[\'"]\s+(rel|href)=[\'"](.+)[\'"]#i', $body, $matches, PREG_SET_ORDER)) {
				foreach ($matches as $match) {

					if (('rel' == $match[1] && 'https://api.w.org/' == trailingslashit($match[2])) || ('rel' == $match[3] && 'https://api.w.org/' == trailingslashit($match[4]))) {
					
						return ('rel' == $match[1]) ? $match[4] : $match[2];
					
					}
				}
			}
		}
		
		// The default
		return trailingslashit($home_url).'wp-json/';
	
	}
	
	/**
	 * Runs on the admin_menu WordPress action
	 */
	public function admin_menu() {
	
		add_menu_page(
			__('Keyy Login', 'keyy'),
			__('Keyy Login', 'keyy'),
			$this->capability_required(),
			'keyy',
			array($this, 'options_page'),
			KEYY_URL.'/images/admin_icon_16x16.png',
			72
		);
		
	}
	
	/**
	 * Runs on the admin_bar_menu WordPress action
	 *
	 * @param  array $wp_admin_bar
	 */
	public function admin_bar_menu($wp_admin_bar) {
	
		if (!get_current_user_id()) return;
		
		$wp_admin_bar->add_menu(array(
			'parent' => 'user-actions',
			'id'     => 'keyy',
			'title'  => __('Keyy Login', 'keyy'),
			'href'   => admin_url('admin.php?page=keyy')
		));
	
	}
	
	/**
	 * Returns an array of URLs to use in templates (preventing duplication)
	 *
	 * @return array
	 */
	public function get_common_urls() {
		return apply_filters('keyy_common_urls', array(
			'home_page' => '<a href="https://getkeyy.com">'.__('Keyy website', 'keyy').'</a>',
			'home_url' => 'https://getkeyy.com',
			'keyy_premium' => 'https://getkeyy.com/premium-features/',
			'keyy_premium_shop' => 'https://getkeyy.com/buy/',
			'wp_plugin' => 'https://wordpress.org/plugin/keyy/',
			'support_forum' => 'https://wordpress.org/support/plugin/keyy/',
			'support' => 'https://getkeyy.com/support/',
			'faqs' => 'https://getkeyy.com/faqs/',
			'faq_how_to_disable' => 'https://getkeyy.com/faqs/how-can-i-disable-keyy-on-a-website/',
			'upcoming_features' => 'https://getkeyy.com/keyy-version-1/',
			'review_url' => 'https://wordpress.org/support/plugin/keyy/reviews/?rate=5#new-post',
			'android_app' => 'https://play.google.com/store/apps/details?id=com.updraftplus.keyy',
			'ios_app' => 'https://itunes.apple.com/us/app/keyy/id1226408587?ls=1&mt=8',
			'updraftplus_landing' => 'https://updraftplus.com',
			'updraftcentral_landing' => 'https://updraftcentral.com',
			'wp_optimize_landing' => 'https://updraftplus.com/wp-optimize/',
			'metaslider_landing' => 'https://www.metaslider.com/',
			'simba_plugins_landing' => 'https://www.simbahosting.co.uk/s3/shop/',
			// TODO
			'sso_information' => 'https://getkeyy.com',
		));
	}
	
	/**
	 * Renders the output for the WP dashboard page
	 */
	public function options_page() {
	
		$this->enqueue_scripts('admin');
	
		$extract_these = $this->get_common_urls();
		
		$extract_these['which_page'] = 'admin';
		
		$this->include_template('dashboard-header.php', false, $extract_these);

		// TODO - not yet handled on the login side
		$extract_these['disable_url'] = $this->get_disable_url();
		
		$this->include_template('dashboard-page.php', false, $extract_these);
		
		do_action('keyy_after_dashboard_page');
	}
	
	/**
	 * Get the (secret) URL for disabling Keyy on a login form
	 */
	public function get_disable_url() {
		return wp_login_url().'?keyy_disable='.$this->get_disable_key();
	}
	
	/**
	 * Get the (secret) key for disabling Keyy on a login form
	 *
	 * @param Boolean $force_refresh - whether to reset the key even if there was an existing one
	 */
	public function get_disable_key($force_refresh = false) {
		$key = Keyy_Options::get_option('keyy_disable_keyy');
		if ($force_refresh || !is_string($key) || empty($key)) {
			$key = $this->get_random_string(32);
			Keyy_Options::update_option('keyy_disable_keyy', $key);
		}
		return $key;
	}
	
	/**
	 * Get a random string
	 *
	 * @param Integer $length - how many characters long
	 *
	 * @return String - the random string
	 */
	function get_random_string($length) {
		$dictionary = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$dictionary_length = strlen($dictionary);
		$output = '';
		for ($i = 0; $i < $length; $i++) {
			$output .= $dictionary[rand(0, $dictionary_length-1)];
		}
		return $output;
	}
	
	/**
	 * Renders or returns the requested template
	 *
	 * @param  String  $path 				   the path, relative to this plugin's template directory, of the template to use.
	 * @param  Boolean $return_instead_of_echo whether to return the output instead of echoing it.
	 * @param  Array   $extract_these 		   an array of key/value pairs of variables to provide in the variable scope when the template runs.
	 * @return String|void - the results of running the template, if $return_instead_of_echo was set.
	 */
	public function include_template($path, $return_instead_of_echo = false, $extract_these = array()) {
	
		// Lazy-load: get them the first time that we need them
		if (!is_array($this->template_directories)) $this->register_template_directories();
	
		if ($return_instead_of_echo) ob_start();

		if (preg_match('#^([^/]+)/(.*)$#', $path, $matches)) {
			$prefix = $matches[1];
			$suffix = $matches[2];
			if (isset($this->template_directories[$prefix])) {
				$template_file = $this->template_directories[$prefix].'/'.$suffix;
			}
		}

		if (!isset($template_file)) {
			$template_file = KEYY_DIR.'/templates/'.$path;
		}

		$template_file = apply_filters('keyy_template', $template_file, $path);

		do_action('keyy_before_template', $path, $template_file, $return_instead_of_echo, $extract_these);

		if (!file_exists($template_file)) {
			error_log("Keyy: template not found: $template_file");
			echo __('Error:', 'keyy').' '.__('template not found', 'keyy')." ($path)";
		} else {
			extract($extract_these);
			// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable -- Need to be ignored as they are infact used within the templates
			$keyy = $this;
			$keyy_login = $this->login_instance();
			$keyy_notices = $this->get_notices();
			// phpcs:enable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
			include $template_file;
		}

		do_action('keyy_after_template', $path, $template_file, $return_instead_of_echo, $extract_these);

		if ($return_instead_of_echo) return ob_get_clean();
	}
	
	/**
	 * Get the directory to find templates in
	 *
	 * @return String - the template directory
	 */
	public function get_templates_dir() {
		return apply_filters('keyy_templates_dir', wp_normalize_path(KEYY_DIR.'/templates'));
	}

	/**
	 * This method is run to build up an internal list of available templates
	 */
	private function register_template_directories() {

		$template_directories = array();

		$templates_dir = $this->get_templates_dir();

		if ($dh = opendir($templates_dir)) {
			while (($file = readdir($dh)) !== false) {
				if ('.' == $file || '..' == $file) continue;
				if (is_dir($templates_dir.'/'.$file)) {
					$template_directories[$file] = $templates_dir.'/'.$file;
				}
			}
			closedir($dh);
		}

		// This is the optimal hook for most extensions to hook into.
		$this->template_directories = apply_filters('keyy_template_directories', $template_directories);

	}
	
	/**
	 * This will customize a URL with a correct Affiliate link
	 * This function can be updated to suit any URL as long as the URL is passed
	 *
	 * @param string $url   URL to be check to see if it an updraftplus match.
	 * @param string $text  Text to be entered within the href a tags.
	 * @param string $html  Any specific HTML to be added.
	 * @param string $class Specify a class for the href.
	 */
	public function keyy_url($url, $text, $html = null, $class = null) {
		// Check if the URL is UpdraftPlus.
		if (false !== strpos($url, '//updraftplus.com')) {
			// Set URL with Affiliate ID.
			$url = $url.'?afref='.$this->get_notices()->get_affiliate_id();

			// Apply filters.
			$url = apply_filters('keyy_updraftplus_com_link', $url);
		}
		// Return URL - check if there is HTMl such as Images.
		if (!empty($html)) {
			echo '<a '.$class.' href="'.esc_attr($url).'">'.$html.'</a>';
		} else {
			echo '<a '.$class.' href="'.esc_attr($url).'">'.htmlspecialchars($text).'</a>';
		}
	}

	/**
	 * Detect URLs that seem like localhost.
	 * Does not have to (and should not be relied upon to) be able to infallibly detect
	 *
	 * @param String $url - the URL.
	 * @return Boolean
	 */
	public function url_looks_internal($url) {
	
		$url_host = strtolower(parse_url($url, PHP_URL_HOST));
		
		if (0 === strpos($url_host, 'localhost') || strpos($url_host, '127.') === 0 || strpos($url_host, '10.') === 0 || '::1' == $url_host || substr($url_host, -10, 10) == '.localhost' || substr($url_host, -4, 4) == '.dev' || '.localdomain' == substr($url_host, -12, 12)) return true;
		
		// Provide a define in case of time-outs.
		if (function_exists('dns_get_record') && (!defined('KEYY_DO_DNS_LOOKUP') || !KEYY_DO_DNS_LOOKUP)) {
			$results = dns_get_record($url_host);
			
			if (is_array($results)) {
				foreach ($results as $result) {
					if (isset($result['ip'])) {
						$ip = $result['ip'];
					
						if (strpos($ip, '127.') === 0 || strpos($ip, '10.') === 0 || '::1' == $ip || strpos($ip, '192.168.') === 0) {
							return true;
						}
					}
				}
			}
		}

		return false;
	}
	
	
	/**
	 * Ensure that the phpseclib library is loaded - specifically, the Crypt_RSA and Crypt_Hash classes
	 */
	public function load_rsa_functions() {
	
		if (class_exists('Crypt_RSA') && class_exists('Crypt_Hash')) return;
	
		if (false === strpos(get_include_path(), KEYY_DIR.'/vendor/phpseclib/phpseclib/phpseclib')) set_include_path(KEYY_DIR.'/vendor/phpseclib/phpseclib/phpseclib'.PATH_SEPARATOR.get_include_path());

		if (!class_exists('Crypt_RSA')) include_once 'Crypt/RSA.php';
		if (!class_exists('Crypt_Hash')) include_once 'Crypt/Hash.php';
		
	}
	
	/**
	 * Run this method to enqueue scripts suitable for the specified page
	 *
	 * @param String $page - 'login', 'sso', 'shared' (used because there's no separate/dedicated script just for connect codes) and 'admin' are the 'native' types; others can be used through filtering.
	 */
	public function enqueue_scripts($page = 'login') {

		// N.B. The order of these next three logic blocks is important.
	
		// Prevent loading unwanted resources (which may clash with 'shared' resources for connect codes)
		if ('login' == $page && is_user_logged_in()) return;
	
		static $enqueued = array();
		if (isset($enqueued[$page])) return;
		$enqueued[$page] = true;

		//@codingStandardsIgnoreLine
		if (!empty($enqueued['login']) && !empty($enqueued['shared'])) trigger_error("Keyy: Both the 'login' and 'shared' scripts have been enqueued. This is not supported.", E_USER_WARNING);

		$script_version = (defined('WP_DEBUG') && WP_DEBUG) ? self::VERSION.'.'.time() : self::VERSION;
		
		$min_or_not = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';
		
		$scripts = array();
		
		$scripts[] = array(
			'handle' => 'kjua',
			'script' => 'kjua/kjua',
			'version' => '0.1.2',
			'has_min_only' => true
		);

		$scripts[] = array(
			'handle' => 'sprintf',
			'script' => 'sprintf/sprintf',
			'has_min' => true,
		);

		$scripts[] = array(
			'handle' => 'jsbarcode',
			'script' => 'jsbarcode/JsBarcode.code128',
			'version' => '0.1.2',
			'has_min_only' => true
		);
			
		$scripts[] = array(
			'handle' => 'keyy-shared',
			'script' => 'shared',
			'deps' => array('kjua', 'jsbarcode', 'sprintf', 'jquery'),
			'final' => ('shared' === $page)
		);

		if ('login' == $page) {
			$scripts[] = array(
				'handle' => 'keyy-login-form',
				'script' => 'login-form',
				'deps' => array('keyy-shared'),
				'final' => true
			);
			
			$scripts[] = array(
				'type' => 'css',
				'handle' => 'keyy-form',
				'script' => 'login-form',
				'final' => true
			);
		} else {
		
			$scripts[] = array(
				'type' => 'css',
				'handle' => 'keyy-admin',
				'script' => 'admin',
				'final' => true
			);
		
			if ('admin' == $page) {
				$scripts[] = array(
					'handle' => 'keyy-admin',
					'script' => 'admin',
					'deps' => array('keyy-shared'),
					'final' => true
				);
				
			} elseif ('sso' == $page) {
				$scripts[] = array(
					'handle' => 'keyy-sso',
					'script' => 'sso',
					'deps' => array('jquery'),
					'final' => true
				);
			}
		}
		
		$scripts = apply_filters('keyy_enqueue_scripts', $scripts, $page, $script_version, $min_or_not);
		
		do_action('keyy_before_enqueue_scripts', $page, $script_version, $min_or_not, $scripts);
		
		foreach ($scripts as $script) {
		
			$version = isset($script['version']) ? $script['version'] : $script_version;
			$deps = isset($script['deps']) ? $script['deps'] : array();
			$min_ext = !empty($script['has_min_only']) ? '.min' : (empty($script['has_min']) ? '' : $min_or_not);
			$type = empty($script['type']) ? 'js' : $script['type'];
			$url_prefix = empty($script['url_prefix']) ? KEYY_URL : $script['url_prefix'];
			
			if (empty($script['final'])) {
				if ('css' == $type) {
					wp_register_style($script['handle'], $url_prefix.'/css/'.$script['script'].$min_ext.'.css', $deps, $version);
				} else {
					wp_register_script($script['handle'], $url_prefix.'/js/'.$script['script'].$min_ext.'.js', $deps, $version);
				}
			} else {
				if ('css' == $type) {
					wp_enqueue_style($script['handle'], $url_prefix.'/css/'.$script['script'].$min_ext.'.css', $deps, $version);
				} else {
					wp_enqueue_script($script['handle'], $url_prefix.'/js/'.$script['script'].$min_ext.'.js', $deps, $version);
				}
			}
		}
		
		do_action('keyy_after_enqueue_scripts', $page, $script_version, $min_or_not);
		
		$is_disabled = (defined('KEYY_DISABLE') && KEYY_DISABLE);
		
		if ('admin' == $page || 'shared' == $page) {
		
			$keyy_login = $this->login_instance();
			
			$connection_token = $keyy_login->get_connection_token();
			
			$seconds_until_expiry = ($connection_token['expiry_time'] - time());
			
			// This spares us from having to assume that the browser has accurate time (we have to assume that the server does, but this is more likely).
			$connection_token['expires_after'] = $seconds_until_expiry;
			
			$pass_to_js = array(
				'ajax_url' => admin_url('admin-ajax.php', 'relative'),
				'ajax_nonce' => wp_create_nonce('keyy-ajax-nonce'),
				'qr_url' => $this->get_qr_url('connect'),
				'connected' => $keyy_login->is_configured_for_user(),
				'connection_token' => $connection_token,
				'connection_result' => $keyy_login->get_connection_result(),
				'is_disabled' => $is_disabled,
				'debug' => (defined('KEYY_DEBUG') && KEYY_DEBUG),
				'context' => 'connect',
			);
		
			$localize = array_merge(
				$pass_to_js,
				include(KEYY_DIR.'/includes/translations.php')
			);

			wp_localize_script('keyy-'.$page, 'keyy', apply_filters('keyy_'.$page.'_lion', $localize));
			
		} elseif ('login' == $page) {
		
			$keyy_login = $this->login_instance();
			
			$login_token = $keyy_login->get_fresh_login_token();
			
			$seconds_until_expiry = ($login_token['expiry_time'] - time());
			
			// This spares us from having to assume that the browser has accurate time (we have to assume that the server does, but this is more likely).
			$login_token['expires_after'] = $seconds_until_expiry;
			
			// The token and URL are variable elements.
			if (!defined('DONOTCACHEPAGE')) define('DONOTCACHEPAGE', true);
			
			$common_urls = $this->get_common_urls();
			
			$hook_forms = array(
				'wp' => array(
					'selector' => '#loginform',
				),
			);
			
			if (!empty($_GET['keyy_disable']) && $_GET['keyy_disable'] === $this->get_disable_key()) {
				error_log("Keyy secret disable URL visited");
				$is_disabled = true;
			}

			$hook_forms_wp_initial_hide_value = apply_filters('keyy_hook_forms_initial_hide_wp_value', '#user_login, label[for="user_login"], #user_pass, label[for="user_pass"]');
			
			if (!$is_disabled && $hook_forms_wp_initial_hide_value && (!defined('KEYY_HIDE_NORMAL_FIELDS_WHEN_ALL_USERS_USING') || KEYY_HIDE_NORMAL_FIELDS_WHEN_ALL_USERS_USING) && $keyy_login->all_users_are_using_keyy(false)) {
			
				$hook_forms['wp']['hide'] = $hook_forms_wp_initial_hide_value;
				
			}
			
			$pass_to_js = array(
				'ajax_url' => admin_url('admin-ajax.php', 'relative'),
				'ajax_nonce' => wp_create_nonce('keyy-ajax-nonce'),
				'qr_url' => $this->get_qr_url('login'),
				'login_token' => $login_token,
				'stealth_mode' => apply_filters('keyy_login_stealth_mode', false),
				'is_disabled' => $is_disabled,
				'context' => 'login',
				'debug' => (defined('KEYY_DEBUG') && KEYY_DEBUG),
				'hook_forms' => $hook_forms,
				'use_wave' => $this->is_wave_enabled(),
				'site_hash' => $this->get_site_hash(),
				'wave_colour' => Keyy_Options::get_option('wave_colour', '#da521b'),
				'keyy_logo_icon' => KEYY_URL.'/images/keyy-logo.png',
				'keyy_thumbs_up' => KEYY_URL.'/images/thumbs-up.svg',
				'learn_more_template' => $this->include_template('login-form-learn-more.php', true, $common_urls),
			);

			$localize = array_merge(
				$pass_to_js,
				include(KEYY_DIR.'/includes/translations.php')
			);

			wp_localize_script('keyy-login-form', 'keyy', apply_filters('keyy_login_form_lion', $localize, $is_disabled));
			
		}
		
	}
	
	/**
	 * Gets HTML for a front-end connection box
	 *
	 * @return String - the HTML
	 */
	public function get_frontend_connection_code() {

		if (is_user_logged_in()) {
		
			$keyy_login = $this->login_instance();
		
			if ($keyy_login->is_configured_for_user()) {
				$settings = $keyy_login->get_user_settings();
				$email = isset($settings['email']) ? $settings['email'] : __('Unknown', 'keyy');
				return sprintf(__('This user is connected to the Keyy account belonging to %s.', 'keyy'), htmlspecialchars($email)).' ';
			}
		} else {
			// TODO: Unfinished - we want to eventually support non-logged-in users too.
			return '';
		}

		return '<div id="keyy-connect-frontend-container"><div id="keyy_connect_frontend_qrcode" class="keyy_connect_qrcode keyy_qrcode"></div></div>';
	
	}


	
	/**
	 * Produce a normalised version of a URL, useful for comparisons. This may produce a URL that does not actually reference the same location; its purpose is only to use in comparisons of two URLs that *both* go through this function.
	 *
	 * @param String $url - the URL
	 *
	 * @return String - normalised
	 */
	public function normalise_url($url) {
		$parsed_descrip_url = parse_url($url);
		if (is_array($parsed_descrip_url)) {
			if (preg_match('/^www\./i', $parsed_descrip_url['host'], $matches)) $parsed_descrip_url['host'] = substr($parsed_descrip_url['host'], 4);
			$normalised_descrip_url = 'http://'.strtolower($parsed_descrip_url['host']);
			if (!empty($parsed_descrip_url['port'])) $normalised_descrip_url .= ':'.$parsed_descrip_url['port'];
			if (!empty($parsed_descrip_url['path'])) $normalised_descrip_url .= untrailingslashit($parsed_descrip_url['path']);
		} else {
			$normalised_descrip_url = untrailingslashit($url);
		}
		return $normalised_descrip_url;
	}

	/**
	 * Returns the site hash
	 *
	 * @return String - Current site hash
	 */
	public function get_site_hash() {

		$site_url = $this->normalise_url(home_url());
		$site_hash = $this->convert_crc32_to_code128(hash("crc32b", $site_url));
		
		return $site_hash;
	}

	/**
	 * Produces a code 128 safe representation of the URL,
	 *
	 * @param String $as_hex - A CRC32 hash of the site url
	 *
	 * @return String - Site hash
	 */
	private function convert_crc32_to_code128($as_hex) {

		$result = '';

		// We ue a 76-character alphabet which is a subset of code-128
		$alphabet = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!\"'()*-._`{}~'";

		// Knock out the highest bit
		if (8 == strlen($as_hex)) {
			$highest = substr($as_hex, 0, 1);
			if (preg_match('/^[fedcba98]$/i', $highest)) {
				$as_hex = str_replace(
					array('f', 'e', 'd', 'c', 'b', 'a', '9', '8'),
					array('7', '6', '5', '4', '3', '2', '1', '0'),
					$highest
				). substr($as_hex, 1);
			}
		}

		$working_on = hexdec($as_hex);

		for ($i=0; $i<=4; $i++) {
			$remainder = $working_on % 76;
			$working_on = $working_on - $remainder;
			$working_on = $working_on / 76;
			$result .= $alphabet[$remainder];
		}

		return $result;
	}

	/**
	 * Get a URL suitable for encoding in a QR code, corresponding to the indicated or current user
	 * e.g.
	 * https://user@example.com/wp
	 *
	 * @param  String		   $context Either 'connect' or 'login'.
	 * @param  WP_User|Boolean $user    Either the user to return a URL for, or false for the currently logged-in user
	 * @return String|Boolean - The URL, or false in the case of failure (e.g. user not logged in).
	 */
	public function get_qr_url($context = 'connect', $user = false) {
	
		$home_url = trailingslashit(trim(home_url()));
		
		if ('connect' == $context) {
		
			if (false === $user) {
				if (!is_user_logged_in()) return false;
				$user = wp_get_current_user();
			}
		
			$user_login = $user->user_login;
			if (preg_match('#^(https?:)?//(.*)#', $home_url, $match_schema)) {
				if (strlen($match_schema[1]) > 0) {
					$qr_url = $match_schema[1].'//'.rawurlencode($user_login).'@'.$match_schema[2];
				} else {
					error_log("Keyy: The home_url appears to lack a scheme: $home_url");
					$qr_url = 'http://'.rawurlencode($user_login).'@'.$match_schema[2];
				}
			} else {
				// Just do our best
				error_log("Keyy: difficulty parsing the home_url (should be reported as a support issue): ".$home_url);
				$qr_url = rawurlencode($user_login).'@'.$home_url;
			}
			
		} else {
			$qr_url = $home_url;
		}
		
		return apply_filters('keyy_get_qr_url', $qr_url);
	}
	
	/**
	 * Return the URL used for API calls
	 *
	 * @return String
	 */
	public function get_api_url() {
	
		return apply_filters('keyy_get_api_url', 'https://'.self::API_SERVER.'/v1/');
	}
	
	/**
	 * Make an API call, and return the results.
	 *
	 * @param  String $command The API call to make.
	 * @param  Array  $data    Any associated data.
	 * @return Array           The result.
	 */
	public function api_call($command, $data = null) {
	
		$api_url = $this->get_api_url();
		
		$args = array(
			'command' => $command,
			'data' => $data
		);
		
		$result = wp_remote_post($api_url, $args);
		
		return apply_filters('keyy_api_call_result', $result, $command, $data);
	}

	/**
	 * WordPress filter (network_admin_)plugin_action_links. Adds the settings link under the plugin on the plugin screen.
	 *
	 * @param  Array  $links Array of links
	 * @param  String $file  Plugin file path
	 * @return Array  Filtered array of links
	 */
	public function plugin_action_links($links, $file) {
		$common_urls = $this->get_common_urls();
		$keyy_buy = $common_urls['keyy_premium_shop'];
		if (is_array($links) && 0 === strcasecmp('keyy/keyy.php', $file)) {
			if(!is_multisite()){
				$settings_link = sprintf('<a href="%1$s">%2$s</a>', admin_url('?page=keyy'), __('Settings', 'keyy'));
				array_unshift($links, $settings_link);
			}
			$settings_link = sprintf('<a href="%1$s">%2$s</a>', $keyy_buy, __('Premium Upgrade', 'keyy'));
			array_unshift($links, $settings_link);
		}
		if (is_array($links) && 0 === strcasecmp('keyy-premium/keyy.php', $file)) {
			if(!is_multisite()){
				$settings_link = sprintf('<a href="%1$s">%2$s</a>', admin_url('?page=keyy'), __('Login Settings', 'keyy'));
				array_unshift($links, $settings_link);
			}
			$settings_link = sprintf('<a href="%1$s">%2$s</a>', admin_url('options-general.php?page=keyy-admin'), __('Admin Settings', 'keyy'));
			array_unshift($links, $settings_link);
		}
		return $links;
	}

	/**
	 * If the HTTP user-agent header is set, then log it for further processing.
	 *
	 * @param WP_User $user - the WordPress user object
	 */
	public function log_user_agent($user) {
	
		if (empty($_SERVER['HTTP_USER_AGENT']) || !is_string($_SERVER['HTTP_USER_AGENT'])) return;

		$current_device = preg_match('/\((.*)\)/', $_SERVER['HTTP_USER_AGENT'], $matches) ? $matches[1] : false;
		
		$user_agents = get_user_meta($user->ID, self::META_KEY_USER_AGENTS, true);
	
		if (!is_array($user_agents)) $user_agents = array();
		
		$a_while_ago = time() - 86400 * 7;
	
		foreach ($user_agents as $agent => $time_seen) {

			// Remove stale entries
			if ($time_seen < $a_while_ago) unset($user_agents[$agent]);

			// Remove older entry (UA) if user has updated their app since
			if (true === strpos($agent, $current_device) && $current_device) unset($user_agents[$agent]);
		}
		
		$user_agents[$_SERVER['HTTP_USER_AGENT']] = time();
		
		update_user_meta($user->ID, self::META_KEY_USER_AGENTS, $user_agents);
	
	}

	/**
	 * Gets the user's user agent information
	 *
	 * @param Integer $user_id - the ID for the WordPress user to fetch information for
	 *
	 * @return Array - an array of user agents and the last time seen
	 */
	public function get_user_agent($user_id) {
	
		$user_agents = get_user_meta($user_id, self::META_KEY_USER_AGENTS, true);
	
		return is_array($user_agents) ? $user_agents : array();
		
	}

	/**
	 * Parse the user's user agent information to check if its outdated
	 *
	 * @param String $user_agent - the user agent string
	 *
	 * @return Boolean - if the user agent is the latest or not
	 */
	public function is_user_agent_outdated($user_agent) {
	
		if (preg_match('/(Android|Linux|Dalvik)(.*)(Keyy\D+)(\d+.\d+.\d+)(.*)$/iu', trim($user_agent), $matches)) {
			return version_compare($matches[4], self::ANDROID_LATEST, '<');
		}

		if (preg_match('/(.*)(\D+)(\d+.\d+.\d+)(\D+)(iOS)/iu', trim($user_agent), $matches)) {
			return version_compare($matches[3], self::IOS_LATEST, '<');
		}

		return false;
	}
}

/**
 * Returns the singleton Keyy_Login_Plugin class
 *
 * @return Keyy_Login_Plugin
 */
function Keyy_Login_Plugin() {
	return Keyy_Login_Plugin::instance();
}

$GLOBALS['keyy_login_plugin'] = Keyy_Login_Plugin();
endif;
