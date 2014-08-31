<?php 


/*
*
* Tests using the SDK without composer
* Author: TC McCarthy
* Aug. 30, 2014
*/


ini_set("display_errors", 1);

//loads aws stuff
require_once(dirname(__FILE__) . "/aws/aws-autoloader.php");
use Aws\Common\Aws;

$aws = Aws::factory(dirname(__FILE__) . "/config.php");

$s3Client = $aws->get('s3');

$result = $s3Client->putObject(array(
    'Bucket'     => "cdn.totalcomputersusa.com",
    'Key'        => "testing/upload-me.txt",
    'SourceFile' => dirname(__FILE__) . "/upload-me.txt",
));