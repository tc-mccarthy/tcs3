<?php

/* Customizations for WP Media to talk directly with S3 */

class tcs3_wp_media
{
    public function __construct($options)
    {
        $this->options = $options;

        $this->uploadDir = wp_upload_dir();

        //hooks
        add_action('add_attachment', [$this, 'upload_handler'], 20, 1); //for all uploads
        add_filter('wp_generate_attachment_metadata', [$this, 'image_upload_handler'], 20, 2); //for images
        add_filter('wp_update_attachment_metadata', [$this, 'image_upload_handler'], 20, 2);
        add_action("delete_attachment", [$this, "delete_handler"], 20, 1);
    }

    public function upload_handler($post_id)
    {
        global $tcS3;

        $type = get_post_mime_type($post_id);
        $file = get_attached_file($post_id);

        $key = $tcS3->aws_ops_->build_attachment_key($file);
        $upload = $tcS3->aws_ops_->s3_upload($file, $key);

        if ($upload) {
            update_post_meta($post_id, "is_on_s3", 1);
        }
    }

    public function image_upload_handler($file_data, $post_id)
    {
        global $tcS3;

        foreach ($file_data["sizes"] as $size => $details) {
            $file = $this->uploadDir["path"] . "/" . $details["file"];
            $key = $tcS3->aws_ops_->build_attachment_key($file);
            $upload = $tcS3->aws_ops_->s3_upload($file, $key);
        }

        return $file_data;
    }

    public function delete_handler($post_id)
    {
        global $tcS3;

        $file_data = wp_get_attachment_metadata($post_id);
        $file = get_attached_file($post_id);
        $key = $tcS3->aws_ops_->build_attachment_key($file);
        $tcS3->aws_ops_->s3_delete($key);

        $path = str_replace($this->options["local_path"], "", $file);
        $path = preg_replace(["/[^\/]+\..+$/", "/^\//"], ["", ""], $path);

        if (isset($file_data["sizes"])) {
            foreach ($file_data["sizes"] as $size => $details) {
                $file = $path . "/" . $details["file"];
                $key = $tcS3->aws_ops_->build_attachment_key($file);
                error_log(json_encode(["file" => $key]));
                $tcS3->aws_ops_->s3_delete($key);
            }
        }
    }
}
