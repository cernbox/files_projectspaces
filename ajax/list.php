<?php

use OC\Files\ObjectStore\EosUtil;
use OC\Files\ObjectStore\EosUtilSecure;
use OCA\Files_ProjectSpaces\Helper;

OCP\JSON::checkLoggedIn();
\OC::$server->getSession()->close();
$l = \OC::$server->getL10N('files');

try {
	$permissions = (\OCP\Constants::PERMISSION_ALL & ~\OCP\Constants::PERMISSION_SHARE);

	$sortAttribute = isset($_GET['sort']) ? (string)$_GET['sort'] : 'name';
	$sortDirection = isset($_GET['sortdirection']) ? ($_GET['sortdirection'] === 'desc') : false;

	/*$files = [];
	$fromDB = \OC_DB::prepare("SELECT DISTINCT file_source FROM oc_share WHERE file_target LIKE '/  project %'")->execute()->fetchAll();
	foreach($fromDB as $projectDB)
	{
		$fid = $projectDB['file_source'];
		$eosInfo = \OC\Files\ObjectStore\EosUtil::getFileById($fid);
		$eosInfo['custom_perm'] = EosUtilSecure::hasReadPermissions($eosInfo['sys.acl']) ? '1' : '0';
		$files[] = $eosInfo;
	}*/
	
	$files = EosUtil::getFolderContents(EosUtil::getEosProjectPrefix());
	
	$start = ord('a');
	$end = ord('z') + 1;
	$rootDir = rtrim(EosUtil::getEosProjectPrefix(), '/') . '/';
	for($i = $start; $i < $end; $i++)
	{
		$curPath = $rootDir . chr($i);
		$curDir = EosUtil::ls($curPath);
		if(!$curDir || count($curDir) < 1)
		{
			continue;
		}
		
		$temp = EosUtil::getFolderContents($curPath);
		if($temp)
		{
			$files = array_merge($files, $temp);
		}
	}
	
	
	foreach($files as $index => $file)
	{
		$name = basename($file['eospath']);
		if(strlen($name) < 2)
		{
			unset($files[$index]);
			continue;
		}
		
		$user = EosUtil::getUserForProjectName($name);
		
		if($user && $user == \OC_User::getUser())
		{
			$file['custom_perm'] = '1';
		}
		else if(!$file || !isset($file['sys.acl']) || !EosUtilSecure::hasReadPermissions($file['sys.acl']))
		{
			$file['custom_perm'] = '0';
		}
		else
		{
			$file['custom_perm'] = '1';
		}
		
		$files[$index] = $file;
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
