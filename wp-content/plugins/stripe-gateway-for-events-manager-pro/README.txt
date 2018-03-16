=== Stripe Gateway for Events Manager Pro ===
Contributors: kirit-dholakiya
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=67KHCQ47R8T6C
Tags: events, event, event registration, event calendar, events calendar, event management, stripe, paypal, addon, extension, addition, registration, ticket, tickets, ticketing, tickets, theme, widget, locations, maps, booking, attendance, attendee, calendar, gigs, payment, payments
Requires at least: 3.8
Tested up to: 4.7
Stable tag: 1.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A Stripe Gateway for Events Manager Pro plugin.

== Description ==

Stripe Gateway for the Events Manager Pro plugin.

Eevent Manager Pro Stripe works like any standard WordPress plugin, and once installed and enabled, it will automatically added a Stripe gateway to your website which has Events Manager Pro plugin installed and enabled.

You can find documentation for installing this plugin here.
https://oliveconcepts.com/events-manager-pro-stripe-documentation.shtml

Demo: <a href="http://emp.oliveconcepts.com/events/test-event/">http://emp.oliveconcepts.com/events/test-event/</a>

you can also find Premium payment gateways for Events Manager Pro here.
<ul>
<li><a href="https://oliveconcepts.com/downloads/events-manager-paypal-pro/">PayPal Pro</a></li>
<li><a href="https://oliveconcepts.com/downloads/events-manager-paypal-advanced/">PayPal Advanced</a></li>
<li><a href="https://oliveconcepts.com/downloads/events-manager-stripe/">Stripe Pro</a></li>
<li><a href="https://oliveconcepts.com/downloads/emp-eway/">eWay</a></li>
</ul>

<b>Pro version</b>
- PCI compliance Solution using Stripe.JS
- 1 Year Pro Support
- 1 Year Updates
- Live Demo you can find here. <a href="http://emp.oliveconcepts.com/events/test-event/">http://emp.oliveconcepts.com/events/test-event/</a>

For more information or to go pro, <a href="https://oliveconcepts.com/emp-stripe-pro/">visit our plugin website.</a>

== Installation ==

Events Manager Stripe Edition works like any standard Wordpress plugin, and once enabled will automatically add a Stripe gateway to your Events Manager Pro enabled website.

= Installing =
1. If installing, go to Plugins > Add New in the admin area, and search for 'events manager pro stripe'.
2. Click install, once installed, activate and you're done!

Once installed, you can enable the Stripe gateway in the Events -> Gateways administration section, after adding your API keys, available from your stripe.com admin console.

== Screenshots ==

1. Activate Stripe Gateways.
2. After activate Stripe gateway, you need to set Test Secret Key, Test Publishable Key, Live Secret Key and Live Publishable Key information.
3. Once you’ve activated Stripe gateways, they will be made available as payment options on your booking form for any event with chargeable tickets (i.e. non-free events).

== Changelog ==
=1.3=
* Fix payment description text on registration form for stripe.
* Langauge support. So now translation possible. Currently, we have only added English UK and partial german version. If anybody want to translate plugin then please contribute. We will add translation for new languages.

= 1.2.4 =
* Add Filter for booking description

= 1.2.3 =
* Fix Gateway Title error

= 1.2.2 =
* Fix TLS Bug
Connection error:: "Stripe no longer supports API requests made with TLS 1.0. Please initiate HTTPS connections with TLS 1.2 or later. You can learn more about this at https://stripe.com/blog/upgrading-tls."

= 1.2.1 =
* Add action hook called 'em_payment_processed'
= 1.2 =
* Change update settings. and also make it compatible to new version of Events Manager Pro 2.4.4.2
= 1.1 =
* Add Registrant Name as card holder name and Email address as receipient email address to Stripe Gateway. So that site owner can cross check payment with registrant name and email address.
= 1.0 =
* Initial Release

== Frequently Asked Questions ==

= Do I need Events Manager Pro? =
Yes! This just provides a Stripe gateway addition. You will also need the base Events Manager plugin.

= How i see stripe gateway settings =
Its very simple. open Events menu, go to Payment Gateway menu. there you seen stripe option. you need to activate it and going to its settings.

= What is Stripe API KEYS =
You can find api key from your stripe account.  https://dashboard.stripe.com/account/apikeys. here both live api keys and test apis key shown.