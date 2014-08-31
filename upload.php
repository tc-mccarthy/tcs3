<?php 

/*
* This script copies uploads from the uploads directory to the same path in S3
* Author: TC McCarthy
* Aug. 30, 2014
*/

//loads aws libs
require_once(dirname(__FILE__) . "/aws/aws-autoloader.php");
use Aws\Common\Exception\MultipartUploadException;
use Aws\S3\Model\MultipartUpload\UploadBuilder;
use Aws\Common\Aws;

//setup variables
$uploads = wp_upload_dir();

preg_match("/\/wp-content(.+)$/", $uploads["basedir"], $matches);
$uploadDir = $matches[1];


//set up AWS
$aws = Aws::factory(dirname(__FILE__) . "/config.php");
$s3Client = $aws->get('s3');
$bucket = "cdn.totalcomputersusa.com";
$bucketDir = "testing";
$concurrentConn = 10;
$minPartSize = 5;

//find all files related to this upload
$keys[] = $file_data["file"];
foreach($file_data["sizes"] as $size => $data){
	$keys[] = substr($uploads["subdir"], 1) . "/" . $data["file"];
}

//loop through all of the files and upload them
foreach($keys as $key){

	//if the file doesn't exist, skip it
	if(!file_exists($uploads["basedir"] . "/" . $key))
		continue;

	//build a multistream upload for the file
	$uploader = UploadBuilder::newInstance()
		->setClient($s3Client)
		->setSource($uploads["basedir"] . "/" . $key)
		->setBucket($bucket)
		->setKey($bucketDir . "/" . $uploadDir . "/" .$key)
		->setOption('ACL', 'public-read')
		->setConcurrency($concurrentConn)
		->setMinPartSize($minPartSize * 1024 * 1024)
		->build();

	try {
		$uploader->upload();
	} catch (MultipartUploadException $e) {
		$uploader->abort();
		echo "Upload failed.\n";
		echo $e->getMessage() . "\n";
	}
}