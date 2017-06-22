<?php

use OCA\Files_ProjectSpaces\Helper;

OCP\JSON::checkLoggedIn();
\OC::$server->getSession()->close();
$l = \OC::$server->getL10N('files');

$projectMapper = \OC::$server->getCernBoxProjectMapper();
$username = \OC::$server->getUserSession()->getUser()->getUID();
$instanceManager = \OC::$server->getCernBoxEosInstanceManager();
$groupManager = \OC::$server->getGroupManager();

try {
	$permissions = \OCP\Constants::PERMISSION_READ;

	$sortAttribute = isset($_GET['sort']) ? (string)$_GET['sort'] : 'name';
	$sortDirection = isset($_GET['sortdirection']) ? ($_GET['sortdirection'] === 'desc') : false;

	$projectInfos = [];
	$projects = $projectMapper->getAllMappings();
	foreach($projects as $project) {
		$path = 'projects/' . $project->getProjectRelativePath();
		$info = $instanceManager->get($username, $path);
		if($info) {
			$info['project_owner'] = $project->getProjectOwner();
			$info['project_name'] = $project->getProjectName();
			$info['project_readers'] = $project->getProjectReaders();
			$info['project_writers'] = $project->getProjectWriters();
			$info['project_admins'] = $project->getProjectAdmins();
			$projectInfos[] = $info;
		}
	}

	foreach($projectInfos as $info) {
		$info['custom_perm'] = 0;
		if($username === $info['project_owner']) {
			$info['custom_perm'] = 1;
		}
		if($groupManager->isInGroup($username, $info['project_readers']) ||
			$groupManager->isInGroup($username, $info['project_writers']) ||
			$groupManager->isInGroup($username, $info['project_admins'])) {
			$info['custom_perm'] = 1;
		}
	}

	$projectInfos = Helper::sortFiles($projectInfos, $sortAttribute, $sortDirection);
	$projectInfos = \OCA\Files\Helper::populateTags($projectInfos);
	$projectInfos = Helper::formatFileInfos($projectInfos);

	$data['directory'] = '/';
	$data['files'] = $projectInfos;
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
