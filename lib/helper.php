<?php

namespace OCA\Files_ProjectSpaces;

use \OC\Files\ObjectStore\EosUtil;

class Helper
{
	public static function formatFileInfos(array $files)
	{
		$data = [];
		foreach($files as $file)
		{
			$data[] = self::formatFileInfo($file);
		}
		
		return $data;
	}
	
	private static function readProp($array, $name, $defaultVaule)
	{
		if(isset($array[$name]))
		{
			return $array[$name];
		}
		
		return $defaultVaule;
	}
	
	public static function formatFileInfo(array $i) {
		$entry = array();
	
		$entry['eospath'] = self::readProp($i, 'eospath', 'unknown');;
		$entry['custom_perm'] = $i['custom_perm'];
		
		$entry['id'] = self::readProp($i, 'fileid', '0');
		$entry['parentId'] = self::readProp($i, 'parent', '0');
		$entry['date'] = \OCP\Util::formatDate($i['mtime']);
		$entry['mtime'] = self::readProp($i, 'mtime', 0) * 1000;
		// only pick out the needed attributes
		$entry['icon'] = '/core/img/filetypes/folder-shared.svg';//self::determineIcon($i);
		$entry['isPreviewAvailable'] = false;
		$entry['name'] = '  project ' . self::readProp($i, 'name', 'unknown');
		$entry['path'] = '';
		$entry['permissions'] = self::readProp($i, 'permissions', '0');;
		$entry['mimetype'] = 'dir-shared';//$i['mimetype'];
		$entry['size'] = self::readProp($i, 'size', '0');
		$entry['type'] = self::readProp($i, 'eostype', 'file') == 'folder' ? 'dir' : 'file';
		$entry['etag'] = self::readProp($i, 'etag', '');;
		if (isset($i['tags'])) {
			$entry['tags'] = $i['tags'];
		}
		if (isset($i['displayname_owner'])) {
			$entry['shareOwner'] = $i['displayname_owner'];
		}
		if (isset($i['is_share_mount_point'])) {
			$entry['isShareMountPoint'] = $i['is_share_mount_point'];
		}
		
		
		if (isset($i['extraData'])) {
			$entry['extraData'] = $i['extraData'];
		}
	
		/** CERNBOX FAVORITES PATCH */
		// HUGO allow eos attrs to be passed to the web frontend
		if(isset($i['cboxid']))
		{
			$entry['cboxid'] = $i['cboxid'];
		}
	
		return $entry;
	}
	
	public static function determineIcon($file) 
	{
		$icon = \OC_Helper::mimetypeIcon($file['mimetype']);
		return substr($icon, 0, -3) . 'svg';
	}
	
	public static function compareFileNames(array $a, array $b) {
		$aType = $a['eostype'];
		$bType = $b['eostype'];
		if ($aType === 'folder' and $bType !== 'folder') {
			return -1;
		} elseif ($aType !== 'folder' and $bType === 'folder') {
			return 1;
		} else {
			return \OCP\Util::naturalSortCompare($a['name'], $b['name']);
		}
	}
	
	public static function compareTimestamp(array $a, array $b) {
		$aTime = $a['mtime'];
		$bTime = $b['mtime'];
		return ($aTime < $bTime) ? -1 : 1;
	}
	
	public static function compareSize(array $a, array $b) {
		$aSize = $a['size'];
		$bSize = $b['size'];
		return ($aSize < $bSize) ? -1 : 1;
	}
	
	public static function sortFiles($files, $sortAttribute = 'name', $sortDescending = false)
	{
		$sortFunc = 'compareFileNames';
		if ($sortAttribute === 'mtime') 
		{
			$sortFunc = 'compareTimestamp';
		} 
		else if ($sortAttribute === 'size') 
		{
			$sortFunc = 'compareSize';
		}
		
		usort($files, array('\OCA\Files_ProjectSpaces\Helper', $sortFunc));
		if ($sortDescending) {
			$files = array_reverse($files);
		}
		return $files;
	}
	
	public static function fileIsShared(array $file)
	{
		if(isset($file['storage']))
		{
			$storage = $file['storage'];
			if (!is_null($storage)) {
				$sid = explode(':', $storage);
				return ($sid[0] === 'shared');
			}
		}
							
		return false;
	}
	
	public static function getProjectSpacesRootDir()
	{
		return rtrim(\OC::$server->getSystemConfig()->getValue('eos_project_prefix', '/eos/project/'), '/');
	}
	
	public static function buildFileStorageStatistics($dir) 
	{
		// information about storage capacities
		$storageInfo = \OC_Helper::getStorageInfo($dir);
		$l = new \OC_L10N('files');
		$maxUploadFileSize = \OCP\Util::maxUploadFilesize($dir, $storageInfo['free']);
		$maxHumanFileSize = \OCP\Util::humanFileSize($maxUploadFileSize);
		$maxHumanFileSize = $l->t('Upload (max. %s)', array($maxHumanFileSize));
		
		return [
				'uploadMaxFilesize' => $maxUploadFileSize,
				'maxHumanFilesize'  => $maxHumanFileSize,
				'freeSpace' => $storageInfo['free'],
				'usedSpacePercent'  => (int)$storageInfo['relative'],
				'owner' => $storageInfo['owner'],
				'ownerDisplayName' => $storageInfo['ownerDisplayName'],
		];
	}
	
	private static function getStorageInfo($path, $rootInfo = null) 
	{
		// return storage info without adding mount points
		$includeExtStorage = \OC_Config::getValue('quota_include_external_storage', false);
	
		if (!$rootInfo) {
			$rootInfo = \OC\Files\ObjectStore\EosUtil::getFileByEosPath($path);
			//$rootInfo = \OC\Files\Filesystem::getFileInfo($path, false);
		}

		$used = isset($rootInfo['size'])? $rootInfo['size'] : 0;
		if ($used < 0) {
			$used = 0;
		}
		$quota = 0;
		$storage = $rootInfo->getStorage();
		if ($includeExtStorage && $storage->instanceOfStorage('\OC\Files\Storage\Shared')) {
			$includeExtStorage = false;
		}
		if ($includeExtStorage) {
			$quota = OC_Util::getUserQuota(\OCP\User::getUser());
			if ($quota !== \OCP\Files\FileInfo::SPACE_UNLIMITED) {
				// always get free space / total space from root + mount points
				return self::getGlobalStorageInfo();
			}
		}
	
		// TODO: need a better way to get total space from storage
		if ($storage->instanceOfStorage('\OC\Files\Storage\Wrapper\Quota')) {
			/** @var \OC\Files\Storage\Wrapper\Quota $storage */
			$quota = $storage->getQuota();
		}
		$free = $storage->free_space('');
		if ($free >= 0) {
			$total = $free + $used;
		} else {
			$total = $free; //either unknown or unlimited
		}
		if ($total > 0) {
			if ($quota > 0 && $total > $quota) {
				$total = $quota;
			}
			// prevent division by zero or error codes (negative values)
			$relative = round(($used / $total) * 10000) / 100;
		} else {
			$relative = 0;
		}
	
		$ownerId = $storage->getOwner($path);
		$ownerDisplayName = '';
		$owner = \OC::$server->getUserManager()->get($ownerId);
		if($owner) {
			$ownerDisplayName = $owner->getDisplayName();
		}
	
		return [
				'free' => $free,
				'used' => $used,
				'total' => $total,
				'relative' => $relative,
				'owner' => $ownerId,
				'ownerDisplayName' => $ownerDisplayName,
		];
	}
}