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
        add_action("wp_ajax_regenerate_all_thumbnails", [$this, "regenerate_all_thumbnails"]);
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

    public function regenerate_all_thumbnails()
    {
        global $tcS3, $wpdb;

        $attachments = $wpdb->get_results("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key LIKE '%attachment_meta%'");

        $attachment_ids = array_map(function ($o) {
            return $o->post_id;
        }, $attachments);

        foreach ($attachment_ids as $id) {
            $url = wp_get_attachment_image_url($id, "original");
            $file = $this->download_image($url);
            wp_generate_attachment_metadata($id, $file);
        }
    }

    public function download_image($url)
    {
        global $tcS3;

        set_time_limit(0);

        $url_path = $tcS3->base_->options["local_path"] . "/" . str_replace($tcS3->base_->options["s3_url"], "", $url);
        $file_path = dirname($url_path);

        error_log("PATH: " . $url_path);
        error_log("FILE PATH: " . $file_path);

        mkdir($file_path, 0777, true);

        //This is the file where we save the    information
        $fp = fopen($url_path, 'w+');

        //Here is the file we are downloading, replace spaces with %20
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 50);

        // write curl response to file
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        // get curl response
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);

        return $url_path;
    }
}
