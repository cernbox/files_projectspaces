<?php

use OCA\Files_ProjectSpaces\Helper;

OCP\JSON::checkLoggedIn();
\OC::$server->getSession()->close();
$l = \OC::$server->getL10N('files');

$projectMapper = \OC::$server->getCernBoxProjectMapper();
$user = \OC::$server->getUserSession()->getUser();
$username = $user->getUID();
$instanceManager = \OC::$server->getCernBoxEosInstanceManager();
$currentInstance = $instanceManager->getCurrentInstance();

try {
	$data = array();

	//$permissions = (\OCP\Constants::PERMISSION_ALL & ~\OCP\Constants::PERMISSION_SHARE);
	$permissions = \OCP\Constants::PERMISSION_READ;

	$sortAttribute = isset($_GET['sort']) ? (string)$_GET['sort'] : 'name';
	$sortDirection = isset($_GET['sortdirection']) ? ($_GET['sortdirection'] === 'desc') : false;

	$files = [];
	
	//$project = EosUtil::getProjectNameForUser($user);
	$projectInfo = $projectMapper->getProjectInfoByUser($username);
	if($projectInfo)
	{
		$projectName = $projectInfo->getProjectName();
		$entry = $instanceManager->get($username, 'projects/' . $projectName);
		//$eosPath = rtrim($currentInstance->getProjectPrefix(), '/') . '/' . $projectName;
		//$eosInfo = EosUtil::getFileByEosPath($eosPath);
		//$eosInfo['custom_perm'] = '1';
		$entry['custom_perm'] = '1';
		$files[] = $entry;
	}
	else
	{
		$groups = \OC::$server->getGroupManager()->getUserGroupIds($user);
		//$groups = \OC_Group::getUserGroups($user);
		$sqlPlaceHolder = str_repeat('?,', count($groups));
		$groups[] = $username;
		$sqlPlaceHolder .= '?';

		$sql = "SELECT DISTINCT file_source FROM oc_share WHERE file_target LIKE '/  project %' AND share_with IN ($sqlPlaceHolder)";
		$dbRecords = \OC_DB::prepare($sql)->execute($groups)->fetchAll();
		foreach($dbRecords as $record)
		{
			$fileID = $record['file_source'];
			$entry = $instanceManager->getPathById($username, $fileID);
			$entry['custom_perm'] = '1';
			$files[] = $entry;
		}
	}

	$files = Helper::sortFiles($files, $sortAttribute, $sortDirection);

	$files = \OCA\Files\Helper::populateTags($files);
	$data['directory'] = '/';
	$data['files'] = Helper::formatFileInfos($files);
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