=== SendPulse Email Marketing Newsletter ===
Contributors: SendPulse
Tags: email marketing, newsletter, subscription form, email optin, autoresponder
Requires PHP: 8.0
Requires at least: 5.7
Tested up to: 7.0
Stable tag: 2.2.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add a customizable email subscription form to your site, send newsletters, and automate email campaigns with autoresponders using SendPulse.

== Description ==

SendPulse plugin for WordPress
Add an email subscription form to your site. Each new subscriber will be automatically added to your mailing list. Create and send email campaigns with SendPulse, a multi-channel marketing automation platform.

= FEATURES =
* Install the plugin in 1 click and set up within minutes;
* Add multiple email subscription forms;
* Customize your subscription forms to fit your brand identity;
* Import contacts from WordPress to your mailing list.

= SENDPULSE’S KEY FEATURES =
* Rich automation possibilities that allow you to create email, SMS, web push, and chatbot campaigns on one platform;
* Drag and drop email editor;
* Ready-made email templates;
* Email personalization and list segmentation;
* Detailed analytics and reports;

= WHAT IS SENDPULSE? =
SendPulse is a multi-channel marketing automation platform for multifaceted business promotion and customer retention.

SendPulse allows you to send email, SMS, and web push campaigns, stay in touch with clients using Telegram, Facebook Messenger, WhatsApp, and Instagram chatbots, and create landing pages in just 15 minutes.

You can easily track all of your marketing activities and gather customer data with SendPulse’s free CRM.

[Create a SendPulse account](https://sendpulse.com/register), and send up to 15,000 emails every month for free.

You can install [SendPulse Free WebPush plugin](https://wordpress.org/plugins/sendpulse-web-push/) if you need a plugin for web push notifications.

= Contacts =
* Customer support – [https://sendpulse.com/support](https://sendpulse.com/support)
* Twitter – [https://twitter.com/SendPulseCom](https://twitter.com/SendPulseCom)
* Facebook – [https://facebook.com/sendpulse](https://facebook.com/sendpulse)

= Usage =

1. Create a subscription form using [SendPulse’s builder](https://login.sendpulse.com/emailservice/forms/constructor/).
2. Add a new SendPulse form using WordPress.
3. Paste your subscription form code in the editor.
4. To display your subscription form, use a shortcode (for example `[sendpulse-form id="..."]` where "..." is form id) in editor or place `<?php echo do_shortcode('[sendpulse-form id="..."]')?>` in your themes file.

= Requirement =
* PHP version >= 8.0+ ([Recommended](https://wordpress.org/about/requirements/) >= 8.0+)

== Installation ==

1. Upload 'sendpulse-email-marketing-newsletter' to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress


== Frequently Asked Questions ==

= How place shortcode in themes file? =
Shortcode can be used anywhere in the theme templates via do_shortcode function. 
For example, `<?php echo do_shortcode('[sendpulse-form id="..."]')?>`.

= Why is the SendPulse subscription form not appearing on my website? =
If the subscription form does not appear on your website, the issue is often caused by frontend optimization plugins.

Many performance plugins (such as WP Rocket, Autoptimize, LiteSpeed Cache, Fast Velocity Minify, etc.) modify how JavaScript is loaded by enabling features like:
- JavaScript minification
- JavaScript combination (bundling)
- Deferred or delayed script execution
- JavaScript optimization and aggregation

These optimizations can change the loading order or execution timing of third-party scripts and may prevent SendPulse subscription forms from loading correctly.

How to fix:
1. Temporarily disable JavaScript optimization options and check whether the subscription form starts working.
2. If it does, re-enable options one by one to identify the conflicting setting.
3. Clear all caches after changing optimization settings.

For WP Rocket users:
- Disable the "Minify JavaScript files" option
- Disable the "Combine JavaScript files" option
- Disable the "Delay JavaScript Execution" option (if enabled)
- Clear the cache after making changes

In some cases, excluding SendPulse resources from optimization may also help.

Recommended exclusions:
- static-login.sendpulse.com
- web.webformscr.com
- /apps/fc3/build/loader.js

These resources are required for loading and rendering SendPulse subscription forms.

Note:
JavaScript combination is often unnecessary on modern HTTP/2 and HTTP/3 websites and may cause compatibility issues with third-party services such as embedded forms, chat widgets, analytics tools, and marketing integrations.

== Changelog ==
= 2.2.5 =
* Improved the plugin admin UI and overall user experience.
* Added visual indicators for external documentation links.
* Expanded the Documentation section with troubleshooting guidance for JavaScript optimization, caching, and performance plugins.
* Improved onboarding and setup instructions inside the plugin.
* Updated and synchronized plugin translations.
* Refined interface terminology and help texts for improved consistency.
* Minor fixes and usability improvements.

= 2.2.4 =
* Removed legacy layer/packages
* Cleaned legacy plugin assets
* Removed unused legacy hooks and duplicate AJAX registrations
* Unified minimum PHP requirement to PHP 8.0 across the plugin
* Improved overall plugin maintainability

= 2.2.3 =
* Improved plugin stability when the SendPulse API is temporarily unavailable.
* Prevented fatal errors caused by unexpected API responses or connection failures.
* Improved error handling for mailing list loading, AJAX import actions, and user registration subscription.

= 2.2.2 =
* Maintenance release.

== Screenshots ==
1. SendPulse Forms table view.
2. Form editor.
3. API setting.
4. Import Wordpress user.