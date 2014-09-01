<?php 

/*
* This script copies uploads from the uploads directory to the same path in S3
* Author: TC McCarthy
* Aug. 30, 2014
*/

use Aws\Common\Exception\MultipartUploadException;
use Aws\S3\Model\MultipartUpload\UploadBuilder;

//find all files related to this upload
$keys[] = $file_data["file"];
foreach($file_data["sizes"] as $size => $data){
	$keys[] = substr($uploads["subdir"], 1) . "/" . $data["file"];
}

//loop through all of the files and upload them
foreach($keys as $key){
	$localFile = $uploads["basedir"] . "/" . $key;
	$remoteFile = $bucketDir . "/" . $uploadDir . "/" .$key;

	//if the file doesn't exist, skip it
	if(!file_exists($localFile))
		continue;

	//build a multistream upload for the file
	$uploader = UploadBuilder::newInstance()
		->setClient($s3Client)
		->setSource($localFile)
		->setBucket($bucket)
		->setKey($remoteFile)
		->setOption('ACL', 'public-read')
		->setConcurrency($concurrentConn)
		->setMinPartSize($minPartSize * 1024 * 1024)
		->build();

	try {
		$upload = $uploader->upload();
	} catch (MultipartUploadException $e) {
		$uploader->abort();
		echo "Upload failed.\n";
		echo $e->getMessage() . "\n";
	}

	//on a successful upload where the settings call for the local file to be deleted right away, delete the local file
	if($upload && $deleteAfterPush){
		unlink($localFile);
	}
}