<?
require 'vendor\autoload.php';

//$smarty = new Smarty();
$loader = new Twig_Loader_Filesystem(__DIR__."/templates/");
$twig = new Twig_Environment($loader, array(
    'cache' => __DIR__.'/templates_c/',
    "debug"=>true,
    'auto_reload' => true,
));

$nonFormatUrl = "/activity?maxResults=2000&streams=update-date+BETWEEN+%s+%s&streams=user+IS+%s";
$arUserLogin = \Jira\Config::getJiraLoginData();
$user_login = $arUserLogin["jira_access_user"];
$pass_login = $arUserLogin["jira_access_pass"];
$jira_address = $arUserLogin["jira_access_url"];
$nonFormatUrl = $jira_address.$nonFormatUrl;
/*
$arUsers = [
	//["login"=>"r.kalinin", "status"=>"m"],
	"a.suvorov" 	=> ["status"=>"s", "time"=>"1"],
	"e.ladutko" 	=> ["status"=>"p", "time"=>"1"],
	"a.bychkov" 	=> ["status"=>"p", "time"=>"1"],
	"a.orlov" 		=> ["status"=>"w", "time"=>"1"],
	"m.rumyantsev"  => ["status"=>"j", "time"=>"1"],
	"a.mikhailov" 	=> ["status"=>"w", "time"=>"1"],
	"a.korneva"  	=> ["status"=>"w", "time"=>"1"],
	"t.novikov"  	=> ["status"=>"w", "time"=>"1"],
	"o.shumilin" 	=> ["status"=>"j", "time"=>"1"],
	"k.lobzova"		=> ["status"=>"w", "time"=>"1"],
	"s.chuikov"		=> ["status"=>"w", "time"=>"1"],
];
*/
$arUsers = \Jira\User::getList(htmlspecialchars($_GET["group"]));
//echo "<pre>"; print_r($arUsers); echo "</pre>";

$calendar = new \Jira\Calendar();
//если хотим получить информацию только по 1 пользователю
if (isset($_GET["user"])){
	$arUsers = [htmlspecialchars($_GET["user"]) => array()];
}
$startDate =  strtotime(date("Y-m-01 00:00:00"))."000";
$endDate = strtotime(date("Y-m-t 23:59:59" ))."999"; 
foreach ($arUsers as $login => $data) {
	$arUniqueIssues = array();////массив с задачами пользователя, без повторений.

	$url = sprintf($nonFormatUrl, $startDate, $endDate, $login);//формируем url для получения данных
	$result = sendQuery($url, $user_login, $pass_login);

	/*анализируем результат*/
	$xml = simplexml_load_string($result);
	$countOfAction = count($xml->entry);
	//начинаем с конца чтобы накапливать с первого числа месяца задачи
	for ($i = $countOfAction-1; $i>=0; $i--) { 
		$arIssue = array();
		if (($xml->entry[$i]->content) && ((strpos($xml->entry[$i]->title, "commented") !== false) || (strpos($xml->entry[$i]->title, "resolved") !== false)))
		{ 
			$issue_date = date("d-m-Y", strtotime($xml->entry[$i]->updated));
			$arIssueUrl = explode("?", $xml->entry[$i]->link[0]->attributes()->href);
			$issue_url = $arIssueUrl[0];
			$arIssueUrl = explode("/", $arIssueUrl[0]);
			$issue_id = array_pop($arIssueUrl); //последний элемент массива
			/*получаем название задачи*/
			$title = strip_tags($xml->entry[$i]->title);
			$arTitle = explode(trim($issue_id), $title);
			$issue_name = $arTitle[1];
			$arIssue["name"] = $issue_id.$issue_name;
			$arIssue["date"] = $issue_date;
			$arIssue["url"] = $issue_url;
			$arUniqueIssues[$issue_id] = $issue_url;
			/*получаем дату и ID задачи, задача учитывается только один раз в момент первого появления в данном месяце
			* и записывается в счет дня первого комментария по ней.
			*/
			//задача ресолв или comment
			$arUsers[$login]["issues"][$arIssue["date"]][$issue_id] = $arIssue; //собираем по дате, для вывода и по id задачи, чтобы не дублировать в выводе при нескольких комментариях
		}
		
	}
	$arUsers[$login]["unique_issues"] = $arUniqueIssues;
}
echo $twig->render('index.twig', array(
					"arUsers"=>$arUsers,
					"worked"=>$calendar->getWorkedDays(), 
					"working"=>$calendar->getWorkingDays(), 
					"detail"=>$_GET["detail"]
					));


function sendQuery($url, $user, $pass){
		$ch = curl_init();
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_URL,  $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10000);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERPWD, $user.":".$pass);
        $response = curl_exec($ch);
        return $response;
}

?>
