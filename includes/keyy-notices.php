	<?php

if (!defined('KEYY_DIR')) die('No direct access allowed');

if (!class_exists('Updraft_Notices_1_0')) require_once(KEYY_DIR.'/includes/updraft-notices.php');

class Keyy_Notices extends Updraft_Notices_1_0 {

	protected static $_instance = null;

	private $initialized = false;

	protected $self_affiliate_id = 216;

	protected $notices_content = array();

	/**
	 * Creates and returns the only notice instance
	 *
	 * @return a Keyy_Notices instance
	 */
	public static function instance() {
		if (empty(self::$_instance)) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * This method gets any parent notices and adds its own notices to the notice array
	 *
	 * @return Array returns an array of notices
	 */
	protected function populate_notices_content() {
		
		$parent_notice_content = parent::populate_notices_content();

		$keyy = Keyy_Login_Plugin();
		
		$common_urls = $keyy->get_common_urls();
		
		$child_notice_content = array(
			1 => array(
				'prefix' => '',
				'title' => __('Upgrade to Keyy Premium', 'keyy'),
				'text' => __('More sites, more users, more features.', 'keyy'),
				'image' => 'notices/keyy_logo.png',
				'button_link' => $common_urls['keyy_premium_shop'],
				'button_meta' => 'keyy_premium',
				'dismiss_time' => 'dismiss_page_notice_until',
				'supported_positions' => $this->anywhere,
			),
			'rate_plugin' => array(
				'prefix' => '',
				'title' => __('Like Keyy and can spare one minute?', 'keyy'),
				'text' => __('Please help Keyy by giving a positive review at wordpress.org.', 'keyy'),
				'image' => 'notices/keyy_logo.png',
				'button_link' => $common_urls['review_url'],
				'button_meta' => 'review',
				'dismiss_time' => 'dismiss_page_notice_until',
				'supported_positions' => $this->anywhere,
			),
			'translation_needed' => array(
				'prefix' => '',
				'title' => 'Can you translate? Want to improve Keyy for speakers of your language?',
				'text' => $this->url_start(true, 'translate.wordpress.org/projects/wp-plugins/keyy')."Please go here for instructions - it is easy.".$this->url_end(true, 'translate.wordpress.org/projects/wp-plugins/keyy'),
				'text_plain' => $this->url_start(false, 'translate.wordpress.org/projects/wp-plugins/keyy')."Please go here for instructions - it is easy.".$this->url_end(false, 'translate.wordpress.org/projects/wp-plugins/keyy'),
				'image' => 'notices/keyy_logo.png',
				'button_link' => false,
				'dismiss_time' => false,
				'supported_positions' => $this->anywhere,
				'validity_function' => 'translation_needed',
			),
			// The sale adverts content starts here
			'blackfriday' => array(
				'prefix' => '',
				'title' => __('Black Friday - 20% off Keyy Premium until November 30th', 'keyy'),
				'text' => __('To benefit, use this discount code:', 'keyy') . ' ',
				'image' => 'notices/black_friday.png',
				'button_link' => $common_urls['keyy_premium'],
				'button_meta' => 'keyy_premium',
				'dismiss_time' => 'dismiss_seasonal_notice_until',
				'discount_code' => 'blackfridaysale2019',
				'valid_from' => '2019-11-20 00:00:00',
				'valid_to' => '2019-11-30 23:59:59',
				'supported_positions' => $this->dashboard_top,
			),
			'christmas' => array(
				'prefix' => '',
				'title' => __('Christmas sale - 20% off Keyy Premium until December 25th', 'keyy'),
				'text' => __('To benefit, use this discount code:', 'keyy') . ' ',
				'image' => 'notices/christmas.png',
				'button_link' => $common_urls['keyy_premium'],
				'button_meta' => 'keyy_premium',
				'dismiss_time' => 'dismiss_seasonal_notice_until',
				'discount_code' => 'christmassale2019',
				'valid_from' => '2019-12-01 00:00:00',
				'valid_to' => '2019-12-25 23:59:59',
				'supported_positions' => $this->dashboard_top,
			),
			'newyear' => array(
				'prefix' => '',
				'title' => __('Happy New Year - 20% off Keyy Premium until January 1st', 'keyy'),
				'text' => __('To benefit, use this discount code:', 'keyy') . ' ',
				'image' => 'notices/new_year.png',
				'button_link' => $common_urls['keyy_premium'],
				'button_meta' => 'keyy_premium',
				'dismiss_time' => 'dismiss_seasonal_notice_until',
				'discount_code' => 'newyearsale2020',
				'valid_from' => '2019-12-26 00:00:00',
				'valid_to' => '2020-01-14 23:59:59',
				'supported_positions' => $this->dashboard_top,
			),
			'spring' => array(
				'prefix' => '',
				'title' => __('Spring sale - 20% off Keyy Premium until April 30th', 'keyy'),
				'text' => __('To benefit, use this discount code:', 'keyy') . ' ',
				'image' => 'notices/spring.png',
				'button_link' => $common_urls['keyy_premium'],
				'button_meta' => 'keyy_premium',
				'dismiss_time' => 'dismiss_seasonal_notice_until',
				'discount_code' => 'springsale2019',
				'valid_from' => '2019-04-01 00:00:00',
				'valid_to' => '2019-04-30 23:59:59',
				'supported_positions' => $this->dashboard_top,
			),
			'summer' => array(
				'prefix' => '',
				'title' => __('Summer sale - 20% off Keyy Premium until July 31st', 'keyy'),
				'text' => __('To benefit, use this discount code:', 'keyy') . ' ',
				'image' => 'notices/summer.png',
				'button_link' => $common_urls['keyy_premium'],
				'button_meta' => 'keyy_premium',
				'dismiss_time' => 'dismiss_seasonal_notice_until',
				'discount_code' => 'summersale2019',
				'valid_from' => '2019-07-01 00:00:00',
				'valid_to' => '2019-07-31 23:59:59',
				'supported_positions' => $this->dashboard_top,
			)
		);

		return array_merge($parent_notice_content, $child_notice_content);
	}
	
	/**
	 * This method is called to setup the notices
	 */
	public function notices_init() {
		if ($this->initialized) return;
		$this->initialized = true;
		$this->notices_content = (defined('KEYY_NOADS_B') && KEYY_NOADS_B) ? array() : $this->populate_notices_content();
		$our_version = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? Keyy_Login_Plugin::VERSION.'.'.time() : Keyy_Login_Plugin::VERSION;
		$min_or_not = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';
		wp_enqueue_style('keyy-notices-css',  KEYY_URL.'/css/keyy-notices'.$min_or_not.'.css', array(), $our_version);
	}

	/**
	 * This method calls the parent verson with the base Keyy and will work out if the user is using a non english language and if so returns true so that they can see the translation advert.
	 *
	 * @param  String $plugin_base_dir the plugin base directory
	 * @param  String $product_name    the name of the plugin
	 * @return Boolean                 returns true if the user is using a non english language and could translate otherwise false
	 */
	protected function translation_needed($plugin_base_dir = null, $product_name = null) {
		return parent::translation_needed(KEYY_DIR, 'keyy');
	}
	
	/**
	 * This method is used to generate the correct URL output for the start of the URL
	 *
	 * @param  Boolean $html_allowed a boolean value to indicate if HTML can be used or not
	 * @param  String  $url          the url to use
	 * @param  Boolean $https        a boolean value to indicate if https should be used or not
	 * @param  String  $website_home a string to be displayed
	 * @return String                returns a string of the completed url
	 */
	protected function url_start($html_allowed, $url, $https = false, $website_home = 'getkeyy.com') {
		return parent::url_start($html_allowed, $url, $https, $website_home);
	}
	
	/**
	 * This method checks to see if the notices dismiss_time parameter has been dismissed
	 *
	 * @param  String $dismiss_time a string containing the dimiss time ID
	 * @return Boolaen              returns true if the notice has been dismissed and shouldn't be shown otherwise display it
	 */
	protected function check_notice_dismissed($dismiss_time) {

		$time_now = (defined('KEYY_NOTICES_FORCE_TIME') ? KEYY_NOTICES_FORCE_TIME : time());
		
		$notice_dismiss = ($time_now < Keyy_Options::get_option('dismiss_page_notice_until', 0));
		$notice_dismiss_seasonal = ($time_now < Keyy_Options::get_option('dismiss_seasonal_notice_until', 0));

		$dismiss = false;

		if ('dismiss_page_notice_until' == $dismiss_time) $dismiss = $notice_dismiss;
		if ('dismiss_seasonal_notice_until' == $dismiss_time) $dismiss = $notice_dismiss_seasonal;

		return $dismiss;
	}

	/**
	 * This method will create the chosen notice and the template to use and depending on the parameters either echo it to the page or return it
	 *
	 * @param  Array   $advert_information     an array with the notice information in
	 * @param  Boolean $return_instead_of_echo a bool value to indicate if the notice should be printed to page or returned
	 * @param  String  $position               a string to indicate what template should be used
	 * @return String                          a notice to display
	 */
	protected function render_specified_notice($advert_information, $return_instead_of_echo = false, $position = 'top') {
	
		if ('bottom' == $position) {
			$template_file = 'bottom-notice.php';
		} elseif ('report' == $position) {
			$template_file = 'report.php';
		} elseif ('report-plain' == $position) {
			$template_file = 'report-plain.php';
		} else {
			$template_file = 'horizontal-notice.php';
		}
		
		$extract_variables = array_merge($advert_information, array('keyy_notices' => $this));

		return Keyy_Login_Plugin()->include_template('notices/'.$template_file, $return_instead_of_echo, $extract_variables);
	}
}

$GLOBALS['keyy_notices'] = Keyy_Notices::instance();
