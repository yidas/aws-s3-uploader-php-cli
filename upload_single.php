<?php

/** 
 * ================================================================================
 * AWS S3 Singel File Upload Interface
 * ================================================================================
 *
 * @param (string) $argv[1]/$_GET['action']: Action
 * @param (string) $argv[2]/$_GET['filename']: Destinate Filename
 *
 */

# Setting =====================================================================

$_aws['bucket'] = 'mybucket'; 	// S3 Bucket

$_aws['object_path'] = 'nick_tsai/'; 		// S3 Destination Object Path

$_file['src'] = '211M.mp4'; // Loacl file

# ==============================================================================

# Action

$_action = isset($_GET['action']) ? $_GET['action'] : NULL;
$_action = isset($argv[1]) ? $argv[1] : $_action ; // Script

$_file['dst_name'] = (isset($_GET['filename'])) ? $_GET['filename'] : NULL;
$_file['dst_name'] = (isset($argv[2])) ? $argv[2] : $_file['dst_name'];

require './awss3helper.class.php';
$awsS3Helper = new awsS3Helper;

$awsS3Helper->bucket = $_aws['bucket'];

$awsS3Helper->uploadProccess($_file['src'], $_aws['object_path'], $_action, $_file['dst_name']);

?>