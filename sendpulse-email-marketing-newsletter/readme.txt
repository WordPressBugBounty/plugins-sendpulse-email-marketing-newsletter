=== SendPulse Email Marketing Newsletter ===
Contributors: SendPulse
Tags: newsletter subscription form, email subscription form, newsletter email optin, email newsletter signup form, email marketing
Requires PHP: 5.6
Requires at least: 5.7
Tested up to: 6.3.1
Stable tag: 2.1.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Add e-mail subscription form, send marketing newsletters and create autoresponders.

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
* PHP version >= 5.6+ ([Recommended](https://wordpress.org/about/requirements/) >= 5.6+)


== Installation ==

1. Upload 'sendpulse-email-marketing-newsletter' to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress


== Frequently Asked Questions ==

= How place shortcode in themes file? =
Shortcode can be used anywhere in the theme templates via do_shortcode function. 
For example, `<?php echo do_shortcode('[sendpulse-form id="..."]')?>`.


== Screenshots ==

1. SendPulse Forms table view.
2. Form editor.
3. API setting.
4. Import Wordpress user.

== Changelog ==

= 1.5.0 - 2017-08-22 =
* Changed: Ability to use the constructor code from SendPulse dashboard.
* Fixed: Support several forms on the page.

= 2.0.0 - 2017-09-19 =
* Added: Ability create multiple form with constructor code from SendPulse dashboard.
* Removed: Plugin generated subscribe form in favor constructor code from SendPulse dashboard.

= 2.0.1 - 2017-09-25 =
* Changed: Documentation and help link.

= 2.1.0 - 2017-10-18 =
* Changed: Down minimal PHP version requirement.

= 2.1.1 - 2022-07-16 =
* Updated supported WP versions
* Tested up to Wordpress 6.0.2
* Updated translations 
* Various fixes

= 2.1.3 - 2023-06-05 =
* Updated supported WP versions
* Tested up to Wordpress 6.2.2
* On plugin activation automatic

= 2.1.4 - 2023-07-14 =
* Change info messages on plugin activation
* Change message box classes
* Change function to check write available on server

== Upgrade Notice ==
In version 2.0.0 of SendPulse Email Marketing Newsletter removed plugin generated subscribe form in favor constructor code from SendPulse dashboard. 
Its breaking change! Please use link https://login.sendpulse.com/emailservice/forms/constructor/ to generate new forms.