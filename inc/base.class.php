<?php

/* Setup class */


class tcs3_base
{
    public function __construct()
    {
        $this->network_activated = $this->network_activation_check();

        if ($this->network_activated) {
            $this->options = get_site_option("tcS3_options");
        } else {
            $this->options = get_option("tcS3_options");
        }

        if (!empty($this->options["access_key_variable"])) {
            $this->options["access_key"] = getenv($this->options["access_key_variable"]);
        }

        if (!empty($this->options["access_secret_variable"])) {
            $this->options["access_secret"] = getenv($this->options["access_secret_variable"]);
        }
    }

    public function network_activation_check()
    {
        // if (!function_exists('is_plugin_active_for_network')) {
        //     require_once(ABSPATH . '/wp-admin/includes/plugin.php');
        // }

        return is_plugin_active_for_network("tcs3/tcS3.php");
    }
}
