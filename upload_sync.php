<?php

/** 
 * ================================================================================
 * AWS S3 Synchronous Batch Upload Tool
 * ================================================================================
 *
 * @date 	2015-06-01
 *
 * @param (string) $argv[1]/$_GET['base']: Base Source Directory
 * @param (string) $argv[2]/$_GET['src']: 
 *					Source Directory refered to S3 Object (Optional)
 * @param (string) $argv[3]/$_GET['dst']: Base Destinate Directory (Optional)
 *
 */

# Setting =====================================================================

$_aws['bucket'] = 'mybucket'; 	// S3 Bucket

$_aws['object_path'] = 'nick_tsai/'; 		// S3 Destination Object Path

# ==============================================================================

# Directory

$_dir['base'] = isset($_GET['base']) ? $_GET['base'] : NULL;
$_dir['base'] = isset($argv[1]) ? $argv[1] : $_dir['base'] ; // From Shell

$_dir['src'] = isset($_GET['src']) ? $_GET['src'] : NULL;
$_dir['src'] = isset($argv[2]) ? $argv[2] : $_dir['src'] ; // From Shell

$_dir['dst'] = isset($_GET['dst']) ? $_GET['dst'] : $_aws['object_path'];
$_dir['dst'] = isset($argv[3]) ? $argv[3] : $_dir['dst'] ; // From Shell
// echo $_dir['dst'];exit;

# Directory Check
if (!is_dir($_dir['base']))
	throw new Exception("Base Source Directory could not found", 400);


# AWS S3 Helper
require './awss3helper.class.php';
$awsS3Helper = new awsS3Helper;

$awsS3Helper->bucket = $_aws['bucket'];


# Directory Scan Proccess

$all_dirs = listDirs($_dir['base'] . $_dir['src']);
$all_dirs[] = $_dir['base'] . $_dir['src']; 	// Current Directory Add
// print_r($all_dirs);exit;


# Find out all directories with recursion

if ($all_dirs) 
foreach ($all_dirs as $key => $dir) {
	
	$file_list = glob($dir."/*.*");
	// print_r($file_list);exit;


	# Find out all files of each directory

	if ($file_list)
	foreach ($file_list as $key => $file) {

		$object_path = "{$_dir['dst']}/{$_dir['src']}/";	// Combinated with object prefix and current path

		// Determine of S3_Object_is_exist here in the future.

		$awsS3Helper->uploadProccess($file, $object_path, 'multipart');
	}
}


/** 
 * ================================================================================
 * Dir Recursive List Function
 * ================================================================================
 */
function listDirs($dir) {

	static $all_dirs = array();

	$dirs = glob($dir . '/*', GLOB_ONLYDIR);

	if (count($dirs) > 0) {

		foreach ($dirs as $d) $all_dirs[] = $d;
	}

	foreach ($dirs as $dir) listdirs($dir);

	return $all_dirs;
}

?>