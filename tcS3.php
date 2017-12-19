<?php
/**
 * Plugin Name: tcS3 -- Send uploads directly to S3
 * Plugin URI: https://mccarthydigitalconsulting.com
 * Description: Allows site admins to push uploads to S3
 * Version: 2.0.0
 * Author: TC McCarthy, McCarthy Digital Consulting
 * Author URI: https://mccarthydigitalconsulting.com
 * License: GPL2
 */

 // composer autoloader
 require_once(__DIR__ . "/vendor/autoload.php");

 class tcs3
 {
     public function __construct()
     {
         add_action("after_setup_theme", [$this, "init"], 20);
         // $this->init();
     }

     public function init()
     {
         $this->migrate();
         $this->base_ = new tcs3_base();
         $this->aws_ops_ = new tcs3_aws_ops($this->base_->options);
         $this->wp_media_ = new tcs3_wp_media($this->base_->options);
         $this->wp_options_ = new tcs3_wp_options($this->base_->options);
     }

     public function migrate()
     {
         if (is_multisite()) {
             $network_option_key = "tcS3_network_options";

             //switch to the primary blog
             switch_to_blog(1);
             $migrated = get_option($network_option_key);

             if (!$migrated) {
                 update_option($network_option_key, get_site_option("tcS3_options"));
             }

             restore_current_blog();
         }
     }
 }

 $tcS3 = new tcs3();
