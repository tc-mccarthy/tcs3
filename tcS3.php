<?php
/**
 * Plugin Name: TCS3 -- Send uploads directly to S3
 * Plugin URI: http://www.tc-mccarthy.com
 * Description: Allows site admins to push uploads to S3
 * Version: 1.0
 * Author: TC McCarthy
 * Author URI: http://www.tc-mccarthy.com
 * License: GPL2
 */
require(dirname(__FILE__) . "/aws/aws-autoloader.php");

use Aws\Common\Aws;
use Aws\Common\Exception\MultipartUploadException;
use Aws\S3\Model\MultipartUpload\UploadBuilder;

class tcS3 {

    //declare variables
    public $aws;
    public $s3Client;
    public $uploads;
    public $options;
    public $uploadDir;
    public $pluginDir;

    /**
     * Construct the plugin object
     */
    public function __construct() {

        $this->pluginDir = @plugin_dir_url();

        //send new uploads to S3
        add_filter('wp_generate_attachment_metadata', array($this, 'create_on_S3'));

        //send delete requesrs to S3
        add_action("delete_attachment", array($this, "delete_from_library"));

        //send crop/rotate/file changes to S3
        add_filter('wp_update_attachment_metadata', array($this, 'update_on_s3'), 10, 5);

        //setup admin menu
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
        add_action('admin_init', array($this, 'enqueue_admin_scripts'));

        //add new column to media library
        add_filter('manage_media_columns', array($this, 'create_s3_media_column'));
        add_action('manage_media_custom_column', array($this, 'create_s3_media_column_content'), 10, 2);

        //add the push to S3 link to individual attachments
        add_filter( 'media_row_actions', array( $this, 'push_single_to_S3' ), 10, 2 );

        //setup plugin on activation
        register_activation_hook(__FILE__, array($this, 'activate'));

        if (isset($_POST["tcS3_option_submit"])) {
            $this->save_s3_settings();
        }

        //setup options
        $this->options = get_option("tcS3_options");

        //set up aws
        $this->aws = Aws::factory($this->build_aws_config());
        $this->s3Client = $this->aws->get('s3');
        $this->uploads = wp_upload_dir();

        preg_match("/\/wp-content(.+)$/", $this->uploads["basedir"], $matches);
        $this->uploadDir = $matches[1];
    }

    //config functions
    public function build_aws_config() {
        return array(
            'key' => $this->options["access_key"],
            'secret' => $this->options["access_secret"],
            'region' => $this->options["bucket_region"]
        );
    }

    public static function activate() {

        $options = array(
            "bucket" => "",
            "bucket_path" => "",
            "bucket_region" => "",
            "concurrent_conn" => 10,
            "min_part_size" => 5,
            "access_key" => "",
            "access_secret" => "",
            "delete_after_push" => 1,
        );

        add_option("tcS3_options", $options);
    }

    /**
     * Deactivate the plugin
     */
    public static function deactivate() {
        // Do nothing
    }

    /******utility functions********/

    //upload a series of keys to S3
    public function push_to_s3($keys) {
        set_time_limit(120);
        
        $errors = 0;
        foreach ($keys as $key) {
            $localFile = $this->uploads["basedir"] . "/" . $key;
            $remoteFile = $this->options["bucket_path"] . "/" . $this->uploadDir . "/" . $key;

            //if the file doesn't exist, skip it
            if (!file_exists($localFile)) {
                continue;
            }

            //build a multistream upload for the file
            $uploader = UploadBuilder::newInstance()
                ->setClient($this->s3Client)
                ->setSource($localFile)
                ->setBucket($this->options["bucket"])
                ->setKey($remoteFile)
                ->setOption('ACL', 'public-read')
                ->setConcurrency($this->options["concurrent_conn"])
                ->setMinPartSize($this->options["min_part_size"] * 1024 * 1024)
                ->build();

            try {
                $upload = $uploader->upload();
            } catch (MultipartUploadException $e) {
                $uploader->abort();
                echo "Upload failed.\n";
                echo $e->getMessage() . "\n";
                $errors++;
            }

            //on a successful upload where the settings call for the local file to be deleted right away, delete the local file
            if ($upload && $this->options["s3_delete_local"] == 1) {
                unlink($localFile);
            }
        }

        return ($errors == 0) ? true : false;
    }

    //function to delete object(s) from S3
    public function delete_from_S3($keys) {
        foreach ($keys as $key) {
            $file = $this->options["bucket_path"] . "/" . $this->uploadDir . "/" . $key;
            if ($this->s3Client->doesObjectExist($this->options["bucket"], $file)) {
                $result = $this->s3Client->deleteObject(
                    array(
                        'Bucket' => $this->options["bucket"],
                        'Key' => $file
                    )
                );
            }
        }
    }

    //find the subdirectory from the filename
    public function get_subdir_from_filename($filename) {
        preg_match("/([0-9]+\/[0-9]+)\/(.+)$/", $filename, $matches);
        return $matches[1];
    }

    //build all of the keys associated with an attachment for upload
    public function build_attachment_keys($file_data) {
        $datePath = $this->get_subdir_from_filename($file_data["file"]);

        $keys[] = $file_data["file"];
        foreach ($file_data["sizes"] as $size => $data) {
            $keys[] = $datePath . "/" . $data["file"];
        }

        return $keys;
    }

    //send output to log
    public function dump_to_log($mixed) {
        ob_start();
        var_dump($mixed);
        error_log(ob_get_contents());
        ob_end_clean();
    }

    //mark image as being on S3
    public function mark_as_transferred($file_data){
        global $wpdb;
        $post_id = $wpdb->get_var("SELECT post_id FROM $wpdb->postmeta WHERE meta_value = '{$file_data["file"]}' LIMIT 1");
        update_post_meta($post_id, "is_on_s3", 1);
    }

    /*   * ***wordpress extensions***** */

    //function for creating new uploads on S3
    public function create_on_S3($file_data) {
        $keys = $this->build_attachment_keys($file_data);
        if($this->push_to_s3($keys)){
            $this->mark_as_transferred($file_data);
        }

        return $file_data;
    }

    //send updated images to S3 after image editor is used
    public function update_on_s3($file_data, $post_id) {
        $keys = $this->build_attachment_keys($file_data);
        if($this->push_to_s3($keys)){
            $this->mark_as_transferred($file_data);
        }

        return $file_data;
    }

    //this function is called when you delete an element from the media library
    public function delete_from_library($post_id) {
        $file_data = wp_get_attachment_metadata($post_id);
        $this->delete_from_S3($this->build_attachment_keys($file_data));
    }

    //set up the additional column in the media library to indicate whether an image is on S3 or not
    public function create_s3_media_column($defaults){
        $defaults["s3"] = 'S3';
        return $defaults;
    }

    public function create_s3_media_column_content($column_name, $post_ID){
        switch($column_name){
            case 's3':
                $is_on_s3 = get_post_meta($post_ID, 'is_on_s3', true);
                if($is_on_s3 == 1){
                    $uploadedClasses = 'uploaded active';
                    $notUploadedClasses = 'notuploaded';
                } else{
                    $uploadedClasses = 'uploaded';
                    $notUploadedClasses = 'notuploaded active';
                }

                echo "<img class='{$uploadedClasses}' src='" . plugin_dir_url(__FILE__) . "img/s3-logo.png' />";
                echo "<img class='{$notUploadedClasses}' src='" . plugin_dir_url(__FILE__) . "img/s3-logo-bw.png' />";
                
                break;
        }
    }

    public function push_single_to_S3( $actions, $post ) {
       
        $url = $this->pluginDir . "tcS3/tcS3-ajax.php?action=push_single&postID={$post->ID}";
        $actions['regenerate_thumbnails'] = '<a class="push_single_to_S3" href="' . esc_url( $url ) . '" title="' . esc_attr("Send this file to S3") . '">' . "Send this file to S3" . '</a>';

        return $actions;
    }

    public function get_all_attachments($skip_synced = true){
        global $wpdb;

        $whereClause = ($skip_synced) ? "AND b.post_id IS NULL" : "";

        $attachments = $wpdb->get_results("
            SELECT ID 
            FROM $wpdb->posts as a
            LEFT JOIN $wpdb->postmeta as b ON a.ID = b.post_id AND b.meta_key = 'is_on_s3'
            WHERE a.post_type = 'attachment'
            {$whereClause}
            GROUP BY ID
            ORDER BY ID DESC
        ");

        foreach($attachments as $id){
            $ids[] = intval($id->ID);
        }

        return $ids;
    }

    /***** admin interface *****/

    public function add_plugin_page() {
        add_options_page(
            'tcS3 Admin', 'tcS3 Admin configuration', 'manage_options', 'tcS3-admin', array($this, 'create_admin_page')
        );
    }

    public function enqueue_admin_scripts(){

        wp_enqueue_script("jquery");
        wp_enqueue_script( 'jquery-ui-progressbar');  // the progress bar
        wp_enqueue_script("tcS3", $this->pluginDir . "tcS3/js/tcS3.js");
        wp_enqueue_style("jquery-ui", "//ajax.googleapis.com/ajax/libs/jqueryui/1.11.1/themes/smoothness/jquery-ui.css");
        wp_enqueue_style("tcS3", $this->pluginDir . "tcS3/css/tcS3.css");

    }

    public function create_admin_page() {
        ?>
        <div class="wrap">
        <?php screen_icon(); ?>
            <h2>tcS3 Settings</h2>           
            <form method="post" action="options-general.php?page=tcS3-admin">
        <?php
        // This prints out all hidden setting fields
        settings_fields('tcS3_option_group');
        do_settings_sections('tcS3-setting-admin');
        submit_button();
        ?>
            </form>
        </div>
                <?php
            }

            public function page_init() {
                register_setting(
                    'tcS3_option_group', // Option group
                    'tcS3-settings', // Option name
                    array($this, 'sanitize') // Sanitize
                );

                add_settings_section(
                    'tcS3-settings', // ID
                    'tcS3 Configuration Parameters', // Title
                    array($this, 'print_section_info'), // Callback
                    'tcS3-setting-admin' // Page
                );

                add_settings_field(
                    'aws_key', // ID
                    'AWS Key', // Title 
                    array($this, 'aws_key_callback'), // Callback
                    'tcS3-setting-admin', // Page
                    'tcS3-settings' // Section           
                );

                add_settings_field(
                    'aws_secret', 'AWS Secret', array($this, 'aws_secret_callback'), 'tcS3-setting-admin', 'tcS3-settings'
                );

                add_settings_field(
                    's3_bucket', 'S3 Bucket', array($this, 's3_bucket_callback'), 'tcS3-setting-admin', 'tcS3-settings'
                );

                add_settings_field(
                    's3_bucket_path', 'S3 Bucket Path', array($this, 's3_bucket_path_callback'), 'tcS3-setting-admin', 'tcS3-settings'
                );

                add_settings_field(
                    's3_bucket_region', 'S3 Bucket Region', array($this, 's3_bucket_region_callback'), 'tcS3-setting-admin', 'tcS3-settings'
                );

                add_settings_field(
                    's3_concurrent', 'S3 Concurrent Connections', array($this, 's3_concurrent_callback'), 'tcS3-setting-admin', 'tcS3-settings'
                );

                add_settings_field(
                    's3_min_part_size', 'S3 Minimum Part Size (MB)', array($this, 's3_min_part_size_callback'), 'tcS3-setting-admin', 'tcS3-settings'
                );

                add_settings_field(
                    's3_delete_local', 'Delete local file after upload', array($this, 's3_delete_local_callback'), 'tcS3-setting-admin', 'tcS3-settings'
                );

                add_settings_section(
                    'tcS3-migration', // ID
                    'tcS3 Migration Tools', // Title
                    array($this, 'migration_output'), // Callback
                    'tcS3-setting-admin' // Page
                );


            }

            public function sanitize($input) {
                $new_input = array();
                if (isset($input['id_number']))
                    $new_input['id_number'] = absint($input['id_number']);

                if (isset($input['title']))
                    $new_input['title'] = sanitize_text_field($input['title']);

                return $new_input;
            }

            public function print_section_info() {
                
            }


            public function migration_output() {
                echo "
                    <div class='migration'>
                        <div class='progressbar'>
                            <div class='progressbar-label'></div>
                        </div>
                        <input id='s3_sync' type='button' class='button sync' value='Sync' data-plugin-path='" . $this->pluginDir . "tcS3/' />
                    </div>
                ";
            }

            public function aws_key_callback() {
                $optionKey = 'access_key';
                $helperText = 'We recommend you create an AWS IAM user just for S3 and use its key. If your blog is every exploited, this method will prevent hackers from doing too much damage to your AWS account if they get their hands on your keys.';

                printf(
                    '<input type="hidden" name="tcS3_option_submit" value="1" /><input type="text" id="%s" name="tcS3_option[%s]" value="%s" /><div><small>%s</small></div>', $optionKey, $optionKey, isset($this->options[$optionKey]) ? esc_attr($this->options[$optionKey]) : '', $helperText
                );
            }

            public function aws_secret_callback() {
                $optionKey = 'access_secret';
                $helperText = 'We recommend you create an AWS IAM user just for S3 and use its secret. If your blog is every exploited, this method will prevent hackers from doing too much damage to your AWS account if they get their hands on your secrets.';

                printf(
                    '<input type="text" id="%s" name="tcS3_option[%s]" value="%s" /><div><small>%s</small></div>', $optionKey, $optionKey, isset($this->options[$optionKey]) ? esc_attr($this->options[$optionKey]) : '', $helperText
                );
            }

            public function s3_bucket_callback() {
                $optionKey = 'bucket';
                $helperText = 'The name of your S3 bucket';

                printf(
                    '<input type="text" id="%s" name="tcS3_option[%s]" value="%s" /><div><small>%s</small></div>', $optionKey, $optionKey, isset($this->options[$optionKey]) ? esc_attr($this->options[$optionKey]) : '', $helperText
                );
            }

            public function s3_bucket_path_callback() {
                $optionKey = 'bucket_path';
                $helperText = 'The path within your S3 bucket that Wordpress should use as your "uploads" directory';

                printf(
                    '<input type="text" id="%s" name="tcS3_option[%s]" value="%s" /><div><small>%s</small></div>', $optionKey, $optionKey, isset($this->options[$optionKey]) ? esc_attr($this->options[$optionKey]) : '', $helperText
                );
            }

            public function s3_bucket_region_callback() {
                $regions = array(
                    array("value" => "ap-northeast-1", "display" => "ap-northeast-1", "options" => ""),
                    array("value" => "ap-southeast-1", "display" => "ap-southeast-1", "options" => ""),
                    array("value" => "ap-southeast-2", "display" => "ap-southeast-2", "options" => ""),
                    array("value" => "eu-west-1", "display" => "eu-west-1", "options" => ""),
                    array("value" => "sa-east-1", "display" => "sa-east-1", "options" => ""),
                    array("value" => "us-east-1", "display" => "us-east-1", "options" => ""),
                    array("value" => "us-west-1", "display" => "us-west-1", "options" => ""),
                    array("value" => "us-west-2", "display" => "us-west-2", "options" => "")
                );

                foreach ($regions as $key => $region) {
                    if ($region["value"] == $this->options["bucket_region"]) {
                        $regions[$key]["options"] = "selected";
                        break;
                    }
                }
                $optionKey = 'bucket_region';
                $helperText = 'What region is your S3 bucket in?';

                printf(
                    '<select id="%s" name="tcS3_option[%s]" />%s</select><div><small>%s</small></div>', $optionKey, $optionKey, $this->array_to_options($regions), $helperText
                );
            }

            public function s3_concurrent_callback() {
                $optionKey = 'concurrent_conn';
                $helperText = 'How many concurrent connections should the server make on upload to S3? (Default: 10)';

                printf(
                    '<input type="text" id="%s" name="tcS3_option[%s]" value="%s" /><div><small>%s</small></div>', $optionKey, $optionKey, isset($this->options[$optionKey]) ? esc_attr($this->options[$optionKey]) : '', $helperText
                );
            }

            public function s3_min_part_size_callback() {
                $optionKey = 'min_part_size';
                $helperText = 'What size chunks should Wordpress break the file up into on S3 upload (in MB)? (Default: 5)';

                printf(
                    '<input type="text" id="%s" name="tcS3_option[%s]" value="%s" /><div><small>%s</small></div>', $optionKey, $optionKey, isset($this->options[$optionKey]) ? esc_attr($this->options[$optionKey]) : '', $helperText
                );
            }

            public function s3_delete_local_callback() {
                $optionKey = 's3_delete_local';
                $helperText = 'Should uploaded files be removed from your local server?';

                printf(
                    '<input type="radio" id="%s" name="tcS3_option[%s]" value="1" %s /> Yes 
                <br /><input type="radio" id="%s" name="tcS3_option[%s]" value="0" %s /> No 
                <div><small>%s</small></div>', $optionKey, $optionKey, ($this->options[$optionKey] == 1) ? "checked" : "", $optionKey, $optionKey, ($this->options[$optionKey] == 0) ? "checked" : "", $helperText
                );
            }

            public function save_s3_settings() {
                foreach ($_POST["tcS3_option"] as $key => $value) {
                    if ($key == "bucket_path") {
                        $options[$key] = "/" . trim($value, "/");
                        continue;
                    }

                    $options[$key] = trim(sanitize_text_field($value));
                }

                update_option("tcS3_options", $options);
            }

            public function array_to_options($arrays) {
                foreach ($arrays as $option) {
                    $option = (object) $option;
                    $options[] = "<option value = '{$option->value}' {$option->options}>{$option->display}</option>";
                }
                return implode("", $options);
            }

        }

        if (is_admin())
            $tcS3 = new tcS3();