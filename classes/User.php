<?
namespace Jira;

class User{
	static function getList($group){
		$group || $group = 5;
		$arResult = array();
		$get_users_url = "user/search?startAt=0&maxResults=1000&username=".$group.".";
		$json_users = \Jira\Config::getResource($get_users_url);
		$arUsers = json_decode($json_users, true);
		foreach ($arUsers as $value) {
			$arResult[$value["key"]] = array();
		}
		return $arResult;
	}	
}
?>