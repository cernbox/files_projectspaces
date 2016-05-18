<?php

use OC\Files\ObjectStore\EosUtilSecure;
use OCA\Files_ProjectSpaces\Helper;

OCP\JSON::checkLoggedIn();
\OC::$server->getSession()->close();
$l = \OC::$server->getL10N('files');

try {
	$dir = Helper::getProjectSpacesRootDir();
	$dirInfo = EosUtilSecure::getFileByEosPath($dir);
	if (!$dirInfo || !$dirInfo['eostype'] === 'folder') {
		header("HTTP/1.0 404 Not Found");
		exit();
	}

	$data = array();
	
	$permissions = $dirInfo['permissions'];
	if(\OCP\Util::isSharingDisabledForUser() || (Helper::fileIsShared($dirInfo) && !\OC\Share\Share::isResharingAllowed()))
	{
		$permissions = $permissions & ~\OCP\Constants::PERMISSION_SHARE;
	}

	$sortAttribute = isset($_GET['sort']) ? (string)$_GET['sort'] : 'name';
	$sortDirection = isset($_GET['sortdirection']) ? ($_GET['sortdirection'] === 'desc') : false;

	$files = [];
	$fromDB = \OC_DB::prepare("SELECT DISTINCT file_source FROM oc_share WHERE file_target LIKE '/  project %'")->execute()->fetchAll();
	foreach($fromDB as $projectDB)
	{
		$fid = $projectDB['file_source'];
		$eosInfo = \OC\Files\ObjectStore\EosUtil::getFileById($fid);
		$eosInfo['custom_perm'] = EosUtilSecure::hasReadPermissions($eosInfo['sys.acl']) ? '1' : '0';
		$files[] = $eosInfo;
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
