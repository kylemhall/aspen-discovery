<?php

include_once ROOT_DIR . '/services/Admin/Admin.php';
include_once ROOT_DIR . '/sys/LocalEnrichment/UserList.php';

class NYTLists extends Admin_Admin
{

	function launch()
	{
		global $interface;
		global $configArray;

		require_once ROOT_DIR . '/sys/Enrichment/NewYorkTimesSetting.php';
		global $configArray;
		$nytSettings = new NewYorkTimesSetting();
		if (!$nytSettings->find(true)) {
			$interface->assign('error', 'The New York Times API is not configured properly, create settings at <a href="/Admin/NewYorkTimesSettings"></a>');
		} else {
			$api_key = $nytSettings->booksApiKey;

			// instantiate class with api key
			require_once ROOT_DIR . '/sys/NYTApi.php';
			$nyt_api = new NYTApi($api_key);

			//Get the raw response from the API with a list of all the names
			$availableListsRaw = $nyt_api->get_list('names');
			//Convert into an object that can be processed
			$availableLists = json_decode($availableListsRaw);

			$interface->assign('availableLists', $availableLists);

			$isListSelected = !empty($_REQUEST['selectedList']);
			$selectedList = null;
			if ($isListSelected) {
				$selectedList = $_REQUEST['selectedList'];
				$interface->assign('selectedListName', $selectedList);

				if (isset($_REQUEST['submit'])) {
					//Find and update the correct Pika list, creating a new list as needed.
					require_once ROOT_DIR . '/services/API/ListAPI.php';
					$listApi = new ListAPI();
					$results = $listApi->createUserListFromNYT($selectedList);
					if ($results['success'] == false) {
						$interface->assign('error', $results['message']);
					} else {
						$interface->assign('successMessage', $results['message']);
					}
				}
			}

			// Fetch lists after any updating has been done

			// Get user id
			$nyTimesUser = new User();
			$nyTimesUser->username = 'nyt_user';
			if ($nyTimesUser->find(1)) {
				// Get User Lists
				$nyTimesUserLists = new UserList();
				$nyTimesUserLists->user_id = $nyTimesUser->id;
				$nyTimesUserLists->whereAdd('title like "NYT - %"');
				$nyTimesUserLists->orderBy('title');
				$pikaLists = $nyTimesUserLists->fetchAll();

				$interface->assign('pikaLists', $pikaLists);
			}
		}

		$this->display('nytLists.tpl', 'Lists from New York Times');
	}

	function getAllowableRoles()
	{
		return array('opacAdmin', 'libraryAdmin', 'libraryManager', 'contentEditor');
	}
}