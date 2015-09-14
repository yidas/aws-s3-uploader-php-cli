<?php

/** 
 * ================================================================================
 * AWS S3 Synchronous Batch Upload Tool
 * ================================================================================
 *
 * @date 		2015-09-14
 * @author 		Nick Tsai
 * @filesource 	AwsS3Helper library
 *
 * @param (string) $argv[1]/$_GET['src']: Local source directory
 * @param (string) $argv[2]/$_GET['dir']: 
 *			Local & destinate(S3) common directory
 * @param (string) $argv[3]/$_GET['dst']: Destinate(S3) directory
 * @param (string) $argv[4]/$_GET['start']: Range start
 * @param (string) $argv[5]/$_GET['end']: Range end
 *
 * @example ($_aws['default_dst'] = 'aws/'; )
 *	php upload_sync.php /mnt/movies/ 			(/mnt/movies/ => aws/)
 *	php upload_sync.php /mnt/movies/july 		(/mnt/movies/july => aws/)
 *	php upload_sync.php /mnt/movies/ july 		(/mnt/movies/july => aws/july)
 *	php upload_sync.php /mnt/movies/ 0 dir 		(/mnt/movies/ => dir/)
 *	php upload_sync.php /mnt/movies/ july dir 	(/mnt/movies/july => dir/july)
 *	php upload_sync.php /mnt/movies/ 0 0 5 100 	(start from 5 to 100)
 *
 */

# Setting =====================================================================

$_aws['default_src'] = ''; 				// Default local source directory

$_aws['default_dir'] = ''; 				// Default local & S3 common directory

$_aws['default_dst'] = 'video/yam/'; 	// Default destinate(S3) directory

# ==============================================================================

# New Line Determine
$_line = (isset($argv[0])) ? "\r\n" : "<br/>";
$_wrap = $_line . $_line;

# Argument 1
$_dir['src'] = isset($_GET['src']) ? $_GET['src'] : $_aws['default_src'];
$_dir['src'] = isset($argv[1]) && $argv[1] ? $argv[1] : $_dir['src'] ; // From Shell

# Argument 2
$_dir['dir'] = isset($_GET['dir']) ? $_GET['dir'] : $_aws['default_dir'];
$_dir['dir'] = isset($argv[2]) && $argv[2] ? $argv[2] : $_dir['dir'] ; // From Shell

# Argument 3
$_range_start = isset($_GET['start']) ? $_GET['start'] : 0;
$_range_start = isset($argv[3]) && $argv[3] ? $argv[3] : $_range_start ; // From Shell

# Argument 4
$_range_end = isset($_GET['end']) ? $_GET['end'] : 0;
$_range_end = isset($argv[4]) && $argv[4] ? $argv[4] : $_range_end ; // From Shell

# Argument 5
$_dir['dst'] = isset($_GET['dst']) ? $_GET['dst'] : $_aws['default_dst'];
$_dir['dst'] = isset($argv[5]) && $argv[5] ? $argv[5] : $_dir['dst'] ; // From Shell
// echo $_dir['dst'];exit;

# Directory Check
if (!is_dir($_dir['src']))
	throw new Exception("Local source directory could not found", 400);


# AWS S3 Helper
require dirname(__FILE__).'/libs/AwsS3Helper.class.php';
$s3Config = require dirname(__FILE__).'/config/s3.php';
$awsS3Helper = new AwsS3Helper($s3Config);


# Directory Scan Proccess

$_dir['src'] = formatPath($_dir['src']);	// Format path

$all_dirs = listDirs($_dir['src'] . $_dir['dir']);
$all_dirs[] = $_dir['src'] . $_dir['dir']; 	// Current Directory Add
// print_r($all_dirs);exit;


# Find out all directories with recursion

if ($all_dirs) 
foreach ($all_dirs as $key => $dir) {

	# Get the synchro directory based on $_dir['src']
	$_dir['sync'] = formatPath(str_replace($_dir['src'], '', $dir));
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