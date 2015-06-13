<?php

/**
 * FTP Sync to S3 Proccess
 *
 * This proccess uses to scan files in the FTP directory, 
 * if the file is coping or moving into the directory, it will be checked continuously.
 * if the file is unchanged and exceed the period time, it will be uploaded to S3 than be removed. 
 *
 * @date 	2015-06-13
 * @author 	Nick Tsai(yam-RD2)
 */

date_default_timezone_set('Asia/Taipei');

error_reporting(E_ALL);
ini_set("display_errors", 1);

define('SYS_NAME', 'ftp_sync'); 				// FTP directroy path
define('ROOT_PATH', dirname(__FILE__).'/');		// Root Path
define('FTP_PATH', '/home/iptvftp/'); 			// FTP directroy path
define('CHECK_LIST_PATH', ROOT_PATH.SYS_NAME.'_checklist.data');	// File path for save last list data
define('RUNTIME_PATH', ROOT_PATH.SYS_NAME.'_runtime.data');			// File path for script runtime check
define('FILESIZE_PERIOD_MINUTES', 1);			// Minutes of file size unchanged and exceeded
define('FILENAME_PREFIX', '');					// File name prefix after upload
define('DEBUG', true);							// Debug mode (Information)
define('NOW', time());							// Current Time


/**
 * Data files check for this proccess
 */
if (!file_exists(CHECK_LIST_PATH)) {

	$resource = fopen(CHECK_LIST_PATH, "w");
	fclose($resource);
}

if (!file_exists(RUNTIME_PATH)) {
	
	$resource = fopen(RUNTIME_PATH, "w");
	fclose($resource);
}


# RunTime check (Assure single proccess only)

$runtime = unserialize( file_get_contents(RUNTIME_PATH) );
// print_r($runtime);exit;

# Check if is running:
if ($runtime && $runtime['is_running'])	{

	echo "Exit because another proccess is running\n";
	exit;
}

# Lock runtime
$runtime = [
	'is_running' => true,
	'last_time' => NOW,
	];

file_put_contents(RUNTIME_PATH, serialize($runtime));



# List FTP directories:
// $dirList = listdirs(FTP_PATH);
// print_r($dirList);


# List files in FTP directory:
$fileList = glob(FTP_PATH."*.*");
// print_r($fileList);


# Get existent check list:

$checkList = unserialize( file_get_contents(CHECK_LIST_PATH) );
// print_r($checkList);exit;


# Get a new list of files' information:

$newList = [];

foreach ($fileList as $key => $file) {
	
	$fileName = pathinfo($file, PATHINFO_BASENAME);

	$newList[$fileName] = [
		'path' => $file,
		'size' => filesize($file),
		'size_locked_at' => NULL,
		'updated_at' => NOW,
		];	

	# Check file data of CHECK_LIST_PATH
	if (!$checkList) 
		break;

	# Matching between newList and checkList
	if (array_key_exists($fileName, $checkList)) {

		$newFile = &$newList[$fileName];

		$lastFile = &$checkList[$fileName];
		
		# Check if sizes are equal
		if ($newFile['size'] == $lastFile['size']) {

			# Check if is first size matched
			if (!$lastFile['size_locked_at']) {
				
				$newFile['size_locked_at'] = NOW;
				continue;

			} else {

				# Check if is the file size unchanged and exceeded FILESIZE_PERIOD_MINUTES
				if ( (NOW - $lastFile['size_locked_at']) > (FILESIZE_PERIOD_MINUTES*60) ) {
 
					$awsS3Helper = getS3Helper();

					# Upload configuration
					$time = date("His" ,NOW);
					$floderName = date("Ymd", NOW);		// S3 object prefix floder name		
					$fileName = "{$time}_".FILENAME_PREFIX."{$fileName}";	// S3 object name
					
					$result = $awsS3Helper->uploadProccess($newFile['path'], "vod_temp/{$floderName}/", 'multipart', $fileName);

					if (!$result && $awsS3Helper->error_code==200) {

						echo "S3 object already exist {$newFile['path']}\n";
					} 
					elseif (!$result) {

						echo "S3 uploaded failed on {$newFile['path']}\n";
						break;
					}


					# Remove the local file

					$result = unlink($newFile['path']); 

					if (!$result) {
						
						echo "Remove local file failed on {$newFile['path']}\n";
						break;
					}		

					echo "Success proccess on {$newFile['path']}\n";

					unset($newList[$fileName]);

				} else {

					$newFile['size_locked_at'] = $lastFile['size_locked_at'];
				}
			}
			
			
		}
	}
}


# Debug Mode
if (DEBUG) {

	echo "Current Time: ".NOW." (".date("Y-m-d H:i:s", NOW).")\n";

	echo "Lock Time: ".(NOW-(FILESIZE_PERIOD_MINUTES*60))."\n\n";

	echo "\n# Check List:\n"; print_r($checkList);

	echo "\n# New List:\n"; print_r($newList);
}


# Save current file list data 

file_put_contents(CHECK_LIST_PATH, serialize($newList));


# Unlock or init runtime data

$runtime['is_running'] = false;

file_put_contents(RUNTIME_PATH, serialize($runtime));


/**
 * S3 Helper initialization
 */
function getS3Helper($value='')
{
	static $awsS3Helper;

	if (!$awsS3Helper) {
		
		require ROOT_PATH.'awss3helper.class.php';

		$configs = [
			'bucket' => '',
			'key' => '',
			'secret' => '',
			'lib_path' => ROOT_PATH.'aws-sdk-php-2.8.8/vendor/autoload.php',
			];

		$awsS3Helper = new awsS3Helper($configs);
	}

	return $awsS3Helper;
}

/** 
 * Dir Recursive List Function
 */
function listDirs($dir) {

	static $all_dirs = array();
	
	# Format input path
	$dir = (substr($dir, -1) == '/') ? substr($dir, 0, -1) : $dir ;
	
	$dirs = glob($dir . '/*', GLOB_ONLYDIR);

	if (count($dirs) > 0) {

		foreach ($dirs as $d) $all_dirs[] = $d;
	}

	foreach ($dirs as $dir) listdirs($dir);

	return $all_dirs;
}


?>