<?php
$keys = array_keys($_GET);
$guid = array_shift($keys);
$index = array_shift($keys);
$file = Api\File::getByGuid($guid);
if ($file && $file->exists()) {
	$api = null;
	$emptyFile = null;
	switch ($file->type) {
		case FILE_TYPE::AD_IMAGE:
			$api = 'Ad';
			$emptyFile = '/resources/images/no-photo.png';
			break;
	}
	if ($api) {
		$api = Api::factory($api);
		if (!$api->findById($file->parent_id)) {
			$file = new File($emptyFile);
		}
	} else {
		$file = new File($emptyFile);
	}
}


if ($guid) {
	$file = File::getByGuid($guid);
	if ($file && !$file->isEmpty() && $index) {
		$file = $file->getPart($index);
	}
}

if ($file && $file->exists()) {
	Output::factory()
			->output($file);
}
?>