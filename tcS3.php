<?php
/**
 * Plugin Name: tcS3 -- Send uploads directly to S3
 * Plugin URI: https://mccarthydigitalconsulting.com
 * Description: Allows site admins to push uploads to S3
 * Version: 2.1.1
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
         add_action("admin_enqueue_scripts", [$this, "scripts"], 20);
     }

     public function init()
     {
         $this->base_ = new tcs3_base();
         $this->aws_ops_ = new tcs3_aws_ops($this->base_->options);
         $this->wp_media_ = new tcs3_wp_media($this->base_->options);
         $this->wp_options_ = new tcs3_wp_options($this->base_->options);
         $this->wp_ajax_ = new tcs3_ajax();
     }

     public function scripts()
     {
         wp_enqueue_script("jquery");
         wp_enqueue_script("tcs3", plugin_dir_url(__FILE__) . "js/app.min.js");
     }
 }

 $tcS3 = new tcs3();
