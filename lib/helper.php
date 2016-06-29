<?php

namespace OCA\Files_ProjectSpaces;

use \OC\Cernbox\Storage\EosUtil;

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
		$storageInfo = self::getStorageInfo($dir);
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
		$owner = EosUtil::getOwner($path);
		return EosUtil::getUserQuota($owner);
	}
}