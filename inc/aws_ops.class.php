<?php

/* Abstraction of S3 ops */

use Aws\S3\MultipartUploader;
use Aws\Exception\MultipartUploadException;

class tcs3_aws_ops
{
    public function __construct($options)
    {
        $this->options = $options;
        $this->s3 = new Aws\S3\S3Client($this->build_aws_config());
        $this->uploadDir = wp_upload_dir();
    }

    public function build_aws_config()
    {
        return [
            'region' => $this->options["bucket_region"],
            "version" => "2006-03-01",
            "credentials" => [
                'key' => $this->options["access_key"],
                'secret' => $this->options["access_secret"]
            ]
        ];
    }

    public function s3_upload($localFile, $remoteFile)
    {
        $success = true;
        $uploader = new MultipartUploader($this->s3, $localFile, [
            'bucket' => $this->options["bucket"],
            'key'    => $this->s3->encodeKey($remoteFile),
            'concurrency' => $this->options["concurrent_conn"],
            'part_size' => $this->options["min_part_size"] * 1024 * 1024,
            'acl' => 'public-read',
            'before_initiate' => function (\Aws\Command $command) {
                // $command is a CreateMultipartUpload operation
                $command['CacheControl'] = 'max-age=' . $this->options["s3_cache_time"];
            }
        ]);

        try {
            $result = $uploader->upload();
        } catch (MultipartUploadException $e) {
            $success = false;
            error_log($e->getMessage());
        }

        return $success;
    }

    public function s3_delete($file)
    {
        if ($this->s3->doesObjectExist($this->options["bucket"], $file)) {
            $result = $this->s3->deleteObject([
                'Bucket' => $this->options["bucket"],
                'Key' => $file
            ]);
        } else {
            $result = false;
        }

        return $result;
    }

    public function build_attachment_key($path)
    {
        $key = str_replace($this->options["local_path"], "", $path);

        $key = $this->options["bucket_path"] . "/" . $key;

        $key = preg_replace(["/[\/]+/", "/^\//"], ["/", ""], trim($key));

        return $key;
    }
}
