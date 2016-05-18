<?php 

OCP\User::checkLoggedIn();

$tmpl = new OCP\Template('files_projectspaces', 'list', '');

OCP\Util::addScript('files_projectspaces', 'app');
OCP\Util::addScript('files_projectspaces', 'projectlist');

$tmpl->printPage();