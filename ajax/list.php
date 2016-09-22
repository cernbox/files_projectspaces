<?php

use OCA\Files_ProjectSpaces\Helper;

OCP\JSON::checkLoggedIn();
\OC::$server->getSession()->close();
$l = \OC::$server->getL10N('files');

$projectMapper = \OC::$server->getCernBoxProjectMapper();
$username = \OC::$server->getUserSession()->getUser()->getUID();
$instanceManager = \OC::$server->getCernBoxEosInstanceManager();

try {
	$permissions = (\OCP\Constants::PERMISSION_ALL & ~\OCP\Constants::PERMISSION_SHARE);

	$sortAttribute = isset($_GET['sort']) ? (string)$_GET['sort'] : 'name';
	$sortDirection = isset($_GET['sortdirection']) ? ($_GET['sortdirection'] === 'desc') : false;

	/*
	 * check here if the data has been cached;
	$data = json_decode(Redis::readFromCacheMap('project_spaces_list', 'all'), true);
	
	$elapsed = time() - (int)$data[0];
	$files = $data[1];
	if($elapsed > 1800 || !$files)
	*/
	{
		// projects can leave just after the project prefix or in a similar
		// layout like the user home directory.
		// Ex: /eos/project/project-zero
		// Ex: /eos/project/a/atlas-project
		// so we need to get information on both levels.
		//$projectPrefix = $currentInstance->getProjectPrefix();

		// first level info => /eos/project/project-zero
		//$files = $instanceManager->getFolderContents($username, $projectPrefix);
		$files = $instanceManager->getFolderContents($username, 'projects');
		if(!$files) {
			throw new \OCP\Files\StorageNotAvailableException();
		}

		if(count($files) > 0 ) {
			// now we are going to ask to the second level projects,
			// prefixed with a letter => /eos/project/a/atlas-project
			$start = ord('a');
			$end = ord('z') + 1;
			//$rootDir = rtrim($projectPrefix, '/') . '/';
			$rootDir = 'projects/';
			for($i = $start; $i < $end; $i++)
			{
				$currentPath = $rootDir . chr($i);
				$filesUnderLetter = $instanceManager->getFolderContents($username, $currentPath);
				if(count($filesUnderLetter) > 0) {
					$files = array_merge($files, $filesUnderLetter);
				}
			}

			// files is an array of cache entries
			// with files like:
			// /eos/project/project-zero and /eos/project/a/atlas-project
			// so the basename call will give us the project name
			foreach($files as $index => $file)
			{
				$projectName= basename($file['eos.file']);

				// Ask Nadir why ?
				// to filter letters ? I think so ...
				if(strlen($projectName) < 2)
				{
					unset($files[$index]);
					continue;
				}

				$projectInfo = $projectMapper->getProjectInfoByProject($projectName);
				if($projectInfo) {
					$projectOwner = $projectInfo->getProjectOwner();
					if($projectOwner && $projectOwner === $username)
					{
						$file['custom_perm'] = '1';
					}
					else if(!$file || !isset($file['eos.sys.acl']))
					{
						$file['custom_perm'] = '0';
					}
					else
					{
						$ownCloudACL = $file->getOwnCloudACL();
						$file['custom_perm'] = isset($ownCloudACL[$username])? '1' : '0';
					}
					$files[$index] = $file;
				} else {
					// custom_perm to '0' ?
				}
			}
		}

		$files = Helper::sortFiles($files, $sortAttribute, $sortDirection);
		$files = \OCA\Files\Helper::populateTags($files);
		$files = Helper::formatFileInfos($files);
		
		// Cache them after all modifications have been performed
		// Redis::writeToCacheMap('project_spaces_list', 'all', json_encode([time(), $files]));
	}
	
	$data['directory'] = '/';
	$data['files'] = $files; 
	$data['permissions'] = $permissions;

	OCP\JSON::success(array('data' => $data));
} catch (\OCP\Files\StorageNotAvailableException $e) {
	\OCP\Util::logException('files', $e);
	OCP\JSON::error(array(
			'data' => array(
					'exception' => '\OCP\Files\StorageNotAvailableException',
					'message' => $l->t('Storage not available')
			)
	));
} catch (\OCP\Files\StorageInvalidException $e) {
	\OCP\Util::logException('files', $e);
	OCP\JSON::error(array(
			'data' => array(
					'exception' => '\OCP\Files\StorageInvalidException',
					'message' => $l->t('Storage invalid')
			)
	));
} catch (\Exception $e) {
	\OCP\Util::logException('files', $e);
	OCP\JSON::error(array(
			'data' => array(
					'exception' => '\Exception',
					'message' => $l->t('Unknown error')
			)
	));
}
