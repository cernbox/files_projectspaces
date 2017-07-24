<?php

use OCA\Files_EosBrowser\Helper;

OCP\JSON::checkLoggedIn();
\OC::$server->getSession()->close();
$l = \OC::$server->getL10N('files');

$instanceMapper = \OC::$server->getCernBoxInstanceMapper();
$username = \OC::$server->getUserSession()->getUser()->getUID();
$instanceManager = \OC::$server->getCernBoxEosInstanceManager();

try {
	$permissions = \OCP\Constants::PERMISSION_READ;

	$sortAttribute = isset($_GET['sort']) ? (string)$_GET['sort'] : 'name';
	$sortDirection = isset($_GET['sortdirection']) ? ($_GET['sortdirection'] === 'desc') : false;

	$instanceInfos = [];
	$instances = $instanceMapper->getAllMappings();
	foreach($instances as $instance) {
		$path = 'files/  EOS Instance ' . $instance->getInstanceName();
		$config = [
			"name" => $instance->getInstanceName(),
			"mgmurl" => $instance->getInstanceMGMUrl(),
			"prefix" => $instance->getInstanceRootPath(),
		];
		$eosInstance = new \OC\CernBox\Storage\Eos\Instance($instance->getInstanceName(), $config);
		$instanceManager->addInstance($eosInstance);
		$instanceManager->setCurrentInstance($instance->getInstanceName());
		$info = $instanceManager->get($username, $path);
		if($info) {
			$info['custom_perm'] = 1;
			$instanceInfos[] = $info;
		}
	}

	$instanceInfos = Helper::sortFiles($instanceInfos, $sortAttribute, $sortDirection);
	$instanceInfos = \OCA\Files\Helper::populateTags($instanceInfos);
	$instanceInfos = Helper::formatFileInfos($instanceInfos);

	$data['directory'] = '/';
	$data['files'] = $instanceInfos;
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
