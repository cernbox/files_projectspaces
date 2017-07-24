<?php

namespace OCA\Files_EosBrowser;

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

		return $entry;
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
		
		usort($files, array('\OCA\Files_EosBrowser\Helper', $sortFunc));
		if ($sortDescending) {
			$files = array_reverse($files);
		}
		return $files;
	}
}