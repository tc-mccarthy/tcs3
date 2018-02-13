<?php

/* Setup class */


class tcs3_base
{
    public function __construct()
    {
        global $tcS3;

        $this->network_activated = $this->network_activation_check();
        $this->build_options();
        $this->migrate();
    }

    public function network_activation_check()
    {
        if (! function_exists('is_plugin_active_for_network')) {
            require_once(ABSPATH . '/wp-admin/includes/plugin.php');
        }
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
          "bucket_path" => "/",
          "local_path" => WP_CONTENT_DIR,
          "local_url" => WP_CONTENT_URL,
          "s3_url" => "",
          "bucket_region" => "us-east-1",
          "concurrent_conn" => 10,
          "min_part_size" => 5,
          "s3_delete_local" => 0,
          "s3_cache_time" => 86400
        ];

        $options = $default;

        //if this is a network site and the plugin is network activated, check for settings
        if ($this->network_activated) {
            $network_options = $this->unserialize(get_site_option("tcS3_options"));
        }

        if (is_array($network_options)) {
            $options = array_merge($options, $network_options);
        }

        //if this site is offering up it's own config, adopt those as well
        $site_override = $this->unserialize(get_option("tcS3_options"));

        if (!!$site_override) {
            $options = array_merge($options, $site_override);
        }

        if (!empty($options["access_key_variable"])) {
            $options["access_key"] = getenv($options["access_key_variable"]);
        }

        if (!empty($options["access_secret_variable"])) {
            $options["access_secret"] = getenv($options["access_secret_variable"]);
        }

        $this->options = $options;
    }

    public function unserialize($str)
    {
        if (is_serialized($str)) {
            $str = unserialize($str);
        }

        return $str;
    }

    public function migrate()
    {
        if ($this->network_activation_check()) {
            $version = get_site_option("tcS3_version");
        } else {
            $version = get_option("tcS3_version");
        }

        $this->v = (!$version) ? 0 : floatval($version);

        // MIGRATION TO VERSION 2.0
        if ($this->v < 2) {
            if ($this->network_activation_check()) {
                // move network settings to primary site
                switch_to_blog(1);
                update_option("tcS3_network_options", get_site_option("tcS3_options"));
                restore_current_blog();
                update_site_option("tcS3_version", "2.0");
            } else {
                update_option("tcS3_version", "2.0");
            }
        }
    }
}
