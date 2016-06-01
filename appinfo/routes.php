<?php 

namespace OCA\Files_ProjectSpaces\AppInfo;

$this->create('files_projectspaces_ajax_list', 'ajax/list.php')
->actionInclude('files_projectspaces/ajax/list.php');

$this->create('files_projectspaces_ajax_list_personal', 'ajax/personal_list.php')
->actionInclude('files_projectspaces/ajax/personal_list.php');