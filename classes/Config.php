<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 11.05.17
 * Time: 23:50
 */

namespace Jira;


class Config {
    public static $jira_user = "<login_to_jira>";
    public static $jira_pass = "<pass_to_jira>";
    public static $jira_url  = "<jira_url_with_http>";
    /*
     * $param $type_of_resource - тип ресурса
     * $param $date - данные которые передаем jira. Меняются в зависимости от типа ресурса
     */
    public static function getResource($typeOfResource){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_URL,  self::$jira_url."/rest/api/2/".$typeOfResource);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10000);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERPWD, self::$jira_user.":".self::$jira_pass);
        $response = curl_exec($ch);
        return $response;
    }

    public static function getJiraLoginData(){
        return array(
                "jira_access_user"=> self::$jira_user,
                "jira_access_pass"=> self::$jira_pass,
                "jira_access_url" => self::$jira_url,
            );
    }
}