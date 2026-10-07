=== Speed Optimizer - The All-In-One Performance-Boosting Plugin ===
Contributors: Hristo Sg, siteground, sstoqnov, stoyangeorgiev, elenachavdarova, ignatggeorgiev, asparuhtenev
Tags: nginx, caching, speed, performance, siteground
Requires at least: 4.7
Requires PHP: 7.0
Tested up to: 6.9
Stable tag: 7.7.10
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Boost your website performance and page speed, and increase conversions with powerful caching, frontend, media, and environment optimizations.

== Description ==

**The award-winning Speed Optimizer plugin is a free WordPress performance-boosting solution to improve user experience, increase conversion rates and drive more traffic. Achieve better SEO rankings, improve Core Web Vitals and enhance your Google Page Speed Score.**

Developed by the WordPress speed experts at SiteGround, our free plugin is actively used and trusted by more than 2 million website owners. It’s specially designed to be easy to use, allowing users of all skill levels to make complex speed optimizations, such as minifying HTML, CSS and JavaScript, image compression and lazy loading, in a few clicks.

Install our caching plugin now to dramatically improve your WordPress website performance on any hosting platform.

= Essential Speed-boosting Features: =

* **Powerful Caching** for up to 20% faster website.
* **Frontend Optimizations** to minify JS, HTML and CSS, reducing wait time due to the number of scripts and characters in your code 
* **Media Optimizations** by up to 85% image size compression without sacrificing quality
* **WordPress Environment Optimizations** to optimize usage and efficiency of your WordPress site resources
* **Speed Test** & tips to get on-demand optimization tips to help your site get even faster

Don't let a slow website compromise your online success. Join the millions of satisfied website owners and see the difference with the free Speed Optimizer plugin.

= AWARDS: =
[Monster Awards 2022](https://www.templatemonster.com/awards/winners-2022/): Best WordPress Optimization Plugin 🥈

= Plugin Video =
[youtube https://www.youtube.com/watch?v=8grmZSkStak]

= Plugin Tutorial =
To gain in-depth knowledge about our plugin and its functionalities, check our [Speed Optimizer Tutorial](https://www.siteground.com/tutorials/wordpress/sg-optimizer/). It provides detailed information on how to optimize each aspect of your site and maximizes your website performance and cache.

== CACHING ==
The Caching page gives you full control of your website’s cache, allowing you to enhance its performance significantly. Take advantage of the powerful caching options available to boost your page speed:
= DYNAMIC CACHING: =
By enabling Dynamic Caching, all non-static resources of your website are intelligently cached, eliminating the need for repetitive database queries and enhancing page loading speed and TTFB (time to first byte). This default feature is available exclusively for SiteGround servers, ensuring optimal performance.
= FILE-BASED CACHING: =
By activating file-based caching, your website generates and stores static HTML versions, ensuring faster loading times and an improved user experience. This efficient caching method stores the cached files conveniently in the browser's memory, allowing future visitors to access your site swiftly and seamlessly.
= MEMCACHED: =
Unleash the power of object caching for your WordPress website. Memcached revolutionizes website performance by storing frequently executed queries to your databases and reusing them for lightning-fast website results. This powerful feature is exclusively available in the SiteGround environment.
= CACHING SETTINGS: =
* **Automatic Cache Purge:** ensure the cache is cleared whenever necessary
* **Manual Cache Purge:** purge cache manually if you are editing new material and do not have automatic purge activated.
* **Browser-specific Caching:** generate caching separately for different browsers
* **Exclude Post Types from Caching:** exclude specific post types from being cached
* **Exclude URLs from Caching:** exclude specific URLs or utilize wildcards to exclude any sub-pages of a designated "parent-page."
* **Test URL Caching Status:** verify if dynamic caching is actively running on specific URLs


== ENVIRONMENT OPTIMIZATIONS ==
Optimize and fine-tune your site's environment for optimal website performance:
= HTTPS Enforce: =
Ensure a secure browsing experience for your visitors by effortlessly enabling HTTPS for your site. Say goodbye to insecure content errors and build trust with your audience.
= Scheduled Database Maintenance: =
Take control of your database by activating the Database Optimization feature. This functionality removes unnecessary items and optimizes your database tables, leading to improved efficiency and website performance. If you're using the InnoDB storage engine, table optimisation is automatically handled by the engine itself.
= Heartbeat Control: =
Manage the frequency of the WP Heartbeat for different areas of your website. By default, the WordPress Heartbeat API checks for scheduled tasks every 15 seconds on post edit pages and every 60 seconds on the dashboard and front end. With Heartbeat Control, you can adjust the frequency of these checks or even disable them entirely, providing you with greater control over resource allocation.
== FRONTEND OPTIMIZATIONS ==
Enhance and fine-tune the performance of your website's front end by minifying JS, HTML and CSS:
= CSS Tab: =
Minify CSS files, activate or deactivate CSS combinations to reduce server requests, and even preload combined CSS for optimized performance. Additionally, you can exclude specific styles from being combined or minified, giving you complete control over your CSS optimization.
= JavaScript Tab: =
Activate or deactivate Minify JavaScript Files to reduce script sizes and lower the number of server requests. You can also defer render-blocking JavaScript to expedite the initial page speed. Furthermore, you have the ability to exclude specific scripts from various optimisation processes, providing flexibility in optimizing your JavaScript resources.
= General Tab: =
Further optimization options include:

* **Web Fonts Optimization:** Enhance the loading of Google fonts by adding a preconnect link in your head tag. This informs the browser to establish a connection to Google's font repository as quickly as possible. Additionally, all other local fonts will be preloaded, resulting in faster caching and rendering. When combined with CSS Combination, we also modify the font-display property to improve rendering speed.
* **Fonts Preloading:** With locally hosted fonts being preloaded, you’re allowing browsers to cache and render them at an accelerated pace.
* **Remove Query Strings from Static Resources:** Improve caching efficiency by removing query strings from static resources, optimizing their caching capabilities.
* **Disable Emojis:** Prevent WordPress from detecting and generating emojis on your pages by disabling emojis support. This helps boost your page speed and improve your website performance.
* **DNS Pre-fetch for External Domains:** Enabling the DNS Pre-fetch for a domain will resolve it before resources from it get requested making those resources load faster.
== MEDIA OPTIMIZATIONS ==
Optimize your website media by reducing image size by up to 85% times while maintaining top-notch quality.
= Image Compression: =
Effortlessly compress images to resize your existing images and reduce the space they occupy on your server. The dimensions of the images will remain unchanged, allowing for optimized storage. Fine-tune the compression level and choose whether to create backups of the original images. Please note that image compression feature is exclusive to the SiteGround Environment.
= WebP Images: =
Leverage the power of WebP, a cutting-edge image format supported by modern browsers, to significantly reduce the size of your images and skyrocket your page speed. If a browser doesn't support WebP, the original images will be loaded. 
= Lazy Load Media: =
Take control of your website's asset loading with the ability to enable or disable Lazy Load for various assets. You can exclude specific assets such as iframes, videos, thumbnails, widgets, and shortcodes from the dropdown menu. Additionally, you have the option to exclude specific images from the Lazy Load by adding their respective class in the dedicated tab.
= Maximum Image Width: =
If you frequently upload large images to your website, you can enable the Maximum Image Width option that automatically resizes existing and future images whose width exceeds 2560 pixels. By optimizing image sizes, you can enhance your website performance and reduce bandwidth usage.
You can customize your media optimisation to your specific needs using the filters, we’ve designed for this purpose.
== SPEED TEST ==
Evaluate the optimization level of your website with Speed Optimizer’s Speed test tool. Our website performance check utilizes the robust capabilities of Google PageSpeed to provide comprehensive insights into your site's optimization. By conducting the speed test, you will receive detailed results highlighting areas that can be further optimized for enhanced performance. These insights will empower you to fine-tune your website and unlock its maximum potential and increase conversions.

## Requirements ##

In order to work correctly, this plugin requires that your server meets the following criteria:

* WordPress 4.7
* PHP 7.0+

Our plugin uses a cookie in order to function properly. It does not store personal data and is used solely for the needs of our caching system.

== Installation ==

= Automatic Installation =

1. Go to Plugins -> Add New
1. Search for "SiteGround Optimizer"
1. Click on the Install button under the SiteGround Optimizer plugin
1. Once the plugin is installed, click on the Activate plugin link

= Manual Installation =

1. Login to the WordPress admin panel and go to Plugins -> Add New
1. Select the 'Upload' menu
1. Click the 'Choose File' button and point your browser to the sg-cachepress.zip file you've downloaded
1. Click the 'Install Now' button
1. Go to Plugins -> Installed Plugins and click the 'Activate' link under the WordPress SiteGround Optimizer listing

== Changelog ==

= Version 7.7.10 =
Release Date: May 5th, 2026
* Email Service improvements

= Version 7.7.9 =
Release Date: Apr 15th, 2026
* Security improvements

= Version 7.7.8 =
Release Date: Apr 7th, 2026

* Memcache improvements
* WP Cli improvements
* Layout improvements
* Heartbeat compatibility improvements

= Version 7.7.7 =
Release Date: Feb 16th, 2026

* Version bump

= Version 7.7.6 =
Release Date: Dec 15th, 2025

* Image Optimization improvements

= Version 7.7.5 =
Release Date: Dec 4th, 2025

* JS Combinator improvements
* Dynamic cache purge improvements
* CSS combination improvements
* WebP deletion improvements
* Query string exclude support for Dynamic Cache


= Version 7.7.4 =
Release Date: Nov 11th, 2025

* Dynamic Cache Purge improvements

= Version 7.7.3 =
Release Date: Nov 3rd, 2025

* File-based cache purge improvements
* Third-Party plugins compatibility improvements
* WP CLI improvements


= Version 7.7.2 =
Release Date: Dec 9th, 2024

* Memcache improvements
* JavaScript Combination improvements
* Database Optimization improvements
* Woocommerce compatibility improvements
* File-caching improvements

= Version 7.7.1 =
Release Date: Nov 21st, 2024

* Translation loading improvements

= Version 7.7.0 =
Release Date: Nov 7th, 2024

* Disable Emojis optimization improvements
* File-Caching improvements

= Version 7.6.8 =
Release Date: Oct 23rd, 2024

* CSS Combination improvements
* File-based caching improvements
* Database-Optimizer improvements
* Improved Memcache handling of excluded options

= Version 7.6.7 =
Release Date: Oct 7th, 2024

* JS & CSS Combination improvements
* Front-end Optimizations improvements
* Image Optimization improvements

= Version 7.6.6 =
Release Date: Sept 18th, 2024

* Templates improvements
* Settings improvements
* SSL improvements

= Version 7.6.5 =
Release Date: Sept 12th, 2024

* Performance Reports improvements
* Message Service improvements
* Admin pages improvements

= Version 7.6.4 =
Release Date: Aug 14th, 2024

* Improved memcache handling of autoload options
* Improved handling of heartbeat optimizations

= Version 7.6.3 =
Release Date: Aug 1st, 2024

* Improved file-based cache purge
* Improved plugin config
* Fixed deprecated warnings in custom WP-CLI commands

= Version 7.6.2 =
Release Date: July 10th, 2024

* Improved Disable Emojis optimization
* Improved JS combination
* Improved Comment cache purge handling
* Improved JS Minification script handling

= Version 7.6.1 =
Release Date: June 25th, 2024

* Improved Multisite performance
* Improved JS combination

= Version 7.6.0 =
Release Date: May 22nd, 2024

* Improved support for PHP 8.2 and 8.3.
* Improved plugin configuration.

= Version 7.5.0 =
Release Date: Mar 21st, 2024

* Security improvements.
* Improved compatibility with WordPress 6.5.
* Plugin optimization.

= Version 7.4.6 =
Release Date: Jan 11th, 2024

* Static assets are now part of the plugin package and load locally.
* New users will be prompted to give their consent for the collection of technical data upon their initial use of the plugin.

= Version 7.4.5 =
Release Date: Dec 12th, 2023

* Improved functionality of Combine JS
* Improved compatibility with plugin “Admin Custom Login”
* Improved users access control
* Fix Image Compression Preview

= Version 7.4.4 =
Release Date: Nov 22nd, 2023

* Dashboard visuals improvements
* Readme file improvements

= Version 7.4.3 =
Release Date: Oct 24th , 2023

* Data collection opt out option
* Readme file formatting improvements
* Plugin name formatting improvements
* Exclude from Deferral compatibility issue with themes twentytwentytwo and twentytwentythree is fixed

= Version 7.4.2 =
Release Date: Sept 26th, 2023

* Changing the name we use inside the plugin from SiteGround Optimizer to Speed Optimizer
* Updating data collection process and introducing a link in the plugin interface to the Plugin Privacy notice

= Version 7.4.1 =
Release Date: Sept 12th, 2023

* Improved performance reports.

= Version 7.4.0 =
Release Date: August 22nd, 2023

* Improved default enabled features.
* Campaign templates update.
* Performance reports for non SiteGround users.

= Version 7.3.4 =
Release Date: July 27th, 2023

* Improved Dynamic Caching handling.
* Improved Speed Test results.

= Version 7.3.3 =
Release Date: June 8th, 2023

* Downgraded wp-background-processing external lib due to incompatibility with third party themes and plugins.

= Version 7.3.2 =
Release Date: June 6th, 2023

* Improved Optimized images filesize detection.
* Improved Defer Render-blocking JavaScript.
* Improved PHP 8.2 compatibility.
* Improved Flo forms and CSS Combination compatibility.
* Improved Avada theme compatibility.

= Version 7.3.1 =
Release Date: Feb 23rd, 2023

* Improved compatibility with WooCommerce related plugins.
* Internal configuration improvements.

= Version 7.3.0 =
Release Date: Feb 1st, 2023

* Improved Multisite File-Based cache support.
* Improved compatibility with woo-variation-swatches and facebook-for-woocommerce plugins.
* Improved compatibility with Foogra theme.
* Internal configuration changes.

= Version 7.2.9 =
Release Date: Dec 2nd, 2022

* Fix for missing apache_response_headers function.

= Version 7.2.8 =
Release Date: Dec 1st, 2022

* Improved namespace performance on other hosts.
* Improved WooCommerce Square plugin support.

= Version 7.2.7 =
Release Date: Nov 30rd, 2022

* Improved Memcache Health status checks
* Improved FileBased Cache cleanup
* Improved FileBased Cache Headers checks
* Improved LazyLoad for sidebar images
* Improved Speed Test results
* Improved Test URL cache status

= Version 7.2.6 =
Release Date: Nov 21st, 2022

* Discontinue of Cloudflare support

= Version 7.2.5 =
Release Date: October 18th, 2022

* Improved Database Optimization interface
* Improved Multisite Memcached support

= Version 7.2.4 =
Release Date: October 11th, 2022

* Memcached Service bug fix

= Version 7.2.3 =
Release Date: October 11th, 2022

* Install Service fix

= Version 7.2.2 =
Release Date: October 10th, 2022

* New filter - Exclude URL from cache
* New filter - Exclude inline scripts from combination
* Improved Database Optimization options
* Improved Memcached service
* Improved Toolset Types plugin support
* Improved admin menu ordering
* Legacy code removed

= Version 7.2.1 =
Release Date: August 10th, 2022

* Improved Cloudflare detection

= Version 7.2.0 =
Release Date: July 14th, 2022

* Brand New Design
* Improved Dynamic cache purge
* Improved data collection

= Version 7.1.5 =
Release Date: June 23rd, 2022

* Improved Memcached service

= Version 7.1.4 =
Release Date: June 21st, 2022

* Improved older PHP versions support

= Version 7.1.3 =
Release Date: June 20th, 2022

* NEW Lazy Load exclude filters
* Improved Max Image Width
* Improved .htaccess modifications checks
* Improved File-Based cache Elementor support
* Improved Password Protected pages excluded from File-Based caching by default
* Improved Single Image compression functionality
* Improved Image Optimization for custom image sizes
* Improved Divi theme support
* Minor fixes

= Version 7.1.2 =
Release Date: June 16th, 2022

* Adding Memcached UNIX socket support
* Improved data collection

= Version 7.1.1 =
Release Date: May 20th, 2022

* Improved default settings

= Version 7.1.0 =
Release Date: May 10th, 2022

* Improved HTTPS Enforce
* Improved Database Optimization
* Improved Auto Purge functionality for scheduled posts
* Improved File-Based cache exclude filtering
* Improved CSS Combination
* Improved JS Minification
* Improved Meks Flexible Shortcodes plugin support
* Improved All in One SEO plugin support
* Improved Authorize.Net Gateway support

= Version 7.0.9 =
Release Date: April 7th, 2022

* Improved SG Security plugin support

= Version 7.0.8 =
Release Date: April 6th, 2022

* Improved File-Based cache checks
* Improved Memcached service checks
* Improved activation checks
* Code Refactoring

= Version 7.0.7 =
Release Date: March 24th, 2022

* Image deletion refactoring

= Version 7.0.6 =
Release Date: March 4th, 2022

* Improved installation for users not hosted on SiteGround

= Version 7.0.5 =
Release Date: March 2nd, 2022

* Improved Cache Preheat
* Improved Auto Purge functionality (File-Based cache, comments, custom post types)
* Improved new images WebP generation
* Improved Multisite support

= Version 7.0.4 =
Release Date: February 28th, 2022

* Improved uninstall checks

= Version 7.0.3 =
Release Date: February 14th, 2022

* Improved Divi support
* Improved WP-CLI support

= Version 7.0.2 =
Release Date: February 10th, 2022

* Improved Cloudflare cache purge
* Improved Divi support
* Improved JS Combination
* Improved WP-CLI support
* Minor bug fixes

= Version 7.0.1 =
Release Date: February 4th, 2022

* Improved Cache Preloading
* Improved Cloudflare detection
* Improved Divi support
* Minor bug fixes

= Version 7.0.0 =
Release Date: February 2nd, 2022

* NEW – Plugin available for users not hosted on SiteGround
* NEW – File-Based Full Page Caching
* NEW – File-Based Full Page Caching for Logged-in Users
* NEW – Cache Preloading (requires FB Caching)
* NEW – Individual Image compression level settings
* Code Refactoring and General Improvements
* Improved HTML Minification
* Improved LazyLoad excludes
* Improved Automatic Purge for custom post types
* Improved Cache exclude for wp-json URLs
* Improved Test URL cache option
* Improved Cloudflare detection
* Improved Phlox theme support
* Improved WooCommerce email verification support
* Improved WP-CLI support
* Environment data collection consent added

= Version 6.0.5 =
Release Date: November 17th, 2021

* Improved HTML minification

= Version 6.0.4 =
Release Date: November 16th, 2021

* Improved HTML minification
* Improved HTTPS Enforce for multisites
* Improved Google PageSpeed Integration
* Improved multisite permissions for admins
* Improved autoflush

= Version 6.0.3 =
Release Date: November 8th, 2021

* Improved translations
* Improved multisite support
* Improved Speed test result
* Improved assets cleanup
* Improved Cloudflare support

= Version 6.0.2 =
* Improved translations
* Improved image optimization

= Version 6.0.1 =
* Improved recommended optimizations labels
* Improved REST API error handling
* Improved CF authentication
* Improved multisite interface
* Improved database optimization
* Fixed Memcached healthcheck

= Version 6.0.0 =
* Brand New Design
* Code Refactoring
* NEW - Recommended Optimizations
* NEW - Plugin Dashboard Page
* NEW - Maximum Image Width
* NEW - Backup Original Images when Optimizing
* NEW - WordPress API Cache Control
* Improved WordPress Heartbeat Optimization
* Improved Image Compression Control

= Version 5.9.7 =
* Improved cache busting for themes utilizing custom post types

= Version 5.9.6 =
* Improved WP Json purging mechanisms
* Added protection against cronjob loops caused by 3rd party plugins
* Improved Spam comments handling

= Version 5.9.5 =
* Improved Page Builders Support (Elementor, Oxygen, Divi and others)

= Version 5.9.4 =
* Improved smart cache

= Version 5.9.3 =
* Fixed WebP regeneration issue

= Version 5.9.2 =
* Improved cache flush queue

= Version 5.9.1 =
* Minor bug fixes

= Version 5.9.0 =
* Plugin refactoring

= Version 5.8.5 =
* Improved CF detection
* Minor bug fixes

= Version 5.8.4 =
* Improved cache purging mechanism

= Version 5.8.3 =
* Improved cache purge

= Version 5.8.2 =
* Improved speed tests
* Improved Google Fonts combination
* Improved HTTPS enforce

= Version 5.8.1 =
* Improved cache purge

= Version 5.8.0 =
* Added preloading for combined css scripts
* New and improved performance test
* Design improvements
* Custom error handler removed
* Increased WebP PNG optimization limit
* Changed tutorials urls
* Improved readme file
* Minor bug fixes

= Version 5.7.20 =
* Perform smart purge on the blog page when editing a post
* Remove jQuery Dependency from Lazy-load

= Version 5.7.19 =
* Change loseless quality

= Version 5.7.18 =
* Improved REST API cache invalidation

= Version 5.7.17 =
* Improved WordPress 5.7 support

= Version 5.7.16 =
* Improved Contact Form 7 support
* Improved Amelia booking support
* Improved support for sites with custom wp-content dir

= Version 5.7.15 =
* Improved Contact Form 7 support

= Version 5.7.14 =
* Improved Vary:User-Agent handling

= Version 5.7.13 =
* Add settings import/export cli command
* Exclude XML sitemaps from optimizations
* Fix DNS Resolver fatal error for non existing hosts
* Fix Cloudflare optimization for sites with custom wp-content dir
* Improved Speed Test description for Webfonts optimization

= Version 5.7.12 =
* Improved Feed Cache Flush

= Version 5.7.11 =
* Improved CloudFlare Optimization
* Add CloudFlare multisite support
* Improved RevSlider support
* Add uploads permissions check

= Version 5.7.10 =
* Revert to old HTML Minification

= Version 5.7.9 =
* Fixed bug with WooCommerce ajax

= Version 5.7.8 =
* Fix HTML Minification Refactoring

= Version 5.7.7 =
* HTML Minification Refactoring

= Version 5.7.6 =
* Improved cache flush, on automatic assets deletion

= Version 5.7.5 =
* Improved Flatsome UX Builder support
* Improved Essential Addons for Elementor support
* Updated readme

= Version 5.7.4 =
* Improved Leverage Browser Caching rules
* Add exclude by post type
* Improved Cloudflare cache purge

= Version 5.7.3 =
* Improved cache purge on Cludflare activation
* Improved deauthentication of Cloudflare
* Text improvements

= Version 5.7.2 =
* Fixed bug when external assets are not cleared properly
* Improved detection of active Cloudflare
* Text improvements

= Version 5.7.1 =
* Fixed bug with clearing cache from helper function

= Version 5.7.0 =
* Full-page Caching on CloudFlare
* Web Fonts Optimization

= Version 5.6.8 =
* Improved SSL Replace patterns

= Version 5.6.7 =
* Improved JS & CSS Combination exclude list
* Bumped JS Combination stop limit
* Improved functionality to stop JS Combination if randomized names create endless combination files

= Version 5.6.6 =
* Improved JS Combination exclude list
* Bumped JS Combination stop limit
* Fixed typos

= Version 5.6.5 =
* Improved Elementor Pro 3.0 support

= Version 5.6.4 =
* Fix error in CSS Combinator

= Version 5.6.3 =
* Better WP 5.5 support
* Improved log handling

= Version 5.6.2 =
* Improved JS Combination exclude list
* Disable native WordPress lazyloading

= Version 5.6.1 =
* Second stage of Memcached improvements applied
* Added WP-CLI control for heartbeat, dns-prefetching and db optimizations
* Fixed non-critical notices while using PHP 7.3

= Version 5.6.0 =
* Added Heartbeat Control
* Added Database Optimization
* Added DNS Prefetching
* Improved Browser Caching XML rules
* Refactored Lazy Load
* Deprecated Compatibility Checker & PHP Switcher
* Improved Lazyload Videos for Classic Editor
* Added functionality to stop JS Combination if randomized names create endless combination files

= Version 5.5.8 =
* Added proper AMP support
* Added samesite parameter to the bypass cookie
* Added support for Shortcodes Ultimate
* Extended the sg purge wp-cli command to delete assets too
* Improved support for Uncode Themes

= Version 5.5.7 =
* Improved Memcached Integration
* Added protection for objects too big to be stored in Memcached
* Improved JS and CSS Combination Exclude List
* Improved Lazy Load functionality
* Improved Image Optimization for sites using CDN

= Version 5.5.6 =
* Improved WP CLI commands
* Extended Combine JavaScript Exclude list
* Improved Beaver Builder Support
* Revamped bypass cookie functionality
* Improved Multisite Controls

= Version 5.5.5 =
* Improved Script Combinations Excluding Functionality
* Improved Internationalisation
* Improved Lazy Loading
* Improved WooCommerce Support for 3rd Party Payment Gateways
* Added Global JS exclude for Plugins with Known Issues
* Added WP-Authorized-Net support
* Added Facebook for WooCommerce support

= Version 5.5.4 =
* Fixed issue with CSS Combination causing problems with media specific stylesheets
* Added defer attribute for the Combined JS files when JS Defer is enabled
* Better support with sites using long domains (.blog, .marketing, etc.)
* Fixed Memcached XSS security issues
* Fixed CSS & JS Combination for sites with custom upload folders

= Version 5.5.3 =
* Fix ISE for Flatsome theme

= Version 5.5.2 =
* Better CSS Combination
* Better Fonts Combination
* Better concatenation of inline scripts
* Improved WebP Quaity Slider
* Updated readme.txt file
* Added WP-CLI Commands: combine-js and webp
* Better Polylang support

= Version 5.5.1 =
* Better Elementor support
* Better Divi support
* Better AMP support
* Better sourcemapping removal

= Version 5.5.0 =
* New - Combine JavaScript Files
* New - WebP quality control plus lossless option
* New - What's new and opportunities slider
* Improved - better WPML support (mostly memcached)
* Improved - better Elementor support
* Improved - better Browser Caching rules for cPanel users

= Version 5.4.6 =
* Interface revamp for better accessability
* Improved compatibility with page builders
* Improved compatibility with latest Elementor
* Added support for popular AMP plugins
* Better WebP optiomization status reporting

= Version 5.4.5 =
* Improved elementor support
* Improved flothemes support
* Improved handling of @imports in combine css

= Version 5.4.4 =
* Improved transients handling
* Added Jet Popup support

= Version 5.4.3 =
* Added Lazy loading functionality for iframes
* Added Lazy loading functionality for videos

= Version 5.4.2 =
* Fixed bug with WebP image regeneration on image delete

= Version 5.4.1 =
* Added PHP 7.4 support for PHP Compatibility Checker
* Improved WebP Conversion
* Fixed bug with WebP image regeneration on image edit
* Improved plugin localization

= Version 5.4.0 =
* Added WebP Support on All Accounts on Site Tools
* Added Google PageSpeed Test
* Improved Image Optimization Process
* Improved SSL Certificate check

= Version 5.3.10 =
* Better PHP Version Management for Site Tools
* NGINX Direct Delivery for Site Tools

= Version 5.3.9 =
* Improved check for SiteGround Servers

= Version 5.3.8 =
* Fixed a bug when Memcached fails to purge when new WordPress version requiring a database update is released
* Added alert and check if you’re running SiteGround Optimizer on a host different than SiteGround
* Improved compatibility with WooCommerce
* Improved conditional styles combination
* Improved image optimization process

= Version 5.3.7 =
* Added WooCommerce Square Payment & Braintree For WooCommerce Exclude by Default
* Improved Google Fonts Optimization
* Added Notice for Defer Render-Blocking Scripts Optimization
* Added wp-cli commands for Google Fonts Optimization
* Changed New Images Optimizer hook to wp_generate_attachment_metadata

= Version 5.3.6 =
* Improved Google Fonts loading with better caching
* Improved Defer of render-blocking JS

= Version 5.3.5 =
* WordPress 5.3 Support Declared
* Better Elementor Compatibility
* Better Image Optimization Messaging
* Better Google Fonts combination
* Added PHP 7.4 support

= Version 5.3.4 =
* Improved Async load of JS files
* Added Google Fonts Combination optimization
* Moved lazyload script in footer
* Improved CSS combination

= Version 5.3.3 =
* Improved browser cache handling upon plugin update
* Added wp-cli commands for Dynamic Cache, Autoflush and Browser-Speciffic cache handling

= Version 5.3.2 =
* Fixed bug with https enforce for www websites
* Improved JILT support

= Version 5.3.1 =
* Better SSL force to accommodate websites with WWW in the URL
* Global exclusion of siteorigin-widget-icon-font-fontawesome from Combine CSS

= Version 5.3.0 =
* Refactoring of the Lazy Load functionality
* Redesign of the Lazy Load screen
* Improved WooCommerce product image Lazy Load
* Gzip functionality update for Site Tools accounts
* Browser caching functionality update for Site Tools accounts
* Improved Browser caching functionality for cPanel accounts

= Version 5.2.5 =
* New Feature: Option to split caches per User Agent
* New Feature: Option to disable lazy loading for mobile devices
* Improved Memcached check

= Version 5.2.4 =
* Improved XML RCP checks compatibility

= Version 5.2.3 =
* Improved LazyLoad

= Version 5.2.2 =
* Improved Events Calendar Compatibility
* Suppressed notices in the REST API in certain cases
* Improved nonscript tag in LazyLoad

= Version 5.2.1 =
* Improved Cloudflare compatibility

= Version 5.2.0 =
* Exclude list Interface for JavaScript handlers
* Exclude list Interface for CSS handlers
* Exclude list Interface for HTML minification (URL like dynamic)
* Exclude list interface for LazyLoading (Class)
* Improved Thrive Architect support
* Fixed notice when purging comment cache

= Version 5.1.3 =
* Improved Elementor support
* Improved CSS optimization for inclusions without protocol
* Excluded large PNGs from optimizations
* Added better WP-CLI command documentation

= Version 5.1.2 =
* Added support for Recommended by SiteGround PHP Version
* Improved LazyLoad Support for WooCommerce sites
* Improved Image Optimization checks
* Improved PHP Version switching checks
* Added wp cli status command for checking optimization status
* Fixed bug with Combine CSS

= Version 5.1.1 =
* Improved cache invalidation for combined styles
* Cache purge from the admin bar now handles combined files too
* Added filter to exclude images from Lazy Loading
* Added filter to exclude pages from HTML Minification
* Added Filter to query params from HTML Minification
* Added PHP 7.3 support

= Version 5.1.0 =
* Added CSS Combination Functionality
* Added Async Load of Render-Blocking JS
* Added WooCommerce Support for LazyLoad
* Added Filter to Exclude Styles from CSS Combination
* Improved Lazy Load Functionality on Mobile Devices
* Fixed Issue with WP Rocket’s .htaccess rules and GZIP
* Fixed Issue with Query String Removal Script in the Admin Section
* Fixed Compatibility Issues with 3rd Party Plugins and Lazy Load
* Fixed Compatibility Issues with Woo PDF Catalog Plugin and HTML Minification
* Improved Memcached Reliability
* Improved Lazy Load for Responsive Images

= Version 5.0.13 =
* Modified HTML minification to keep comments
* Interface Improvements
* Better input validation and sanitation for PHP Version check
* Improved security

= Version 5.0.12 =
* Better cache purge for multisite
* Surpress dynamic cache notices for localhost sites

= Version 5.0.11 =
* Improved handling of third party plugins causing issues with the compatibility checker functionality
* Optimized WP-CLI commands for better performance
* Better notice handling for Multisite and conflicting plugins

= Version 5.0.10 =
* Fixed issue with Mythemeshop themes
* Fixed issues with exclude URL on update
* Fixed issues with exclude URL on update
* Exclude Lazy Load from AMP pages
* Exclude Lazy Load from Backend pages
* Fixed WPML problems
* Fixed Beaver Builder issues
* Fixed Spanish translations
* Fixed incompatibility with JCH Optimize

= Version 5.0.9 =
* Fixed woocommerce bugs
* Improved memcached flush
* Improved https force

= Version 5.0.8 =
* Better .htaccess handling when disabling and enabling Browser Cache and Gzip
* Improved image optimization handling
* Added option to stop the image optimization and resume it later
* Fixed bug with memcached notifications
* Fixed bug with conflicting plugin notices for non-admins
* Fixed bug when user accesses their site through IP/~cPaneluser
* Fixed bug with labels for HTML, CSS & JS Minification
* SEO Improvements in the Lazy Load functionality

= Version 5.0.7 =
* Fixed bug with notifications removal
* Fixed bug with modifying wrong .htaccess file for installations in subdirectory
* Flush redux cache when updating to new version
* Improved check for existing SSL rules in your .htaccess file
* Added check and removal of duplicate Gzip rules in your .htaccess file
* Added check and removal of duplicate Browser caching  rules in your .htaccess file

= Version 5.0.6 =
* Memcache issues fixed. Unique WP_CACHE_KEY_SALT is generated each time you enable it on your site.
* Better status update handling
* Added option to start checks even if the default WP Cron is disabled (in case you use real cronjob)

= Version 5.0.5 =
* Fixed Compatibility Checker progress issues.
* Fixed images optimization endless loops.
* Changed php version regex to handle rules from other plugins.

= Version 5.0.4 =
* Fixed CSS minification issues.
* Add option to re-optimize images.
* Allow users to hide notices.

= Version 5.0.0 =
* Complete plugin refactoring
* Frontend optimiztions added
* Environment optimizations added
* Images Optimizatoins adder
* Full WP-CLI Support
* Better Multisite Support
* Better Interface

= Version 4.0.7 =
* Fixed bug in the force SSL functionality in certain cases for MS
* Added information about the cookie our plugin uses in the readme file

= Version 4.0.6 =
* Bug fixes
* Better https enforcement in MS environment

= Version 4.0.5 =
* Removed stopping of WP Rocket cache

= Version 4.0.4 =
* Minor bug fixes

= Version 4.0.3 =
* Switching recommended PHP Version to 7.1

= Version 4.0.2 =
* WPML and Memcache / Memcached bug fix

= Version 4.0.1 =
* Minor bug fixes
* UK locale issue fixed

= Version 4.0.0 =
* Added proper Multisite support
* Quick optimizations - Gzip and Browser cache config settings for the Network Admin
* Network admin can purge the cache per site
* Network admin can disallow Cache and HTTPS configuration pages per site
* WPML support when Memcached is enabled
* Cache is being purged per site and not for the entire network
* Multiple performance & interface improvements
* Security fixes against, additional access checks introduced
* Fixed minor cosmetic errors in the interface

= Version 3.3.3 =
* Fixed minor interface issues

= Version 3.3.2 =
* Fixed bug with disabling the Force HTTPS option

= Version 3.3.1 =
* Fixed cache purge issue when CloudFlare is enabled
* Added logging of failed attempts in XMLRPC API.

= Version 3.3.0 =
* Improved public purge function for theme and plugin developers
* Added WP-CLI command for cache purge - wp sg purge

= Version 3.2.4 =
* Updated Memcache.tpl
* Fixed a link in the PHP Check interface

= Version 3.2.3 =
* Improved WP-CLI compatibility

= Version 3.2.1 =
* Improved cron fallback, added error message if the WP CRON is disabled

= Version 3.2.0 =
* Adding PHP 7.0 Compatibility check & PHP Version switch

= Version 3.0.5 =
* Improved Certficiate check

= Version 3.0.4 =
* Fixed bug with unwrittable .htaccess

= Version 3.0.3 =
* Fixed bug in adding CSS files

= Version 3.0.2 =
* User-agent added to the SSL availability check

= Version 3.0.1 =
* PHP Compatibility fixes

= Version 3.0.0 =

* Plugin renamed to SG Optimizer
* Interface split into multiple screens
* HTTPS Force functionality added which will reconfigure WordPress, make an .htaccess redirect to force all the traffic through HTTPS and fixes any potential insecure content issues
* Plugin prepared for PHP version compatibility checker and changer tool

= Version 2.3.11 =
* Added public purge function
* Memcached bug fixes

= Version 2.3.10 =
* Improved Memcached performance
* Memcached bug fixes

= Version 2.3.9 =
* Improved WordPress 4.6 compatibilitty

= Version 2.3.8 =
* Improved compatibility with SiteGround Staging System

= Version 2.3.7 =
* Fixed PHP warnings in Object Cache classes

= Version 2.3.6 =
* Minor URL handling bug fixes

= Version 2.3.5 =
* Improved cache testing URL detection

= Version 2.3.4 =
* CSS Bug fixes

= Version 2.3.3 =
* Improved Memcache work
* Interface improvements
* Bug fixes

= Version 2.3.2 =
* Fixed bug with Memcached cache purge

= Version 2.3.1 =
* Interface improventes
* Internationalization support added
* Spanish translation added by <a href="https://www.siteground.es">SiteGround.es</a>
* Bulgarian translation added

= Version 2.3.0 =
* Memcached support added
* Better PHP7 compatibility

= Version 2.2.11 =
* Improved compatibility with WP Rocket
* Bug fixes

= Version 2.2.10 =
* Revamped notices work
* Bug fixes

= Version 2.2.9 =
* Bug fixes

= Version 2.2.8 =
* Bug fixing and improved notification behaviour
* Fixed issues with MS installations

= Version 2.2.7 =
* Added testing box and notification if Dynamic Cache is not enabled in cPanel

= Version 2.2.6 =
* Fixed bug with Memcached causing issues after WP Database update

= Version 2.2.5 =
* Minor system improvements

= Version 2.2.4 =
* Minor system improvements

= Version 2.2.3 =
* Admin bar link visible only for admin users

= Version 2.2.2 =
* Minor bug fixes

= Version 2.2.1 =
* Added Purge SG Cache button
* Redesigned mobile-friendly interface

= Version 2.2.0 =
* Added NGINX support

= Version 2.1.7 =
* Fixed plugin activation bug

= Version 2.1.6 =
* The purge button will now clear the Static cache even if Dynamic cache is not enabled
* Better and more clear button labeling

= Version 2.1.5 =
* Better plugin activation and added to the wordpress.org repo

= Version 2.1.2 =
* Fixed bug that prevents you from enabling Memcached if using a wildcard SSL Certificate

= Version 2.1.1 =
* Cache will flush when scheduled posts become live

= Version 2.1.0 =
* Cache will be purged if WordPress autoupdates

= Version 2.0.3 =
* Minor bug fixes

= Version 2.0.2 =
* 3.8 support added

= Version 2.0.1 =
* Interface improvements
* Minor bug fixes

= Version 2.0 =
* New interface
* Minor bug fixes
* Settings and Purge pages combined into one

= Version 1.2.3 =
* Minor bug fixes
* SiteGround Memcached support added
* URL Exclude from caching list added

= 1.0 =
* Plugin created.

== Screenshots ==

1. The SiteGround Optimizer Dashboard Page offers a quick look at the current optimization status of your website, along with shortcuts to the relevant optimization pages.
2. The SiteGround Optimizer Caching Page handles your Dynamic caching and Memcached. Here, you can exclude URls from the cache, test your site and purge the Dynamic caching manually.
3. The SiteGround Optimizer Environment Page, you can force HTTPS for your site, tweak the WordPress Heartbeat Optimization, pre-fetch external domains and enable the Database Maintenance.
4. The SiteGround Optimizer Frontend Optimization Page allows you to Minify HTML, CSS & JS, as well as to remove query strings from your static resources and disable the Emoji support.
5. The SiteGround Optimizer Media Page allows you to optimize your Media Library images, as well as adds Lazy Loading functionality for your site.
6. The SiteGround Optimizer Speed Test Page, allows you to test your site loading speed, as well as additional tips on improving your site performance.

## Data Collection ##

Collection of technical data is optional and is [listed here](https://www.siteground.com/kb/what-information-wp-plugins-collect). This data is collected only for technical analysis, improvements and the possibility to contact the plugin user in case urgent issues need to be fixed (for example a critical security release that needs to be communicated to site owners). The plugin user can manage their preferences within the WP admin to control the collection of technical data. We advise opting in for this data collection, as it can enhance the plugin's performance. You may find more information on data collection in our [Plugins Privacy Notice](https://www.siteground.com/viewtos/siteground_plugins_privacy_notice).

## Credits ##

Photo credits to Anna Shvets https://www.pexels.com/@shvetsa
