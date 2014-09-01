<?php

/**
 * Plugin Name: TCS3 -- Upload directly to S3
 * Plugin URI: http://www.tc-mccarthy.com
 * Description: Allows site admins to push uploads to S3
 * Version: 1.0
 * Author: TC McCarthy
 * Author URI: http://www.tc-mccarthy.com
 * License: GPL2
 */

require(dirname(__FILE__) . "/aws/aws-autoloader.php");
use Aws\Common\Aws;

//setup variables
$uploads = wp_upload_dir();

preg_match("/\/wp-content(.+)$/", $uploads["basedir"], $matches);
$uploadDir = $matches[1];
$deleteAfterPush = (1 == 1) ? true : false;


//set up AWS
$aws = Aws::factory(dirname(__FILE__) . "/config.php");
$s3Client = $aws->get('s3');
$bucket = "cdn.totalcomputersusa.com";
$bucketDir = "testing";
$concurrentConn = 10;
$minPartSize = 5;

add_filter('wp_generate_attachment_metadata', 'push_to_S3');

function push_to_S3($file_data){	
	global $uploads, $uploadDir, $deleteAfterPush, $aws, $s3Client, $bucket, $bucketDir, $concurrentConn, $minPartSize;
	include_once(dirname(__FILE__) . "/upload.php");
	return $file_data;
}

add_action("delete_attachment", "delete_from_S3");

function delete_from_S3($post_id){
	global $uploads, $uploadDir, $deleteAfterPush, $aws, $s3Client, $bucket, $bucketDir, $concurrentConn, $minPartSize;

	$file_data = wp_get_attachment_metadata($post_id);	

	if(!is_array($file_data))
		return;
	
	$keys[] = $file_data["file"];
	foreach($file_data["sizes"] as $size => $data){
		$keys[] = substr($uploads["subdir"], 1) . "/" . $data["file"];
	}

	foreach($keys as $key){
		$result = $s3Client->deleteObject(array(
	   	 	// Bucket is required
	   	 	'Bucket' => $bucket,
	   	 	'Key' => $bucketDir . "/" . $uploadDir . "/" . $key,
		));
	}

	return;
}