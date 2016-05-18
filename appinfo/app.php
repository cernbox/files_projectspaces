<?php

namespace OCA\Files_ProjectSpaces\Appinfo;

$eventDispatcher = \OC::$server->getEventDispatcher();
$eventDispatcher->addListener
(
		'OCA\Files::loadAdditionalScripts',
		function() 
		{
			\OCP\Util::addScript('files_projectspaces', 'app');
			\OCP\Util::addScript('files_projectspaces', 'projectlist');
			\OCP\Util::addStyle('files_projectspaces', 'projectspaces');
		}
);

\OC::$server->getActivityManager()->registerExtension(function() {
	return new \OCA\Files_ProjectSpaces\Activity();
});

\OCA\Files\App::getNavigationManager()->add(
[
	"id" => 'projectspaces',
	"appname" => 'files_projectspaces',
	"script" => 'list.php',
	"order" => 30,
	"name" => /*$l->t(*/'Project Spaces'//)
]);