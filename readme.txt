=== Kaje Picture Password ===
Contributors: jsterj, kajelogin
Tags: kaje, kaje picture password, picture password, password, entropy, security, login, pin, smartcard, proof of knowledge
Requires at least: 3.8.1
Tested up to: 3.9
Stable tag: 1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Easily integrate Kaje Picture Password™ on your WordPress site.

== Description ==

The Kaje Picture Password™ service serves as a proof of knowledge replacement for typed passwords.   Picture passwords are superior in every way to typed passwords.   Anytime you ask your users to type in a password, consider giving them the option to mouse-in or touch-in their password instead.   With only THREE actions, you get the strength of EIGHT typed alphanumeric-symbol characters.

[youtube https://www.youtube.com/watch?v=vMB5RhJIHz8]

Features include:

* Users login via Kaje using their existing usernames.  No need to create secondary accounts.
* You can administer your users' Kaje accounts directly from the default USERS screen in the WordPress Dashboard.
* The status of the Kaje service is automatically checked before offering Kaje as a login option to users.
* Detailed instructions are included directly on the plugin's SETTINGS screen to guide you through the simple setup process.

For more information, check out [PicturePassword.info](http://www.picturepassword.info/).

== Installation ==

1. Install Kaje either via the WordPress.org plugin directory, or by uploading the files to your server
2. From your WordPress Dashboard, navigate to SETTINGS -> KAJE PICTURE PASSWORD
3. There will be detailed instructions on that page to guide you along the process

== Frequently Asked Questions ==

= Can I implement Kaje on a site that does NOT use SSL (https)? =

No.  For security, Kaje requires that your website be SSL enabled.

= Is the use of the Kaje Picture Password service free? =

The Kaje Picture Password service is free for the first 10,000 successful proofs of knowledge.

= Do I need to sign up for anything in order to implement Kaje on my site? =

You will need to create a Requesting Party Admin account.  Signing up is free and only requires a valid email address.  Instructions regarding sign up are included on the plugin's SETTINGS page.

= Will the Kaje plugin work with other login plugins? =

The Kaje plugin was designed to work with the default WordPress login screen, normally located here YourWebSite.com/wp-login.php.

= Where can I find additional information about Kaje Picture Password? =

On our website at http://picturepassword.info/

== Screenshots ==

1. Login Screen.
2. Kaje Interface.
3. User Management.
4. Settings.

== Changelog ==

= 1.0 =
* Initial release

= 1.1 =
* Added CA Certs from mozilla.org.  The certs are not always included with CURL and can cause SSL failures.
* CURL failures are now handled more gracefully. 

= 1.2 =
* Kaje admin footer no longer displaying on all admin pages.  Only on the Kaje settings page now.
* Tested plugin with WordPress 3.9.