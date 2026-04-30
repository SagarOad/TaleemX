<?php
$config['courseprovider'] = array(
    'youtube' => lang('youtube'),	
	'vimeo' => lang('vimeo'),  
	'html5' => lang('html5'),
	's3_bucket' => lang('s3_bucket')   
	);

// Simplified provider list used by the Course Preview URL selector and by
// the Lesson Video Provider selector on the online course create/edit
// screens. The full `courseprovider` list above is only kept for legacy
// data compatibility.
$config['course_preview_providers'] = array(
	'youtube' => lang('youtube'),
	'file'    => lang('file'),
	);

$config['lesson_type'] = array(
    'video' => lang('video'),
	'pdf' => lang('pdf'),
	'text' => lang('text'),
	'document' => lang('document'),
	);

$config['front_side_visibility'] = array(
    'yes' => lang('yes'),
	'no' => lang('no'),
	);

?>