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
}
