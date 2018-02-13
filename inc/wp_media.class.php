<?php

/* Customizations for WP Media to talk directly with S3 */

class tcs3_wp_media
{
    public function __construct($options)
    {
        $this->options = $options;

        $this->uploadDir = wp_upload_dir();

        //sending to s3
        add_action('add_attachment', [$this, 'upload_handler'], 20, 1); //for all uploads
        add_filter('wp_generate_attachment_metadata', [$this, 'image_upload_handler'], 20, 2); //for images
        add_filter('wp_update_attachment_metadata', [$this, 'image_upload_handler'], 20, 2);
        add_action("delete_attachment", [$this, "delete_handler"], 20, 1);

        //modifying URLs
        add_filter('wp_get_attachment_url', [$this, 'url_handler'], 20, 1);
        add_filter('wp_get_attachment_image_src', [$this, 'get_attachment_image_src'], 20, 1);
        add_filter('wp_calculate_image_srcset', [$this, 'calculate_image_srcset'], 20, 1);
    }

    public function upload_handler($post_id)
    {
        global $tcS3;

        $file = get_attached_file($post_id);

        $key = $tcS3->aws_ops_->build_attachment_key($file);
        $upload = $tcS3->aws_ops_->s3_upload($file, $key);

        if ($upload) {
            update_post_meta($post_id, "is_on_s3", 1);
        }

        if ($this->options["s3_delete_local"] == 1) {
            unset($file);
        }

        return $upload;
    }

    public function image_upload_handler($file_data, $post_id)
    {
        global $tcS3;

        foreach ($file_data["sizes"] as $size => $details) {
            $file = $this->uploadDir["path"] . "/" . $details["file"];
            $key = $tcS3->aws_ops_->build_attachment_key($file);
            $upload = $tcS3->aws_ops_->s3_upload($file, $key);

            if ($this->options["s3_delete_local"] == 1) {
                unset($file);
            }
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

        $path = $this->get_path_from_file($file);

        if (isset($file_data["sizes"])) {
            foreach ($file_data["sizes"] as $size => $details) {
                $file = $path . "/" . $details["file"];
                $key = $tcS3->aws_ops_->build_attachment_key($file);
                $tcS3->aws_ops_->s3_delete($key);
            }
        }
    }

    public function get_path_from_file($file)
    {
        $path = str_replace($this->options["local_path"], "", $file);
        $path = preg_replace(["/[^\/]+\..+$/", "/^\//"], ["", ""], $path);

        return $path;
    }

    public function url_handler($url)
    {
        $uploadDir = str_replace($this->options["local_path"], "", $this->uploadDir["path"]);
        $uploadDir = preg_replace("/\/\d{4}\/\d{2}/", "", $uploadDir);

        preg_match("/(\/\d{4}\/\d{2}\/.+$)/", $url, $matches);

        $s3_path = preg_replace(["/[\/]+/", "/^\//"], ["/", ""], $uploadDir . "/" . $matches[1]);

        $url = $this->options["s3_url"] . $s3_path;


        return $url;
    }

    public function get_attachment_image_src($image)
    {
        $image["src"] = $this->url_handler($image[0]);
        return $image;
    }

    public function calculate_image_srcset($sources)
    {
        foreach ($sources as $key => $source) {
            $sources[$key]['url'] = $this->url_handler($source['url']);
        }

        return $sources;
    }

    public function get_all_uploads()
    {
        global $wpdb;
        $attachments = $wpdb->get_results("SELECT ID FROM {$wpdb->posts} p WHERE post_type = 'attachment'");

        $attachments = array_map(function ($o) {
            return $o->ID;
        }, $attachments);

        return $attachments;
    }

    public function s3_sync_count()
    {
        global $wpdb;
        $query = "SELECT count(*) as num FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND meta_key = 'is_on_s3' AND meta_value = '1' WHERE post_type = 'attachment'";
        return intval($wpdb->get_var($query));
    }

    public function attachment_count()
    {
        global $wpdb;
        $query = "SELECT count(*) as num FROM {$wpdb->posts} WHERE post_type = 'attachment'";
        return intval($wpdb->get_var($query));
    }
}
