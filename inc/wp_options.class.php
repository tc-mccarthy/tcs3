<?php

/* WP options controller */

class tcs3_wp_options
{
    public $fields = [
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
            "custom" => "<div><strong>We recommend you set up IAM keys specifically for tcS3 and limit the scope of the permissions. This will protect your AWS account should these keys fall into unwanted hands.</strong></div>"
          ],
          [
            'name' => 'AWS Key',
            'id' => 'access_key',
            'type' => 'text',
            'desc' => 'We recommend you create an AWS IAM user just for S3 and use its key. If your blog is every exploited, this method will prevent hackers from doing too much damage to your AWS account if they get their hands on your keys.'
          ],
          [
            'name' => 'AWS Secret',
            'id' => 'access_secret',
            'type' => 'text',
            'desc' => 'We recommend you create an AWS IAM user just for S3 and use its secret. If your blog is every exploited, this method will prevent hackers from doing too much damage to your AWS account if they get their hands on your secrets.'
          ],
          [
            "name" => "Or Environment Variables",
            "type" => "heading",
          ],
          [
            'name' => 'AWS Access Key environment variable',
            'id' => 'access_key_variable',
            'type' => 'text',
            'desc' => 'For security reasons, you may prefer to store your AWS key and secret in an environment variable instead of your DB. If that is your preference enter the name of the env variable for your key.'
          ],
          [
            'name' => 'AWS Access Key Secret environment variable',
            'id' => 'access_secret_variable',
            'type' => 'text',
            'desc' => 'For security reasons, you may prefer to store your AWS key and secret in an environment variable instead of your DB. If that is your preference enter the name of the env variable for your secret.'
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
            'desc' => 'The name of your S3 bucket'
          ],
          [
            'name' => 'S3 Bucket path',
            'id' => 'bucket_path',
            'type' => 'text',
            'desc' => 'The path within your S3 bucket where your \'uploads\' directory will be placed'
          ],
          [
            'name' => 'S3 URL',
            'id' => 's3_url',
            'type' => 'text',
            'desc' => 'The URL (including http(s)://) to your S3 bucket and directory where uploads are being stored (e.g. http://mybucket.s3.amazonaws.com/uploads)'
          ],
          [
            'name' => 'S3 Bucket Region',
            'id' => 'bucket_region',
            'type' => 'select',
            'desc' => '',
            "options" => [
              "ap-northeast-1" => "AP North East 1",
              "ap-southeast-1" => "AP South East 1",
              "ap-southeast-2" => "AP South East 2",
              "eu-west-1" => "EU West 1",
              "sa-east-1" => "SA East 1",
              "us-east-1" => "US East 1",
              "us-west-1" => "US West 1",
              "us-west-2" => "US West 3",
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
              'name' => 'S3 Concurrent Connections',
              'id' => 'concurrent_conn',
              'type' => 'text',
              'desc' => 'How many concurrent connections should the server make on upload to S3? (Default: 10)'
            ],
            [
              'name' => 'S3 Minimum Part Size (MB)',
              'id' => 'min_part_size',
              'type' => 'text',
              'desc' => 'What size chunks should Wordpress break the file up into on S3 upload (in MB)? (Default: 5)'
            ],
            [
              'name' => 'Delete local file after upload',
              'id' => 's3_delete_local',
              'type' => 'radio',
              'desc' => 'Should uploaded files be removed from your local server?',
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
            ],
            [
              'type' => 'save'
            ]
          ] //end s3 config fields
        ] //end s3 config tab
    ];

    public function __construct($options)
    {
        $this->options = $options;

        add_action("tf_create_options", [$this, "init"]);
        // add_action("network_admin_menu", [$this, "init"]);
    }

    public function init()
    {
        $titan = TitanFramework::getInstance('tcs3');
        $admin_panel = $titan->createAdminPanel([ 'name' => 'tcS3 Admin', 'id' => 'tcs3-admin', 'parent' => '' ]);

        foreach ($this->fields as $tab) {
            $fields = $tab["fields"];
            unset($tab["fields"]);
            $tab = $admin_panel->createTab($tab);

            foreach ($fields as $field) {
                $tab->createOption($field);
            }
        }
    }
}
