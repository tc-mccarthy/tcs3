<?php

/* AJAX OPS */

class tcs3_ajax
{
    public function __construct()
    {
        add_action("wp_ajax_push_single", [$this, "push_single"]);
        add_action("wp_ajax_get_attachment_ids", [$this, "get_attachment_ids"]);
        add_action("wp_ajax_sync_all", [$this, "sync_all"]);
        add_action("wp_ajax_mark_all_synced", [$this, "mark_all_synced"]);
    }

    public function push_single()
    {
        global $tcS3;

        $post_id = $_POST["postID"];

        $upload = $tcS3->wp_media_->upload_handler($post_id);

        echo json_encode(["success" => $upload]);
        exit();
    }

    public function sync_all()
    {
        global $tcS3;

        $attachments = $tcS3->wp_media_->get_all_uploads();
        $response = ["success" => [], "error" => [], "message" => "Done!"];


        foreach ($attachments as $post_id) {
            $upload = $tcS3->wp_media_->upload_handler($post_id);
            $file_data = wp_get_attachment_metadata($post_id);
            $file = get_attached_file($post_id);
            $path = $this->get_path_from_file($file);

            $success = $upload;

            if (isset($file_data["sizes"])) {
                foreach ($file_data["sizes"] as $size => $details) {
                    $file = $path . "/" . $details["file"];
                    $key = $tcS3->aws_ops_->build_attachment_key($file);
                    $upload = $tcS3->aws_ops_->s3_upload($file, $key);

                    if (!$upload) {
                        $success = false;
                    }
                }
            }

            if ($success) {
                $response["success"][] = $post_id;
            } else {
                $response["error"][] = $post_id;
            }
        }

        wp_send_json_success($response);
    }

    public function mark_all_synced()
    {
        global $tcS3;

        $attachments = $tcS3->wp_media_->get_all_uploads();
        $success = true;

        foreach ($attachments as $post_id) {
            $result = update_post_meta($post_id, "is_on_s3", 1);
        }

        if ($success) {
            wp_send_json_success(__('Done!', 'default'));
        } else {
            wp_send_json_error(__('Failed!', 'default'));
        }
    }

    public function get_attachment_ids()
    {
        global $tcS3;

        $attachments = $tcS3->wp_media_->get_all_uploads();

        echo json_encode($attachments);
        exit();
    }
}
