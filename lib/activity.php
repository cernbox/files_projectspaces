<?php

namespace OCA\Files_ProjectSpaces;

use OCA\Comments\Activity\Extension;
use OCP\Activity\IExtension;

class Activity extends Extension
{
	const TYPE_PROJECTSPACES = 'projectspaces';
	
	public function getNotificationTypes($languageCode)
	{
		return false;
	}
	
	public function getDefaultTypes($method)
	{
		return [self::TYPE_PROJECTSPACES];
	}
	
	public function getTypeIcon($type)
	{
		//if($type === self::TYPE_PROJECTSPACES)
		//{
			return 'icon-external';
		//}
		
		return false;
	}
	
	public function translate($app, $text, $params, $stripPath, $highlightParams, $languageCode)
	{
		return false;
	}
	
	public function getSpecialParameterList($app, $text)
	{
		return false;
	}
	
	public function getGroupParameter($activity)
	{
		return false;
	}
	
	public function getNavigation()
	{
		return false;
	}
	
	public function isFilterValid($filterValue)
	{
		return false;
	}
	
	public function filterNotificationTypes($types, $filter)
	{
		return false;
	}
	
	public function getQueryForFilter($filter)
	{
		return false;
	}
}