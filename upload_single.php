<?php

/** 
 * ================================================================================
 * AWS S3 Singel File Upload Interface
 * ================================================================================
 *
 * @date 		2015-09-14
 * @author 		Nick Tsai
 * @filesource 	AwsS3Helper library
 *
 * @param (string) $argv[1]/$_GET['src']: Local source directory
 * @param (string) $argv[2]/$_GET['dst']: Destinate(S3) directory
 * @param (string) $argv[3]/$_GET['dst_name']: Destinate filename
 * @param (string) $argv[4]/$_GET['action']: Action
 *
 */

# Setting =====================================================================

$_file['default_src'] = '211M.mp4'; 		// Default loacl file with path

$_file['default_dst'] = ''; 				// Default destinate(S3) directory

$_file['default_dst_name'] = ''; 			// Default destinate filename

# ==============================================================================

# Action

$_file['src'] = isset($_GET['src']) ? $_GET['src'] : $_file['default_src'];
$_file['src'] = isset($argv[1]) && $argv[1] ? $argv[1] : $_file['src'];

$_file['dst'] = isset($_GET['dst']) ? $_GET['dst'] : $_file['default_dst'];
$_file['dst'] = isset($argv[2]) && $argv[2] ? $argv[2] : $_file['dst'];

$_file['dst_name'] = isset($_GET['dst_name']) ? $_GET['dst_name'] : $_file['default_dst_name'];
$_file['dst_name'] = isset($argv[3]) && $argv[3] ? $argv[3] : $_file['dst_name'];

$_action = isset($_GET['action']) ? $_GET['action'] : NULL;
$_action = isset($argv[4]) ? $argv[4] : $_action ; // Script

if (!file_exists($_file['src'])) 
	throw new Exception("Source File doesn't exist", 400);

# AWS S3 Helper
require dirname(__FILE__).'/libs/AwsS3Helper.class.php';
$s3Config = require dirname(__FILE__).'/config/s3.php';
$awsS3Helper = new AwsS3Helper($s3Config);


$awsS3Helper->uploadProccess($_file['src'], $_file['dst'], $_action, $_file['dst_name']);

?>