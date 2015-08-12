<?php

/** 
 * ================================================================================
 * AWS S3 Synchronous Batch Upload Tool
 * ================================================================================
 *
 * @date 	2015-08-12
 * @author 	Nick Tsai
 *
 * @param (string) $argv[1]/$_GET['base']: Base Source Directory
 * @param (string) $argv[2]/$_GET['src']: 
 *					Source Directory refered to S3 Object (Optional)
 * @param (string) $argv[3]/$_GET['dst']: Base Destinate Directory (Optional)
 *
 * @example 
 *	php upload_sync.php /mnt/movies/ 			(/mnt/movies/ => /aws/)
 *	php upload_sync.php /mnt/movies/july 		(/mnt/movies/july => /aws/)
 *	php upload_sync.php /mnt/movies/ july 		(/mnt/movies/july => /aws/july)
 *	php upload_sync.php /mnt/movies/ july 0 100 (start from 0 to 100)
 *	php upload_sync.php /mnt/movies/ 0 0 100 	(/mnt/movies/ => /aws/)
 *	php upload_sync.php /mnt/movies/ 0 0 0 dir 	(/mnt/movies/ => dir/)
 *
 */

# Setting =====================================================================

$_aws['bucket'] = 'mybucket'; 					// S3 Bucket

$_aws['default_object_path'] = 'nick_test/'; 	// S3 Destination Default Path

# ==============================================================================

# New Line Determine
$_line = (isset($argv[0])) ? "\r\n" : "<br/>";
$_wrap = $_line . $_line;

# Argument 1
$_dir['base'] = isset($_GET['base']) ? $_GET['base'] : NULL;
$_dir['base'] = isset($argv[1]) && $argv[1] ? $argv[1] : $_dir['base'] ; // From Shell

# Argument 2
$_dir['src'] = isset($_GET['src']) ? $_GET['src'] : NULL;
$_dir['src'] = isset($argv[2]) && $argv[2] ? $argv[2] : $_dir['src'] ; // From Shell

# Argument 3
$_range_start = isset($_GET['start']) ? $_GET['start'] : 0;
$_range_start = isset($argv[3]) && $argv[3] ? $argv[3] : $_range_start ; // From Shell

# Argument 4
$_range_end = isset($_GET['end']) ? $_GET['end'] : 0;
$_range_end = isset($argv[4]) && $argv[4] ? $argv[4] : $_range_end ; // From Shell

# Argument 5
$_dir['dst'] = isset($_GET['dst']) ? $_GET['dst'] : $_aws['default_object_path'];
$_dir['dst'] = isset($argv[5]) && $argv[5] ? $argv[5] : $_dir['dst'] ; // From Shell
// echo $_dir['dst'];exit;

# Directory Check
if (!is_dir($_dir['base']))
	throw new Exception("Base Source Directory could not found", 400);


# AWS S3 Helper
require './awss3helper.class.php';
$awsS3Helper = new awsS3Helper;

$awsS3Helper->bucket = $_aws['bucket'];


# Directory Scan Proccess

$_dir['base'] = formatPath($_dir['base']);	// Format path

$all_dirs = listDirs($_dir['base'] . $_dir['src']);
$all_dirs[] = $_dir['base'] . $_dir['src']; 	// Current Directory Add
// print_r($all_dirs);exit;


# Find out all directories with recursion

if ($all_dirs) 
foreach ($all_dirs as $key => $dir) {

	# Get the synchro directory based on $_dir['base']
	$_dir['sync'] = formatPath(str_replace($_dir['base'], '', $dir));
	// echo $_dir['sync'];exit;

	# Format destinate
	$_dir['dst'] = formatPath($_dir['dst']);

	
	$file_list = glob($dir."/*.*");
	// print_r($file_list);echo count($file_list);exit;

	# Range Option (appling when $all_dirs count is one)
	$range['start'] = $_range_start;
	$range['end'] 	= $_range_end;

	$range['start'] = round($range['start']);
	$range['end'] = round($range['end']);
	$range['end'] = ( min($range['end'], count($file_list)) > $range['start']) ? $range['end'] : count($file_list) - 1;
	echo "Runing range started at {$range['start']} to {$range['end']} {$_wrap}";


	# Find out all files of each directory

	if ($file_list && ($range['end'] > $range['start']))
	for ($i = $range['start']; $i <= $range['end']; $i++) { 

		$object_path = "{$_dir['dst']}{$_dir['sync']}";	// Combinated with object prefix and current path
		// echo $_dir['sync'];exit;

		echo "[Sync] Proccess Key:{$i} of {$range['end']} ================== {$_wrap}";

		$awsS3Helper->uploadProccess($file_list[$i], $object_path, 'multipart');

		echo "{$_wrap}";
	}
}


/** 
 * ================================================================================
 * Dir Recursive List Function
 * ================================================================================
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

/** 
 * ================================================================================
 * Format File Path
 * ================================================================================
 */
function formatPath($path)
{
	if ($path && substr($path, -1) != '/') {
		$path .= '/';
	}

	return $path;
}
?>