<?php

/** 
 * ================================================================================
 * AWS S3 Helper
 * ================================================================================
 *
 * @date 	2015-06-13
 * @author 	Nick Tsai (https://github.com/nickyidas)
 * @package	aws-sdk-php-2.8.8
 */
class awsS3Helper
{
	
	/**
	 * (string) S3 Bucket
	 */
	public $bucket;
	
	/**
	 * (string) S3 Key
	 */
	private $key;

	/**
	 * (string) S3 Secret key
	 */
	private $secret;

	private $lib_path = './aws-sdk-php-2.8.8/vendor/autoload.php';
	private $client;

	/**
	 * Exception Information
	 */
	public $error_code;
	public $error_msg;

	function __construct($configs=array()) {

		require $this->lib_path;

		# Set configurations
		foreach ($configs as $key => $value) {
			
			$this->$key = $value;
		}

		$this->client = $this->awsClinetGet();
	}

	private function awsClinetGet()
	{
		return Aws\S3\S3Client::factory(array(
		    'profile' => '<profile in your aws credentials file>',
		    'credentials' => array(
		        'key'    => $this->key,
		        'secret' => $this->secret)
		    ));
	}

	/** 
	 * ================================================================================
	 * Aws\S3\Model\MultipartUpload\UploadBuilder
	 * ================================================================================
	 */
	public function multipartUpload($file_src, $file_dst)
	{
		$uploader = Aws\S3\Model\MultipartUpload\UploadBuilder::newInstance()
		    ->setClient($this->client)
		    ->setSource($file_src)
		    ->setBucket($this->bucket)
		    ->setKey($file_dst)
		    ->setOption('Metadata', array('Foo' => 'Bar'))
		    ->calculateMd5(false)
		    ->calculatePartMd5(false)
		    // ->setMinPartSize(8*1024*1024)
		    ->setOption('CacheControl', 'max-age=3600')
		    ->setConcurrency(50)
		    ->build();

		// Perform the upload. Abort the upload if something goes wrong
		try {
		    
		    $uploader->upload();    
		    return true;

		} catch (Aws\Common\Exception\MultipartUploadException $e) {
		    
		    $uploader->abort();

		    echo $e->getMessage();
		    return false;   
		}
	}

	/** 
	 * ================================================================================
	 * PutObject with Wrapper Method
	 * ================================================================================
	 */
	public function putObjectWrapper($file_src, $file_dst)
	{

		$this->client->registerStreamWrapper();

		$result = $this->client->putObject(array(
		    'Bucket'     => $this->bucket,
		    'Key'        => $file_dst,
		    'Body'   	 => fopen($file_src, 'r+'), 
		    'Metadata'   => array(
		        'Foo' => 'abc'
		    )
		));

		return $result;
	}

	/** 
	 * ================================================================================
	 * doesBucketExist API
	 * ================================================================================
	 */
	public function object_exist($key)
	{
		 
		$response = $this->client->doesObjectExist($this->bucket, $key);
		 
		// Success? (Boolean, not a CFResponse object)
		return $response;
	}

	/** 
	 * ================================================================================
	 * Upload Proccess
	 * ================================================================================
	 *
	 * @param (string) $file_src: Source file with path
	 * @param (string) $object_path: S3 object upload path without filename
	 * @param (string) $action: Type of Upload Proccess action (Optional)
	 * @param (string) $file_dst_name: Target filename (Optional)
	 * @param (boolean) $overwrite: Upload file even if file exists
	 *
	 */
	public function uploadProccess($file_src, $object_path, $action=NULL, $file_dst_name='', $overwrite=false)
	{

		if (!$this->bucket)	
			throw new Exception("Bucket is empty", 400);

		if (!file_exists($file_src)) 
			throw new Exception("Source File is not existent", 400);
		

		# Max Excution Time
		ini_set('max_execution_time', '0');

		# Newline Express For Browser and Shell
		global $argv;
		$_line = (isset($argv[0])) ? "\r\n" : "<br/>";
		$_wrap = $_line . $_line;

		echo "# AWS S3 Helper Process {$_wrap}";

		# Timer Start
		$_exc_time['start'] = microtime(true); 


		# File Process

		$file['src'] = $file_src;
		$file['size'] = filesize($file['src']);

		$file['filename'] = pathinfo(basename($file['src']),PATHINFO_BASENAME);
		// echo $file['filename'];exit;

		$file['extension'] = pathinfo(basename($file['src']),PATHINFO_EXTENSION);
		// echo $file['extension'];exit;	

		$file_dst_name = ($file_dst_name) ? $file_dst_name .'.'. $file['extension'] : $file['filename'];

		$file['dst'] = $object_path . $file_dst_name;
		// echo $file['dst'];exit;

		echo "{$file['src']} => {$file['dst']} {$_line}";
		
		if ($overwrite == false) {

			if ($this->object_exist($file['dst'])) {
				
				$this->error_code = 200;	// Success because the object exist
				$this->error_msg = "S3 Object Key already exists";

				echo "{$this->error_msg} {$_line}({$file['dst']})";
				echo "{$_wrap}";

				return false;
			}
		}

		# Actions
		switch ($action) {

			/** 
			 * ================================================================================
			 * AWS Wrapper Upload
			 * ================================================================================
			 */
			case 'wrapper':	

				$result = $this->putObjectWrapper($file['src'], $file['dst']);

				if ($result) {
					
					echo 'Upload Success!';

				} else {

					echo 'Upload Failed';
				}

				break;

			/** 
			 * ================================================================================
			 * AWS Multipart Upload
			 * ================================================================================
			 */
			case 'multipart':

				$result = $this->multipartUpload($file['src'], $file['dst']);

				if ($result) {
					
					echo 'Upload Success!';

				} else {

					echo 'Upload Failed';
				}

				break;
			
			/** 
			 * ================================================================================
			 * Default
			 * ================================================================================
			 */
			default:
				
				echo 'Please Give the Action';
				echo "{$_wrap}";
				exit;

				break;
		}

		# Error Handler
		if (!$result) {

			$this->error_code = 500;	// Success because the object exist
			$this->error_msg = "Upload Failed";
		}


		# Timer End
		$_exc_time['end'] = microtime(true);
		$_exc_time['difference'] = round( ($_exc_time['end'] - $_exc_time['start']) , 2);
		$_exc_time['bandwidth'] = ($_exc_time['difference']) ? $this->formatSizeUnits( $file['size'] / $_exc_time['difference'] ) : 0;
		$_exc_time['filesize-format'] = $this->formatSizeUnits( $file['size'] );

		# Template
		echo "{$_wrap}";
		echo "# Excution Info =============={$_line}";
		echo "Action: {$action}{$_line}";
		echo "Start Date: ".date("Y-m-d H:i:s", $_exc_time['start'])."{$_line}";
		echo "Start Time: {$_exc_time['start']}{$_line}";
		echo "End Time: {$_exc_time['end']}{$_line}";
		echo "Run Time: {$_exc_time['difference']} sec {$_line}";
		echo "# File Info =================={$_line}";
		echo "File Source: {$file['src']} {$_line}";
		echo "File Destination: {$file['dst']} {$_line}";
		echo "Bucket: {$this->bucket} {$_line}";
		echo "# Transfer Info =============={$_line}";
		echo "File Size: {$_exc_time['filesize-format']} {$_line}";
		echo "Bandwith: {$_exc_time['bandwidth']} MBps in average {$_line}";
		echo "{$_line}";

		return $result;
	}

	private function formatSizeUnits($bytes)
	{
    
        if ($bytes >= 1073741824) {

            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        }
        elseif ($bytes >= 1048576) {

            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        }
        elseif ($bytes >= 1024) {

            $bytes = number_format($bytes / 1024, 2) . ' KB';
        }
        elseif ($bytes > 1) {

            $bytes = $bytes . ' bytes';
        }
        elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        }
        else {

            $bytes = '0 bytes';
        }

        return $bytes;

	}

}