<h2>tcS3 -- STORE YOUR WP UPLOADS DIRECTLY ON S3</h2>

This all-inclusive plugin uses the AWS SDK for PHP to facilitate uploads directly from your Wordpress instance to S3. Amazon's inexpensive, unlimited cloud storage system is an excellent asset backend for all websites and this plugin allows you to seamlessly interact with your S3 bucket right from within your dashboard. Best of all, this plugin requires nothing special from you -- it has been tested for performance on shared hosting, VPS and dedicated servers and worked on each, out of the box, both on Apache and nginX. tcS3 has been tested on wordpress 3.7 - 4.0 and worked well on all versions.

This plugin is being released in beta -- the wide popularity of S3 makes it difficult for me, the only developer on this project, to know every possible use case for it, so I'm relying on feedback from its use to provide further enhancements.

<h3>Current capabilities</h3>
<ul>
	<li> Upload directly to S3 (with the option to delete from your local instance immediately after a successful upload)
	<li> Push all prexisting images on a single wordpress site to S3
	<li> Push or repush a single image to S3 right from within the media library
	<li> Delete media from S3 when it's deleted from your library
	<li> Adds a redundancy layer where it lightly but intelligently figures out if your image is available on S3 and falls back to your webserver's copy of it if it isn't.
</ul>

The plugin, on install, also creates a layer of redundancy to help improve the up-time of your images. It sets up a WP Rewrite rule and updates your images to use this new URL scheme. The rewrite URL references a method within the plugin which reads the HTTP status of the URL to the object on S3. If the method determines the object is there it will 301 redirect the browser to your image on S3. If the object does not resolve on S3 (upload fails, S3 has an outage, etc.) it will check the headers to the URL of the same object on your webserver and 301 redirects the browser there to render the image. If that also fails the image will then 404.

<h3>Advanced features</h3>

The plugin's use of the AWS SDK for PHP allows for a more flexible configuration. Out of the box the plugin is set up to do two things, and if you never change them you'll probably be fine.

<ol>
	<li> Set cache headers on your image so repeat visitors load the image faster and don't cost you a GET against S3
	<li> Performs multithreaded uploads on files larger than 5MB -- larger files can take longer for your webserver to send to S3... those can really slow down your site! So, the plugin will split your files up into chunks no smaller than 5MB and send them to S3 that way, seamlessly, without your having to ask.
</ol>

<h3>Coming (very) soon</h3>
<ul>
	<li> Single configuration for multisite environment -- right now network activating will require webmaster's to define the settings for each of their site's individually (which may be what you want, but in many cases one set of settings is all that's required)
	
	<li> Negotiation for load balanced environments -- currently this plugin is capable of pushing new uploads to S3 no matter how many servers you have. But a push of previously uploaded files is more difficult because they're probably scattered across multiple application servers.

	<li> Mark files as synced if they're improperly marked as not synced. For extremely large libraries, pushing your previous uploads via this plugin is not advised as PHP can be expensive and may timeout. Savvy developers/system administrators will have other ways of syncing previous uploads to S3 and should be able to mark certain or all items as synced to S3.
</ul>

<h3>Unsolicited advice</h3>
While S3 is relatively inexpensive (very inexpensive the more you use it), it's not free and it's not just how much you upload to it, but how much traffic you're getting. If there are a lot of get requests going to it (take a look at <a href="http://aws.amazon.com/s3/pricing/">Amazon's S3 pricing</a> guide) it could get expensive. The cache headers being assigned by this plugin will certainly help, but if you sign up for a <a href="http://www.cloudflare.com">free Cloudflare account</a> and set up your <a href="http://docs.aws.amazon.com/AmazonS3/latest/dev/VirtualHosting.html">S3 bucket as a subdomain</a> that Cloudflare is caching, responses to initial requests will come from S3, but many subsequent requests will hit Cloudflare and cost you nothing (and images will load faster because Cloudflare is a CDN).
