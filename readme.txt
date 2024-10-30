=== Keyy Two Factor Authentication (like Clef) ===
Contributors: nexist, AnkurChauhan126
Tags: two factor auth, login, security, authenticate, password, security, two factor, 2fa
Requires at least: 4.4
Tested up to: 6.6.1
Stable tag: 1.2.2
Author: nexist, AnkurChauhan126
License: MIT
License URI: https://opensource.org/licenses/MIT

WordPress 2-Factor Authentication plugin that's high security and low hassle. Say goodbye to remembering usernames, passwords and tokens; say hello to a quick and easy login experience. 

== Description ==

Keyy gives you 2-factor authentication with a difference. It replaces passwords with sophisticated RSA public-key cryptography, which results in stronger security and a better user experience.

[vimeo https://player.vimeo.com/video/217465671]

Keyy does away with typing:

* Usernames
* Passwords
* One-time-passwords or other 2FA tokens

Instead, users log in simply using their mobile phone. It's easy!

* Install the Keyy app on your phone, available through Android or iOS (iPhone / iPad / iPod).
* Secure the app using either a fingerprint or a 4-number pin
* To log in, open the app and point it at the code shown on the screen. 

Keyy gives you one-click access to all your WordPress websites simultaneously.

Security

Keyy has been built on RSA public-key cryptography, which is the same tried-and-tested technology underlying secure websites (SSL) and many other industry standards.

It involves a 2048-bit RSA digital key, which is created and stored on the user's mobile phone. Keyy doesn't keep a central database of user profile and login details, so you're not reliant upon any third parties. The digital key is secured in the Android Keystore or Apple Keychain, only accessible via each user's mobile phone protected by a fingerprint scan or a 6-digit PIN, so data remains safe even if the phone becomes lost or stolen.

Because it doesn't use passwords, Keyy protects against a host of common password-stealing hacks, including:

* Brute-forcing
* Weak credentials
* Key-logging
* Password re-use
* Shoulder-surfing
* Connection sniffing

By strengthening individual account security, Keyy keeps the entire network safe. 

Hold your phone up to any computer and you're instantly logged in. 

You need to have a device (e.g. phone or tablet) that uses either Android or iOS (e.g. iPhone, iPad).

N.B. This is our initial release. It is expected to be rough around the edges!

Please don't hit us with a bad review before giving us a chance to improve the product; we're very eager for your and suggestions feedback in the support channel.

In the coming weeks and months we will:
* Launch a single-sign on feature, so logging into one site with Keyy logs you into all sites on that device
* Ability to log on to a localhost site or other site without incoming Internet access (not currently possible)
* Various other smaller improvements also planned

= Features =

* Login by scanning a code with your phone (or other device). No passwords to remember!

* Industry-standard RSA encryption (assymetric keys) - your login key lives on your phone. There is no back-door access, even for us.

* No central point of failure. The login instruction (signed by your unique private key) goes directly from your phone to your website; no third-party server is involved. You don't get locked out if somebody else's server is down.

* Secret URL for de-activating Keyy: note and securely store this URL when you set up, and if you lose your phone later, you can use it to login using the ordinary WordPress username/password mechanism.

* If you lose your phone, you can also disable the plugin through your web hosting account. i.e. You can't be permanently logged out if you still have access to your WordPress install through your web hosting.

= Premium Features =

The Premium version of this plugin adds these extra features:

* Ability to choose whether to require a password as well as, or instead of, a scan

* Ability for administrators to impose scan/password policies on users (e.g. all editors require both)

* Scan codes also appear on the WooCommerce and Affiliates-WP login forms and Theme My Login widgets and secondary login forms

* Stealth mode: Hide the Keyy scan image until the user presses a key to reveal it

* Hide username/password fields and require Keyy for all users

* Mass contacting of all users with a connect scan code (useful when requiring Keyy of all users)

* Ability for admins to view and over-ride settings for a specific user

* Keyy admin pages do not show information about other products from our product family

* Ability to customise/brand the "What is this?" message

* Access to Premium support channels


== Installation ==

Standard WordPress installation procedure: search for the plugin from your dashboard's plugin page, then press on "Install", then on "Activate".

Or, download the plugin zip and upload it via the plugin installer in your WordPress dashboard (in Plugins -> Add New -> Upload), and then activate it.

= Requirements =

- WordPress 4.4 or later (or possibly an earlier version if you also add the offical WP REST plugin - but we have not tested and cannot provide support for this).

- An Android smartphone or tablet, or an iPhone/iPad (or any device that runs Android or iOS apps).

- Your WordPress site must not have the REST interface disabled, and that interface must be reachable from the public Internet (or at least, have its URL reachable by your device running the app)

In previous versions, it was necessary to use WordPress's default pretty permalinks. However, that is no longer the case. Please make sure that you have updated Keyy on your sites (to 0.6.9 or later).

== Changelog ==

= 1.2.3 - 2024/Jul/26 =

* TWEAK: Mark as compatible on WP 6.6.1

= 1.2.2 - 2023/Dec/11 =

* TWEAK: Mark as compatible on WP 6.4.2

= 1.2.1 - 2023/Sep/10 =

* TWEAK: Mark as compatible on WP 6.2
* TWEAK: Mark as compatible up to PHP8.2

= 1.2.0 - 2023/Apr/11 =

* TWEAK: Mark as compatible on WP 6.2

= 1.1.1 - 2022/Nov/10 =

* TWEAK: Mark as compatible on WP 6.1

= 1.1.0 - 2022/Jul/15 =

* TWEAK: Mark as compatible on WP 5.9.2
* FIX: Removed "Lost your password" link from /wp-admin page if Keyy is activated

= 1.0.1 - 2022/Mar/22 =

* FIX: Mark as compatible on WP 5.9.2

= 1.0.0 - 2022/Mar/22 =

* TWEAK: Mark as compatible on WP 5.9.2

= 0.9.0 - 2021/Jul/30 =

* TWEAK: Mark as compatible on WP 5.8

= 0.8.4 - 2021/Jun/19 =

* TWEAK: Mark as compatible on WP 5.7.2

= 0.8.3 - 2021/Mar/11 =

* TWEAK: Mark as compatible on WP 5.7
* TWEAK: Update to latest version of updater library (Premium) (1.8)

= 0.8.2 - 2021/Feb/07 =

* TWEAK: Mark as compatible on WP 5.6.1
* TWEAK: Update to latest version of updater library (Premium) (1.8)

= 0.8.1 - 2020/Dec/23 =

* TWEAK: Mark as compatible on WP 5.6
* TWEAK: Update to latest version of updater library (Premium) (1.8)

= 0.8.0 - 2020/Oct/19 =

* TWEAK: Mark as compatible on WP 5.5.1
* TWEAK: Update to latest version of updater library (Premium) (1.8)
* TWEAK: Update to the Keyy Admin Dashboard menus

= 0.7.8 - 2019/Sep/24 =

* TWEAK: Change username of main developer - this plugin is now under the ownership of NexIST (https://nex.ist)
* TWEAK: Prevent unnecessary PHP notice upon connection

= 0.7.7 - 2019/May/23 =

* TWEAK: Update to latest version of updater library (Premium) (1.8)
* TWEAK: Fix an error in how the network-active plugins were fetched on a multisite
* TWEAK: Update plugin headers + latest known Android app number

= 0.7.6 - 2018/Nov/19 =

* SECURITY: A brute-force attacker on sites with the Keyy wave enabled (which is the default) could restrict the opportunity for legitimate users to log in, by grabbing all available security tokens. This would require over 60,000 HTTP requests without any legitimate user logging in in the mean-time. i.e. A "denial of service" attack. This is now resolved by adding rolling checks to prevent token exhaustion.
* TWEAK: Added seasonal notices
* TWEAK: Mark as compatible on WP 5.0+
* FIX: Prevent incorrect errors on the login screen

= 0.7.5 - 2018/Jun/20 =

* TWEAK: Adjust the conditions on when the upgrade notice is displayed on the admin dashboard

= 0.7.4 - 2018/Mar/20 =

* FIX: In Keyy Premium, if a policy had been set requiring certain users to use Keyy, then this was not always being enforced prior if those users had previously created a password-only login
* TWEAK: Added compatibility with the Material WP Admin plugin
* TWEAK: Added admin notices if the apps need an update
* TWEAK: Update updater library in paid version to current release (1.5)

= 0.7.3 - 2017/Sep/28 =

* FEATURE: Add keyy_connect shortcode, allowing users to set up Keyy via the front-end
* PERFORMANCE: Stop polling for scans when the browser visibility API indicates that the screen is hidden
* FIX: Improved compatibility with TML widgets and shortcodes. 
* TWEAK: Successful scans trigger an exit animation. 
* TWEAK: Login form UI now much better
* TWEAK: On multisite, when checking active plugins that might be disabling REST, also check network-activated plugins
* TWEAK: Improved plugin copy for better clarity
 
= 0.7.2 - 2017/Jul/27 =

* FEATURE: Keyy Wave now available by default. If you don't like it, add <code>define('KEYY_USE_WAVE', false);</code> to your wp-config.php (or, if you have Keyy Premium, switch it off in the settings).
* FIX (relevant to Keyy Premium only): Incorrect error message when saving "valid for" field

= 0.7.1 - 2017/Jul/17 =

* FIX (relevant to Keyy Premium only): if the user saved a site policy, then entirely removed it, then it would re-appear after the settings were saved

= 0.7.0 - 2017/Jul/13 =

* FEATURE: Added support for the Keyy Wave on login forms. The code is in this release; you will also need to 1) Have a recent-enough app version (1.0.9 for Android, or 1.2.4 for iOS) and 2) Add a line to your wp-config.php (careful not to add a syntax error - your site will then go off-line until you remove it). The feature will be enabled by default in our next major release (we are allowing a little bit of time for testing of the major refactoring, and for peoples' apps to update). Here is the line: <code>define('KEYY_USE_WAVE', true);</code>
* TWEAK: Work around security rules which require the name/value pair form the login form submit button to be present, by adding that to the form before submission
* TWEAK: More detection of ways in which the REST interface may have been disabled
* TWEAK: Added plugin links to Dashboard plugins page
* TWEAK: Delete expired login tokens slightly later
* TWEAK: Add some extra checking on the /login call, when using new-style shorter tokens

= 0.6.12 - 2017/Jun/30 =

* FEATURE: Add support for multiple login forms of the same type on the same page (e.g. two widgets, two TML shortcodes)
* FEATURE: Add support for login forms in "Theme My Login" widgets (Premium)
* TWEAK: When there are multiple login forms on the same page, no extra polling is required (use less resources)
* FIX: With multiple login forms on the same page, when the login token timed out, only the first was being updated
* TWEAK: Make the login token lifetime filterable, for easier debugging

= 0.6.11 - 2017/Jun/27 =

* FIX: Fix JavaScript syntax in shared.js that caused a build failure
* FIX: As a result of the build failure, files were missing from the plugin

= 0.6.10 - 2017/Jun/27 =

* TWEAK: Implement a back-off/die sooner polling strategy that is less troublesome in hosting setups that limit the number of active PHP processes
* TWEAK: Slightly strengthen checks around KEYY_DISABLE in case any cacheing is reducing its effectiveness
* TWEAK: Close the PHP session when doing login polling to avoid blocking any other session-using code
* TWEAK: Alert the user in the Keyy dashboard if mod_security is installed
* TWEAK: Make the test app send a user-agent header, to handle default mod_security rules

= 0.6.9 - 2017/Jun/26 =

* FEATURE: WP sites with non-default permalink structures are now supported
* TWEAK: Detect further ways that users may have disabled WP's REST interface
* TWEAK: Attempt to deal with WooCommerce not re-setting phpmailer fully after Keyy sends a mail (only makes any difference if the same page-load first sends a Keyy mail and then a WooCommerce one)

= 0.6.8 - 2017/Jun/15 =

* TWEAK: For rendering QR codes in the browser, swap to the kjua library, dropping the dependency on jQuery, and thus preserve success on sites with ancient plugins loading antique jQuery versions

= 0.6.7 - 2017/Jun/14 =

* FIX: User logins (on the plugin side) beginning with numbers were failing to connect
* TWEAK: Command-line test script had issues when run on Windows due to line-ending differences.
* TWEAK: If the user has disabled WP's REST interface (e.g. via a security plugin), then tell them why Keyy isn't working

= 0.6.6 - 2017/Jun/10 =

* FIX: If the user had chosen to allow either Keyy OR a traditional password login (option in the Premium version), then the plugin was actually requiring both (i.e. AND).
* TWEAK: A few bits added internally as part of the work towards Single Sign-On

= 0.6.5 - 2017/Jun/09 =

* FEATURE: Secret URL for de-activating Keyy: note and securely store this URL when you set up, and if you lose your phone later, you can use it to login using the ordinary WordPress username/password mechanism.
* FEATURE: The WP users page now allows you to filter users depending on whether they are connected via Keyy or not.
* TWEAK: (Premium) The administrative area now displays a count of how many connected and unconnected users there are, linking to the lists in the user area.
* TWEAK: Never hide normal login form elements when KEYY_DISABLE is set

= 0.6.4 - 2017/Jun/07 =

* TWEAK: Be more tolerant/careful in handling of home_url values

= 0.6.3 - 2017/Jun/05 =

* FEATURE: In the free version, if all users are connected to Keyy, then hide the normal username/password fields
* FIX: The facility in Keyy Premium for sending all un-enrolled users an email was previously not working if the Keyy Server plugin was not installed.
* TWEAK: The facility in Keyy Premium for sending all un-enrolled users an email now has an improved indication of progress.
* TWEAK: Close the PHP session when doing scan polling to avoid blocking any other session-using code

= 0.6.2 - 2017/Jun/03 =

* TWEAK: When policy for a particular user is "Require both Keyy and password" (Premium option), allow the user to supply credentials in either order in all situations (previously only covered some)
* TWEAK: Tweak the capitalisation when choosing a user policy (Premium option) to make the difference more quickly visible
* FIX: When policy for a particular user was "Keyy only; no passwords", passwords were still working if the user used their email address instead of their user login.
* FIX: When policy for a particular user was "Require both Keyy and password" (Premium option), passwords alone were still working if the user used their email address instead of their user login.

= 0.6.1 - 2017/Jun/01 = 

* RELEASE: Initial release (please see our list of upcoming features on the plugin page)

== Frequently Asked Questions ==

= Where are the FAQs for Keyy? =

Here: <a href="https://getkeyy.com/faqs/">https://getkeyy.com/faqs/</a> (we keep them in one place, so that they don't get out of date!)

== Screenshots ==

1. Keyy connection page (not connected)

2. Keyy connection page (connected)

3. Keyy site administration (Premium version)

4. Keyy iOS splash screen

5. Keyy iOS signup screen

6. Keyy iOS passcode setup screen

7. Keyy iOS home screen (camera permission)

8. Keyy iOS home screen (camera off)

9. Keyy iOS settings screen

10. Keyy Android splash screen

11. Keyy Android signup screen

12. Keyy Android passcode setup screen

13. Keyy Android setup tutorial

14. Keyy Android settings sidebar

15. Keyy Android home screen

16. Keyy Android edit site screen

17. Keyy Android delete site screen

18. Keyy Android change email screen

== Upgrade Notice ==
* 0.7.8 - Change references to reflect the new maintainership
