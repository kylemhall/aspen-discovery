<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Grouping/GroupedWorkDisplaySetting.php';

class Admin_GroupedWorkDisplay extends ObjectEditor
{
	function getObjectType(){
		return 'GroupedWorkDisplaySetting';
	}
	function getToolName(){
		return 'GroupedWorkDisplay';
	}
	function getPageTitle(){
		return 'Grouped Work Display Settings';
	}
	function canDelete(){
		return UserAccount::userHasRole('opacAdmin') || UserAccount::userHasRole('libraryAdmin');
	}
	function getAllObjects(){
		$object = new GroupedWorkDisplaySetting();
		$object->orderBy('name');
		$object->find();
		$list = array();
		while ($object->fetch()){
			$list[$object->id] = clone $object;
		}
		return $list;
	}
	function getObjectStructure(){
		return GroupedWorkDisplaySetting::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'id';
	}
	function getIdKeyColumn(){
		return 'id';
	}
	function getAllowableRoles(){
		return array('opacAdmin', 'libraryAdmin');
	}

	function getInstructions(){
		//return 'For more information on themes see TBD';
		return '';
	}

	function getListInstructions(){
		return $this->getInstructions();
	}

	/** @noinspection PhpUnused */
	function resetMoreDetailsToDefault(){
		$groupedWorkSetting = new GroupedWorkDisplaySetting();
		$groupedWorkSettingId = $_REQUEST['id'];
		$groupedWorkSetting->id = $groupedWorkSettingId;
		if ($groupedWorkSetting->find(true)){
			$groupedWorkSetting->clearMoreDetailsOptions();

			$defaultOptions = array();
			require_once ROOT_DIR . '/RecordDrivers/RecordInterface.php';
			$defaultMoreDetailsOptions = RecordInterface::getDefaultMoreDetailsOptions();
			$i = 0;
			foreach ($defaultMoreDetailsOptions as $source => $defaultState){
				$optionObj = new GroupedWorkMoreDetails();
				$optionObj->groupedWorkSettingsId = $groupedWorkSettingId;
				$optionObj->collapseByDefault = $defaultState == 'closed';
				$optionObj->source = $source;
				$optionObj->weight = $i++;
				$defaultOptions[] = $optionObj;
			}

			$groupedWorkSetting->moreDetailsOptions = $defaultOptions;
			$groupedWorkSetting->update();

			$_REQUEST['objectAction'] = 'edit';
		}
		header("Location: /Admin/GroupedWorkDisplay?objectAction=edit&id=" . $groupedWorkSettingId);
	}
}