<?php

/* WP options controller */

class tcs3_wp_options
{
    public function __construct($options)
    {
        $this->options = $options;

        add_action("tf_create_options", [$this, "init"]);
        add_action("tf_pre_save_admin_tcs3_network", [$this, "pre_network_save"], 10, 3);
        add_action("tf_save_admin_tcs3_network", [$this, "post_network_save"], 10, 3);
    }

    public function init()
    {
        $this->setFields();

        //hack for network admin stuff
        if (is_network_admin()) {
            $titan = TitanFramework::getInstance('tcS3_network');

            //create the network admin page
            $admin_panel = $titan->createContainer([ 'type' => 'network-page', 'name' => 'tcS3 Admin', 'id' => 'tcs3-admin', 'parent' => '', "icon" => "dashicons-cloud"]);
        } else {
            $titan = TitanFramework::getInstance('tcS3');
            //create the single instance page
            $admin_panel = $titan->createAdminPanel([ 'name' => 'tcS3 Admin', 'id' => 'tcs3-admin', 'parent' => '', "icon" => "dashicons-cloud" ]);
        }

        foreach ($this->fields as $tab) {
            $fields = $tab["fields"];
            unset($tab["fields"]);
            $tab = $admin_panel->createTab($tab);

            foreach ($fields as $field) {
                $tab->createOption($field);
            }
        }
    }

    public function setFields()
    {
        global $tcS3;

        $this->fields = [
        [  //creds tab
          "name" => "Authentication",
          "desc" => "",
          "id" => "authentication",
          "fields" => [  //creds field
            [
              "name" => "&nbsp;",
              "type" => "custom",
              "custom" => "<div><strong style=\"font-size: 1.5em;\">Leave these fields blank if you are running tcs3 on an EC2 instance and you'd like to use the instance configuration to talk to S3</strong></div>"
            ],
            [
              "name" => "Use AWS IAM Keys",
              "type" => "heading",
            ],
            [
              "name" => "&nbsp;",
              "type" => "custom",
              "custom" => "<div><strong>We recommend you <a href='https://console.aws.amazon.com/iam/home' target='_blank'>set up IAM keys</a> specifically for tcS3 and limit the scope of the permissions. This will protect your AWS account should these keys fall into unwanted hands.</strong></div>"
            ],
            //no default values for access_key and access_secret so we don't unintentionally reveal the values of env vars
            [
              'name' => 'Access key ID',
              'id' => 'access_key',
              'type' => 'text',
              'desc' => 'We recommend you <a href="https://console.aws.amazon.com/iam/home" target="_blank">set up IAM keys</a> just for S3 and use its key. If your blog is every exploited, this method will prevent hackers from doing too much damage to your AWS account if they get their hands on your keys.',
            ],
            [
              'name' => 'Access secret',
              'id' => 'access_secret',
              'type' => 'text',
              'desc' => 'We recommend you <a href="https://console.aws.amazon.com/iam/home" target="_blank">set up IAM keys</a> just for S3 and use its key. If your blog is every exploited, this method will prevent hackers from doing too much damage to your AWS account if they get their hands on your keys.',
            ],
            [
              "name" => "Or Environment Variables",
              "type" => "heading",
            ],
            [
              'name' => 'Access key ID environment variable',
              'id' => 'access_key_variable',
              'type' => 'text',
              'desc' => 'For security reasons, you may prefer to store your AWS key and secret in an environment variable instead of your DB. If that is your preference enter the name of the env variable for your key.',
              "default" => $this->options["access_key_variable"]

            ],
            [
              'name' => 'Access secret environment variable',
              'id' => 'access_secret_variable',
              'type' => 'text',
              'desc' => 'For security reasons, you may prefer to store your AWS key and secret in an environment variable instead of your DB. If that is your preference enter the name of the env variable for your secret.',
              "default" => $this->options["access_secret_variable"]
            ],
            [
              'type' => 'save'
            ]
          ] //end creds fields
        ], //end creds tab
        [  //S3 config tab
          "name" => "S3 configuration",
          "desc" => "",
          "id" => "s3-config",
          "fields" => [  //creds field
            [
              'name' => 'S3 Bucket',
              'id' => 'bucket',
              'type' => 'text',
              'desc' => 'The name of your S3 bucket',
              "default" => $this->options["bucket"]
            ],
            [
              'name' => 'S3 Bucket path',
              'id' => 'bucket_path',
              'type' => 'text',
              'desc' => 'The path within your S3 bucket where your \'uploads\' directory will be placed',
              "default" => $this->options["bucket_path"]
            ],
            [
              'name' => 'S3 URL',
              'id' => 's3_url',
              'type' => 'text',
              'desc' => 'The URL (including http(s)://) to your S3 bucket and directory where uploads are being stored. Unless you\'re doing something special, the format is usually http://%BUCKET_NAME%.s3.amazonaws.com/%BUCKET_PATH%/uploads',
              "default" => $this->options["s3_url"]
            ],
            [
              'name' => 'S3 Bucket Region',
              'id' => 'bucket_region',
              'type' => 'select',
              'desc' => '',
              "default" => $this->options["bucket_region"],
              "options" => [
                  "ap-northeast-1" => "Asia Pacific (Tokyo)",
                  "ap-northeast-2" => "Asia Pacific (Seoul)",
                  "ap-south-1" => "Asia Pacific (Mumbai)",
                  "ap-southeast-1" => "Asia Pacific (Singapore)",
                  "ap-southeast-2" => "Asia Pacific (Sydney)",
                  "ca-central-1" => "Canada (Central)",
                  "cn-north-1" => "China (Beijing)",
                  "eu-central-1" => "EU (Frankfurt)",
                  "eu-west-1" => "EU (Ireland)",
                  "eu-west-2" => "EU (London)",
                  "eu-west-3" => "EU (Paris)",
                  "sa-east-1" => "South America (S‹o Paulo)",
                  "us-east-1" => "US East (N. Virginia)",
                  "us-east-2" => "US East (Ohio)",
                  "us-west-1" => "US West (N. California)",
                  "us-west-2" => "US West (Oregon)"
              ]
            ],
            [
              'type' => 'save'
            ]
          ] //end s3 config fields
        ], //end s3 config tab
        [  //Advanced config tab
          "name" => "Advanced",
          "desc" => "",
          "id" => "advanced",
          "fields" => [  //creds field
              [
                'name' => '&nbsp;',
                'type' => 'custom',
                'custom' => "<div><strong style='font-size: 1.5em;'>These are advanced settings and do not need to be altered for this plugin to do its job. Only change these values if you know what you're doing!</strong></div>"
              ],
              [
                'name' => 'Local path to uploads directory parent',
                'id' => 'local_path',
                'type' => 'text',
                'desc' => 'What is the full path to your server\'s upload directory parent (usually wp-content)?',
                "default" => $this->options["local_path"]
              ],
              [
                'name' => 'URL to uploads directory parent',
                'id' => 'local_url',
                'type' => 'text',
                'desc' => 'What is the absolute URL to your server\'s upload directory parent (usually wp-content)? We use this to figure out how to rewrite your asset URLs.',
                "default" => $this->options["local_url"]
              ],
              [
                'name' => 'S3 Concurrent Connections',
                'id' => 'concurrent_conn',
                'type' => 'text',
                'desc' => 'How many concurrent connections should the server make on upload to S3? (Default: 10)',
                "default" => $this->options["concurrent_conn"]
              ],
              [
                'name' => 'S3 Minimum Part Size (MB)',
                'id' => 'min_part_size',
                'type' => 'text',
                'desc' => 'What size chunks should Wordpress break the file up into on S3 upload (in MB)? (Default: 5)',
                "default" => $this->options["min_part_size"],
              ],
              [
                'name' => 'Delete local file after upload',
                'id' => 's3_delete_local',
                'type' => 'radio',
                'desc' => 'Should uploaded files be removed from your local server?',
                "default" => $this->options["s3_delete_local"],
                "options" => [
                  "1" => "Yes",
                  "0" => "No",
                ]
              ],
              [
                'name' => 'Cache time for S3 objects	',
                'id' => 's3_cache_time',
                'type' => 'text',
                'desc' => 'Sets the Cache-Control header on S3. How long (in seconds) should the cache headers be set for on S3 objects? (This will help keep your S3 bill down and improve page load for returning visitors)',
                "default" => $this->options["s3_cache_time"]
              ],
              [
                'type' => 'save'
              ]
            ] //end s3 config fields
          ], //end s3 config tab
          [  //Advanced config tab
            "name" => "Sync",
            "desc" => "",
            "id" => "sync",
            "fields" => [  //creds field
                [
                  'name' => '&nbsp;',
                  'type' => 'custom',
                  'custom' => sprintf("<div>%d of your %d uploads have been synced to S3</div>", $tcS3->wp_media_->s3_sync_count(), $tcS3->wp_media_->attachment_count())
                ],
                [
                  'name' => 'Mark all synced',
                  'type' => 'ajax-button',
                  'id' => 'mark-all-synced',
                  'desc' => "If you sent your existing images to S3 before activating this plugin you can mark them all as synced here",
                  "action" => "mark_all_synced",
                  "label" => "Update",
                  "class" => "button-primary"
                ],
                [
                  'name' => 'Sync all',
                  'type' => 'ajax-button',
                  'id' => 'sync-all',
                  'desc' => "This will push all of your existing uploads to S3. This process can be resource intensive so if you have a lot of uploads we recommend you upload them a different way",
                  "action" => "sync_all",
                  "label" => "Sync",
                  "class" => "button-primary"
                ]

              ] //end s3 sync fields
            ] //end s3 sync tab
      ];
    }

    public function pre_network_save($container, $activeTab, $options)
    {
    }

    public function post_network_save($container, $activeTab, $options)
    {
        $network_options = get_option("tcS3_network_options");
        update_site_option("tcS3_options", $network_options);
    }
}
