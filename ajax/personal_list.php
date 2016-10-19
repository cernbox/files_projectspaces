<?php

use OC\Files\ObjectStore\EosUtil;
use OCA\Files_ProjectSpaces\Helper;

OCP\JSON::checkLoggedIn();
\OC::$server->getSession()->close();
$l = \OC::$server->getL10N('files');

try {
	$data = array();

	//$permissions = (\OCP\Constants::PERMISSION_ALL & ~\OCP\Constants::PERMISSION_SHARE);
	$permissions = \OCP\Constants::PERMISSION_READ;
	$sortAttribute = isset($_GET['sort']) ? (string)$_GET['sort'] : 'name';
	$sortDirection = isset($_GET['sortdirection']) ? ($_GET['sortdirection'] === 'desc') : false;

	$files = [];
	
	$user = \OC_User::getUser();
	$project = EosUtil::getProjectNameForUser($user);
	if($project)
	{
		$eosPath = rtrim(EosUtil::getEosProjectPrefix(), '/') . '/' . $project;
		$eosInfo = EosUtil::getFileByEosPath($eosPath);
		$eosInfo['custom_perm'] = '1';
		$files[] = $eosInfo;
	}
	else
	{
		$groups = \OC_Group::getUserGroups($user);
		$sqlPlaceHolder = str_repeat('?,', count($groups));	
		$groups[] = $user;
		$sqlPlaceHolder .= '?';
	
		$sql = "SELECT DISTINCT file_source FROM oc_share WHERE file_target LIKE '/  project %' AND share_with IN ($sqlPlaceHolder)";
		$fromDB = \OC_DB::prepare($sql)->execute($groups)->fetchAll();
		foreach($fromDB as $projectDB)
		{
			$fid = $projectDB['file_source'];
			$eosInfo = \OC\Files\ObjectStore\EosUtil::getFileById($fid);
			$eosInfo['custom_perm'] = '1';
			$files[] = $eosInfo;
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
