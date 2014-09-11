=== tcS3 ===
Contributors: tcmccarthy1
Tags: Amazon, S3, upload, media
Requires at least: 3.5
Tested up to: 4.0
Stable tag: 1.0
License: GPL, version 2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This all-inclusive plugin uses the AWS SDK for PHP to facilitate uploads directly from your Wordpress instance to S3.

== Description ==
This all-inclusive plugin uses the AWS SDK for PHP to facilitate uploads directly from your Wordpress instance to S3. Amazon\'s inexpensive, unlimited cloud storage system is an excellent asset backend for all websites and this plugin allows you to seamlessly interact with your S3 bucket right from within your dashboard. Best of all, this plugin requires nothing special from you -- it has been tested for performance on shared hosting, VPS and dedicated servers and worked on each, out of the box, both on Apache and nginX. tcS3 has been tested on wordpress 3.7 - 4.0 and worked well on all versions.

This plugin is being released in beta -- the wide popularity of S3 makes it difficult for me, the only developer on this project, to know every possible use case for it, so I\'m relying on feedback from its use to provide further enhancements.

Current capabilities



	
 Upload directly to S3 (with the option to delete from your local instance immediately after a successful upload)
	
 Push all prexisting images on a single wordpress site to S3
	
 Push or repush a single image to S3 right from within the media library
	
 Delete media from S3 when it\'s deleted from your library
	
 Adds a redundancy layer where it lightly but intelligently figures out if your image is available on S3 and falls back to your webserver\'s copy of it if it isn\'t.


The check mentioned above will go a long way to help improve the up-time of your images. It sets up a WP Rewrite rule and then modifies your images to use this new URL scheme. Accessing the images through the new URL allows the plugin to check the file\'s headers on S3 to determing if the file can be loaded form your bucket. If it can, your users browser is redirected to the image on S3. If it can\'t, the plugin then checks the local URLs you provide in the plugin setup and if it finds the image there redirects the user\'s browser there. If that also fails the image 404s.

Advanced features



The plugin\'s use of the AWS SDK for PHP allows for a more flexible configuration. Out of the box the plugin is set up to do two things, and if you never change them you\'ll probably be fine.


	
 Set cache headers on your image so repeat visitors load the image faster and don\'t cost you a GET against S3
	
 Performs multithreaded uploads on files larger than 5MB -- larger files can take longer for your webserver to send to S3... those can really slow down your site! So, the plugin will split your files up into chunks no smaller than 5MB and send them to S3 that way, seamlessly, without your having to ask.
	
 Optional Memcache (only displays when your server supports it) or file system (supported everywhere) caching of header lookup results to save on PHP resource usage. You can set the TTL of the header lookup cache AND the cache headers on S3 right from within the dashboard


Install


This plugin is installed just like any other. Simply upload the zip file you can download from github and upload it using the WordPress dashboard or FTP. This plugin has been submitted to the Wordpress team for review as well and will hopefully be available in the Wordpress plugin repository soon.

Unsolicited advice


While S3 is relatively inexpensive (very inexpensive the more you use it), it\'s not free and it\'s not just how much you upload to it, but how much traffic you\'re getting. If your bucket is receiving a lot of GET requests (which happens when you have a lot of traffic on your site) it could get expensive (take a look at Amazon\'s S3 pricing guide). The cache headers being assigned by this plugin will certainly help, but if you sign up for a free Cloudflare account and set up your S3 bucket as a subdomain that Cloudflare is caching, responses to initial requests will come from S3, but many subsequent requests will hit Cloudflare and cost you nothing (and images will load faster because Cloudflare is a CDN).

== Installation ==
This plugin is installed just like any other. Simply upload the zip file you can download from GitHub and upload it using the WordPress dashboard or FTP. You can also install it right from the Wordpress Plugin Repository!