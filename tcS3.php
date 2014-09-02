<?php
/**
 * Plugin Name: TCS3 -- Upload directly to S3
 * Plugin URI: http://www.tc-mccarthy.com
 * Description: Allows site admins to push uploads to S3
 * Version: 1.0
 * Author: TC McCarthy
 * Author URI: http://www.tc-mccarthy.com
 * License: GPL2
 */
require(dirname(__FILE__) . "/aws/aws-autoloader.php");

use Aws\Common\Aws;

class tcS3 {

//declare variables
    public $aws;
    public $s3Client;
    public $uploads;
    public $options;
    public $uploadDir;

    /**
     * Construct the plugin object
     */
    public function __construct() {
        add_filter('wp_generate_attachment_metadata', array($this, 'push_to_S3'));
        add_action("delete_attachment", array($this, "delete_from_S3"));
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
        register_activation_hook( __FILE__, array($this, 'activate') );
        
        if(isset($_POST["tcS3_option_submit"])){
        	$this->save_s3_settings();
        }

        //setup options
        $this->options = get_option("tcS3_options");

		//set up aws
        $this->aws = Aws::factory($this->build_aws_config());
        $this->s3Client = $this->aws->get('s3');

        $this->uploads = wp_upload_dir();

        preg_match("/\/wp-content(.+)$/", $uploads["basedir"], $matches);
        $this->uploadDir = $matches[1];
    }

    /**
     * Activate the plugin
     */

    public function build_aws_config(){
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


    public function push_to_S3($file_data) {
        include_once(dirname(__FILE__) . "/upload.php");
        return $file_data;
    }

	//function to delete object(s) from S3
    public function delete_from_S3($post_id) {
        $file_data = wp_get_attachment_metadata($post_id);

        if (is_array($file_data)){
	        preg_match("/([0-9]+\/[0-9]+)\/(.+)$/", $file_data["file"], $matches);
            $datePath = $matches[1];
            $keys[] = $file_data["file"];
            foreach($file_data["sizes"] as $size => $data){
                $keys[] = $datePath . "/" . $data["file"];
            }

	        foreach ($keys as $key) {
	        	$file = $this->options["bucket_path"] . "/" . $this->uploadDir . "/" . $key;
	        	if($this->s3Client->doesObjectExist($this->options["bucket"], $file)){
		            $result = $this->s3Client->deleteObject(
			            					array(
						                		'Bucket' => $this->options["bucket"],
						                		'Key' => $file
						            		)
					            		);
		        }
	        }
	    }
    }

    public function add_plugin_page() {
        add_options_page(
                'tcS3 Admin', 'tcS3 Admin configuration', 'manage_options', 'tcS3-admin', array($this, 'create_admin_page')
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page() { ?>
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

    /**
     * Register and add settings
     */
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
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */

    public function sanitize($input) {
        $new_input = array();
        if (isset($input['id_number']))
            $new_input['id_number'] = absint($input['id_number']);

        if (isset($input['title']))
            $new_input['title'] = sanitize_text_field($input['title']);

        return $new_input;
    }

    /**
     * Print the Section text
     */

    public function print_section_info() {
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

    	foreach($regions as $key => $region){
    		if($region["value"] == $this->options["bucket_region"]){
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

    public function s3_min_part_size_callback(){
    	$optionKey = 'min_part_size';
    	$helperText = 'What size chunks should Wordpress break the file up into on S3 upload (in MB)? (Default: 5)';

        printf(
                '<input type="text" id="%s" name="tcS3_option[%s]" value="%s" /><div><small>%s</small></div>', $optionKey, $optionKey, isset($this->options[$optionKey]) ? esc_attr($this->options[$optionKey]) : '', $helperText
        );
    }

    public function s3_delete_local_callback(){
    	$optionKey = 's3_delete_local';
    	$helperText = 'Should uploaded files be removed from your local server?';

        printf(
                '<input type="radio" id="%s" name="tcS3_option[%s]" value="1" %s /> Yes 
                <br /><input type="radio" id="%s" name="tcS3_option[%s]" value="0" %s /> No 
                <div><small>%s</small></div>', 

                $optionKey, $optionKey, ($this->options[$optionKey] == 1) ? "checked" : "", $optionKey, $optionKey, ($this->options[$optionKey] == 0) ? "checked" : "", $helperText
        );
    }

    public function save_s3_settings(){
    	foreach($_POST["tcS3_option"] as $key => $value){
    		if($key == "bucket_path"){
    			$options[$key] = "/" . trim($value, "/");
    			continue;
    		}

    		$options[$key] = sanitize_text_field($value);
    	}

    	update_option("tcS3_options", $options);
    }

    public function array_to_options($arrays){
    	foreach($arrays as $option){
    		$option = (object) $option;
    		$options[] = "<option value = '{$option->value}' {$option->options}>{$option->display}</option>";
    	}
    	return implode("", $options);
    }

    public function dump_to_log($mixed){
    	ob_start();
    	var_dump($mixed);
    	error_log(ob_get_contents());
    	ob_flush();
    }

}

if (is_admin())
    $tcS3 = new tcS3();