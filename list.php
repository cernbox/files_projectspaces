<?php 

OCP\User::checkLoggedIn();

$tmpl = new OCP\Template('files_projectspaces', 'list', '');

\OCP\Util::addScript('files_projectspaces', 'app');
\OCP\Util::addScript('files_projectspaces', 'projectlist');

\OCP\Util::addScript('files', 'favoritesfilelist');
\OCP\Util::addScript('files', 'tagsplugin');
\OCP\Util::addScript('files', 'favoritesplugin');

$tmpl->printPage();