=== tcS3 ===
Contributors: tcmccarthy1
Tags: Amazon, S3, upload, media, multisite, aws
Requires at least: 3.5
Tested up to: 4.9.4
Stable tag: 2.1.1
License: GPL, version 2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This all-inclusive plugin uses the AWS SDK for PHP to facilitate uploads directly from your Wordpress instance to S3.

== Important ==
Requires PHP >= 5.5.0 because of the AWS SDK.

== Description ==
This all-inclusive plugin uses the AWS SDK for PHP to facilitate uploads directly from your Wordpress instance to S3. Amazon's inexpensive, unlimited cloud storage system is an excellent asset backend for all websites and this plugin allows you to seamlessly interact with your S3 bucket right from within your dashboard. Best of all, this plugin requires nothing special from you -- it has been tested for performance on shared hosting, VPS and dedicated servers and worked on each, out of the box, both on Apache and nginX. tcS3 has been tested on wordpress 3.7 - 4.9 and worked well on all versions.

This plugin is being released in beta -- the wide popularity of S3 makes it difficult for me, the only developer on this project, to know every possible use case for it, so I'm relying on feedback from its use to provide further enhancements.

== Current capabilities ==
* Use EC2 instance profile, environment variables or IAM keys to connect to S3
* Upload directly to S3 (with the option to delete from your local instance immediately after a successful upload)
* Delete media from S3 when it's deleted from your library
* Update URLs to point directly at your S3 bucket using the link you specify (adding flexibility to point to CDN services like CloudFlare or Cloudfront or pointing directly to your bucket)

== Advanced features ==
* The plugin's use of the AWS SDK for PHP allows for a more flexible configuration. Out of the box the plugin is set up to do two things, and if you never change them you'll probably be fine.
* Set cache headers on your image so repeat visitors load the image faster and don't cost you a GET against S3
* Performs multithreaded uploads on files larger than 5MB -- larger files can take longer for your webserver to send to S3... those can really slow down your site! So, the plugin will split your files up into chunks no smaller than 5MB and send them to S3 that way, seamlessly, without your having to ask.
* Plugin kept light and fast by breaking plugin and dependencies up into classes and autoloading them via Composer

== Install ==
This plugin is installed just like any other. Simply upload the zip file you can download from github and upload it using the WordPress dashboard or FTP. This plugin has been submitted to the Wordpress team for review as well and will hopefully be available in the Wordpress plugin repository soon.

== Unsolicited advice ==
While S3 is relatively inexpensive (very inexpensive the more you use it), it's not free and it's not just how much you upload to it, but how much traffic you're getting. If your bucket is receiving a lot of GET requests (which happens when you have a lot of traffic on your site) it could get expensive (take a look at Amazon's S3 pricing guide). The cache headers being assigned by this plugin will certainly help, but if you sign up for a free Cloudflare account and set up your S3 bucket as a subdomain that Cloudflare is caching, responses to initial requests will come from S3, but many subsequent requests will hit Cloudflare and cost you nothing (and images will load faster because Cloudflare is a CDN).

== Installation ==
This plugin is installed just like any other. Simply upload the zip file you can download from GitHub and upload it using the WordPress dashboard or FTP. You can also install it right from the Wordpress Plugin Repository!

== Changelog ==
= 2.1.1 =
* Fixes bug where browserify breaks JS

= 2.1.0 =
* Improves language on the configuration page
* Adds some validation to the configuration page
* Fixes bug that prevents images from being deleted after push to S3
* Moves JS to ES6

= 2.0.1 =
* Fixes network activation bug
* Fixes issue with uploading to bucket root


= 2.0.0 =
* Redesigns options page
* Allows for instance override of network config in WPMU
* Upgrades to latest version of the AWS SDK
* Adds additional AWS regions
* Improves configuration experience
* Reduces plugin footprint


= 1.9.1 =
* Reverts accidentally released ads feature. Sorry for the mishap on this -- the selectors were broad and still a work in progress (ads showing on non-plugin pages). Elements of the wrong branch got introduced into this release inadvertently.

= 1.9 =
* Upgrades to the latest AWS PHP SDK

= 1.7.2 =
* Grunts the plugin and object orients the JS.

= 1.7.1 =
* Corrects some bugs that are resulting in notices

= 1.7 =
* Users have expressed use cases where the automated S3 push is needed but they wish to not modify the attachment URL. Adds this option
* WP 4.4 added functions for defining the srcset polyfill. This functionality is now supported by tcS3.

= 1.6 =
* Adds fallback option where users can opt to use the tcS3_media endpoint OR link directly to their S3 bucket
* Increases hook priority to 20 to allow for various image editing plugins to have their work pushed to S3
* Adds support for the use of environment variables to store AWS keys and secrets

= 1.1 =
* Bug fix where some environments don't show menu options for plugin when plugin installed from the Wordpress repository
* Bug fix where some environments choke on upload of non-images

= 1.0 =
* Initial Release
