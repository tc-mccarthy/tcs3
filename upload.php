<?php 

/*
*
* Tests using the SDK without composer
* Author: TC McCarthy
* Aug. 30, 2014
*/

ini_set("display_errors", 1);
$start = time();

$file = $_FILES["file"];
$uploadDir = dirname(__FILE__) . "/";

if(!move_uploaded_file($file["tmp_name"],  $uploadDir . $file["name"])){
	die("Fuck. Check your perms, bitch");
} else{

}

function startsWith($haystack, $needle)
{
	return $needle === "" || strpos($haystack, $needle) === 0;
}
function endsWith($haystack, $needle)
{
	return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
}


//loads aws stuff
require_once(dirname(__FILE__) . "/aws/aws-autoloader.php");
use Aws\Common\Exception\MultipartUploadException;
use Aws\S3\Model\MultipartUpload\UploadBuilder;
use Aws\Common\Aws;

$uploadDir = dirname(__FILE__) . "/";

$aws = Aws::factory(dirname(__FILE__) . "/config.php");

$s3Client = $aws->get('s3');
$key = (endsWith($_POST["path"], "/")) ? $_POST["path"].$file["name"] : $_POST["path"]."/".$file["name"];

$uploader = UploadBuilder::newInstance()
	->setClient($s3Client)
	->setSource($uploadDir . $file["name"])
	->setBucket("cdn.totalcomputersusa.com")
	->setKey($key)
	->setOption('ACL', 'public-read')
	->setConcurrency(10)
	->setMinPartSize(5 * 1024 * 1024)
	->build();

try {
	$uploader->upload();
	echo "Upload completed in " . gmdate("H:i:s", (time() - $start)) . ".\n";
} catch (MultipartUploadException $e) {
	$uploader->abort();
	echo "Upload failed.\n";
	echo $e->getMessage() . "\n";
}
