<?php

namespace OCA\Files_ProjectSpaces\Appinfo;

$eventDispatcher = \OC::$server->getEventDispatcher();
$eventDispatcher->addListener
(
		'OCA\Files::loadAdditionalScripts',
		function() 
		{
			\OCP\Util::addScript('files_eosbrowser', 'app');
			\OCP\Util::addScript('files_eosbrowser', 'projectlist');
			\OCP\Util::addStyle('files_eosbrowser', 'styles');
		}
);

\OCA\Files\App::getNavigationManager()->add(
[
	"id" => 'eosbrowser',
	"appname" => 'files_eosbrowser',
	"script" => 'list.php',
	"order" => 30,
	"name" => /*$l->t(*/'EOS Browser'//)
]);
