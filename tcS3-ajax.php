<?php

require_once(dirname(__FILE__) . "/../../../wp-load.php");

$tcS3 = new tcS3();




switch($_GET["action"]){

	case "push_single":
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
		break;

	case "get_attachment_ids":
		$full_sync = ($_GET["full_sync"] == 1 || !isset($_GET["full_sync"])) ? true : false;
		echo json_encode($tcS3->get_all_attachments($full_sync));
		break;

	case "mark_all_synced":
		echo update_site_option("tcS3_mark_all_attachments", 1);
		break;
}
?>

