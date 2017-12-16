<?php

/* Customizations for WP Media to talk directly with S3 */

class tcs3_wp_media
{
    public function __construct($options)
    {
        $this->options = $options;

        $this->uploadDir = wp_upload_dir();

        //hooks
        add_action('add_attachment', [$this, 'upload_handler'], 20, 1); //for non-images
    }

    public function upload_handler($post_id)
    {
        global $tcS3;

        $type = get_post_mime_type($post_id);
        $file = get_attached_file($post_id);

        //if this isn't an image
        if (!preg_match("/image\//", $type, $matches)) {
            $key = $tcS3->aws_ops_->build_attachment_key($file);
        }
    }
}
