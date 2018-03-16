﻿=== WP FullCalendar ===
Contributors: netweblogic, mikelynn
Tags: calendar, calendars, jQuery calendar, ajax calendar, event calendars, events calendar
Text Domain: wp-fullcalendar
Requires at least: 3.6
Tested up to: 4.7
Stable tag: 1.2

Uses the jQuery FullCalendar plugin to create a stunning calendar view of events, posts and other custom post types

== Description ==

[FullCalendar](http://arshaw.com/fullcalendar/ "jQuery Calendar Plugin") is a free open source jQuery plugin by Adam Arshaw which generates a stunning calendar populated with your events.

This plugin combines the power of FullCalendar 2.x and WordPress to present your posts or any other custom post type in a calendar format, which can be filtered by custom taxonomies such as categories and tags.

[Demo - See it in action](http://demo.wp-events-plugin.com/calendar/ "Events Manager Calendar Plugin")

= Features =

* AJAX powered
* Month/Week/Day views
* Style your calendar with dozens of themes or create your own with the jQuery ThemeRoller
* Filter by taxonomy, such as category, tag etc.
* Supports custom post types and custom taxonomies
* Popout post summaries and thumbnails when you hover over your calendar items using jQuery qTips
* Integrates seamlessly with [Events Manager](http://wordpress.org/extend/plugins/events-manager/)
* Various hooks and filters for added flexibility for developers

= Credits =

* Big thank you to Michael Lynn who generously gave us this plugin namespace after deciding not to go through with his implementation. One less confusing name on the plugin repo!
* This plugin was originally created for the Events Manager plugin Pro add-on, which has been moved over here so it can be used by the community for other post types.

= Roadmap =

Here's a rough roadmap of where we're heading, and will be ammended as time permits

* Move away from using qTips (or make as an alternative) and use jQuery UI tooltips instead
* Add more native FullCalendar options to the settings page
* Add formats for custom post types (currently only possible with Events Manager)
* Colors for other taxonomies (currently only possible with Events Manager)
* Multiple post types on one calendar

== Installation ==

Install this plugin like a normal WordPress plugin. Once activated, you'll see a new panel in the Settings section for editing the options for calendar display.

== Changelog ==
= 1.2 =
* updated to FullCalendar 2.6.1 library
* added wpfc_calendar_displayed action when a calendar has been displayed
* added wpfc_calendar_header_vars filter which enables overriding FC properties via PHP only
* fixed double loading of calendars (FC library doesn't support it anyway atm),
* fixed loading of multiple calendar html showing multiple search filters in one calendar,
* fixed redundant JS to show the loading spinner
* updated link to time formatting options which correspond to FC 2.x

= 1.1 =
* fixed EM 5.6.2 conflict,
* fixed "undefined 'type'" PHP warning
* partial fix for arabic not showing events (item times will still show roman numerals)
* fixed languages with long locales (Chinese Dialects, Portuguese Brazilian) not translating properly
* updated to FullCalendar library 2.5.0

= 1.0 =
* switched to FullCalendar 2.x library
* changed plugin textdomain so it adheres to upcoming wordpress.org plugin translation features
* moved previously hard-coded translations of calendar out of php code and now included via JS files shipped (and maintained better) via FullCalendar library
* updated to FullCalendar 2.4.0
* changed jquery style enqueue ID to jquery-ui so that EM won't override it
* fixed double-inclusion bug for wpfc-languages.php
* added some extra actions before WP_Query and also in admin setttings page
* fixed 'more' link appearing at top rather than bottom

= 0.9.1 =
* fixed privacy/security vulnerability where post types of any status can be retrieved via AJAX request (Thanks to [François Harvey](http://francoisharvey.ca/) for reporting this responsably)
* fixed ui-lightness theme stylesheet not being loaded (due to inconsistent file naming in jQuery UI bundle)
* fixed "No Theme" selection attempting to load non-existent CSS files (requires settings resave)
* fixed attachment post type tooltip not outputting a thumbnail image

= 0.9 =
* fixed E_STRICT warning for calling non-static functions
* moved hard-coded translations out of wp-fullcalendar.php into wpfc-languages.php to prevent encoding issues when editing/committing
* fixed HTTP(S) schema issues when only admin area forces SSL, AJAX only uses SSL if page is SSL
* fixed wpfc_js_vars hook passing on filtered values to wp_localize_script()
* moved PHP out of footer JS for calendar initiation
* moved footer JS out of wp-fullcalendar.php and into external JS file which is then included
* added 'settings saved' confirmation
* fixed not being able to uncheck all taxonomies in settings page
* fixed events spanning over a month not showing when going forward a month
* updated jQuery UI CSS theme files to 1.11.4 including backwards compatibility for 1.10.x,
* moved jQuery UI CSS loading out of JS and directly via wp_enqueue_style(),
* changed theme CSS storage value to contain jQuery theme name or custom stylesheet name without paths (to allow backwards compatibility)
* updated FullCalendar library to 1.6.6 (next update will use FC 2.x)
* removed old selectmenu script/css and using native jQuery UI selectmenu instead
* added Italian translation, thanks to Jeremy Wright!

= 0.8.4 =
* moved Events Manager integration code out of WPFC into the EM plugin and added warning to notify EM users to update to latest plugin version
* added Finnish, updated French
* fixed post types with exclude_from_search = false not showing up
* fixed media attachment post types not showing in the calendar
* changed first function to be executed on plugins_loaded instead of init

= 0.8.3 =
* added some translations to calendars,
* added wpfc_js_vars filter
* updated selectmenu lib to support WP 3.6 jQuery version
* prevented countries from showing up when wpfc_search_events hook is fired until they're added to options page,
* renamed EM_Categories_Walker to WPFC_EM_Categories_Walker
* fixed some php warnings
* added Finish translation - Jan-Erik Finlander
* added Russian calendar translation - Andrey Borisov
* fixed mistaken use of add_action and add_filter instead of do_ and apply_
* fixed all taxonomy dropdowns showing when no taxonomies are supplied to arguments

= 0.8.2 =
* fixed non-all-day events being considered as all day
* added option for conditional loading of css and js, fixed French typo
* fixed more... links using &amp; and having trailing slashes for event day links
* fixed translation issues for FC items - props @Christian
* added Italian for day names
* added filter wpfc_ajax_post for non-EM post type queries
* fixed non-2013 normal posts not showing up
* fixed first day of week not matching wp settings if localized

= 0.8.1 =
* fixed all-day EM events ending a day early

= 0.8 =
* added localization for calendar text (hardcoded, see WP_FullCalendar::localize_script())
* added POT file and ability to translate, files located in included/langs
* updated FC core code to 1.5.4, WP 3.5 Compatible
* improved handling of white categories, now has a darker text/border for clarity
* fixed events manager breaking tip content if no format is entered into EM settings
* fixed qtips not being disabled if set to in settings page
* fixed more... showing a time of 11:59pm
* fixed more... not showing for other CPTs
* added option to format times
* added option to choose available views
* added option to choose default view
* fixed categories not correctly filtering if shortcode is passed a category attribute
* fixed times being stripped when switching categories

= 0.7 =
* fixed issues with EM outputting converted html entities
* fixed problem with ignoring CONTENTS on EM page when overriding calendars
* fixed time problem when users turn calendar into agenda view

= 0.6.1 =
* jQuery/js - tiggered wpfc_fullcalendar_args event to document, passes on fullcalendar options object
* fixed limit and more text options being ignored (requires resave of settings)

= 0.6 =
* added taxonomy shortcode attributes
* added localization
* year/month shortcode arguments load the initial month shown on calendar 

= 0.1 - 0.5 =
* first version, ported from Events Manager Fullcalendar 1.4