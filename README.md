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

The check mentioned above will go a long way to help improve the up-time of your images. It sets up a WP Rewrite rule and then modifies your images to use this new URL scheme. Accessing the images through the new URL allows the plugin to check the file's headers on S3 to determing if the file can be loaded form your bucket. If it can, your users browser is redirected to the image on S3. If it can't, the plugin then checks the local URLs you provide in the plugin setup and if it finds the image there redirects the user's browser there. If that also fails the image 404s.

<h3>Advanced features</h3>

The plugin's use of the AWS SDK for PHP allows for a more flexible configuration. Out of the box the plugin is set up to do two things, and if you never change them you'll probably be fine.

<ol>
	<li> Set cache headers on your image so repeat visitors load the image faster and don't cost you a GET against S3
	<li> Performs multithreaded uploads on files larger than 5MB -- larger files can take longer for your webserver to send to S3... those can really slow down your site! So, the plugin will split your files up into chunks no smaller than 5MB and send them to S3 that way, seamlessly, without your having to ask.
	<li> Optional Memcache (only displays when your server supports it) or file system (supported everywhere) caching of header lookup results to save on PHP resource usage. You can set the TTL of the header lookup cache AND the cache headers on S3 right from within the dashboard
</ol>

<h3>Install</h3>
This plugin is installed just like any other. Simply upload the zip file you can download from <a href="https://github.com/tc-mccarthy/tcS3">github</a> and upload it using the WordPress dashboard or FTP. This plugin has been submitted to the Wordpress team for review as well and will hopefully be available in the Wordpress plugin repository soon.

<h3>Unsolicited advice</h3>
While S3 is relatively inexpensive (very inexpensive the more you use it), it's not free and it's not just how much you upload to it, but how much traffic you're getting. If your bucket is receiving a lot of GET requests (which happens when you have a lot of traffic on your site) it could get expensive (take a look at <a href="http://aws.amazon.com/s3/pricing/">Amazon's S3 pricing</a> guide). The cache headers being assigned by this plugin will certainly help, but if you sign up for a <a href="http://www.cloudflare.com">free Cloudflare account</a> and set up your <a href="http://docs.aws.amazon.com/AmazonS3/latest/dev/VirtualHosting.html">S3 bucket as a subdomain</a> that Cloudflare is caching, responses to initial requests will come from S3, but many subsequent requests will hit Cloudflare and cost you nothing (and images will load faster because Cloudflare is a CDN).