<?php

require_once(dirname(__FILE__) . "/../../../wp-load.php");

$tcS3 = new tcS3();
$post_id = $_GET["postID"];

$file_data = wp_get_attachment_metadata($post_id);

$keys = $tcS3->build_attachment_keys($file_data);

if($tcS3->push_to_S3($keys)){
	$results = array("success" => array("message" => "File successfully pushed to S3"));
	update_post_meta($post_id, "is_on_s3", 1);
} else{
	$results = array("error" => array("message" => "Could not send to S3"));
}

echo json_encode($results);

?>

