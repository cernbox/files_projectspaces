<?php

namespace OCA\Files_ProjectSpaces;

use OCP\Files\Cache\ICacheEntry;

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
	
	public static function formatFileInfo(ICacheEntry $i) {
		$entry = $i;
	
		$entry['custom_perm'] = $i['custom_perm'];
		$entry['isPreviewAvailable'] = false;
		$entry['path'] = '';
		$entry['type'] = $i->getMimeType() === 'httpd/unix-directory' ? 'dir' : 'file';

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

		//$entry['icon'] = '/core/img/filetypes/folder-shared.svg';//self::determineIcon($i);
		//$entry['name'] = '  project ' . $i->getName();
		//$entry['mimetype'] = $i->getMimeType();
		//$entry['size'] = $i->getMimeType();
		return $entry;
	}
	
	public static function compareFileNames(ICacheEntry $a, ICacheEntry $b) {
		$aType = $a->getMimeType();
		$bType = $b->getMimeType();
		if ($aType === 'httpd/unix-directory' and $bType !== 'httpd/unix-directory') {
			return -1;
		} elseif ($aType !== 'httpd/unix-directory' and $bType === 'httpd/unix-directory') {
			return 1;
		} else {
			return \OCP\Util::naturalSortCompare($a['name'], $b['name']);
		}
	}
	
	public static function compareTimestamp(ICacheEntry $a, ICacheEntry $b) {
		$aTime = $a->getMTime();
		$bTime = $b->getMTime();
		return ($aTime < $bTime) ? -1 : 1;
	}
	
	public static function compareSize(ICacheEntry $a, ICacheEntry $b) {
		$aSize = $a->getSize();
		$bSize = $b->getSize();
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