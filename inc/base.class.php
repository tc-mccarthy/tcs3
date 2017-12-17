<?php

/* Setup class */


class tcs3_base
{
    public function __construct()
    {
        $this->network_activated = $this->network_activation_check();
        $this->build_options();
    }

    public function network_activation_check()
    {
        // if (!function_exists('is_plugin_active_for_network')) {
        //     require_once(ABSPATH . '/wp-admin/includes/plugin.php');
        // }

        return is_plugin_active_for_network("tcs3/tcS3.php");
    }

    public function build_options()
    {
        $default = [
        "access_key" => "",
        "access_secret" => "",
        "access_key_variable" => "",
        "access_secret_variable" => "",
        "bucket" => "",
        "bucket_path" => "",
        "local_path" => WP_CONTENT_DIR,
        "s3_url" => "",
        "bucket_region" => "us-east-1",
        "concurrent_conn" => 10,
        "min_part_size" => 5,
        "s3_delete_local" => 0
      ];



        if ($this->network_activated) {
            $user_options = get_site_option("tcS3_options");
        } else {
            $user_options = get_option("tcS3_options");
        }

        if (!empty($user_options["access_key_variable"])) {
            $user_options["access_key"] = getenv($user_options["access_key_variable"]);
        }

        if (!empty($user_options["access_secret_variable"])) {
            $user_options["access_secret"] = getenv($user_options["access_secret_variable"]);
        }

        $this->options = array_merge($default, $user_options);
    }
}
