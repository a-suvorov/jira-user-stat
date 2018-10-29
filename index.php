<?$start = microtime(true);
require __DIR__.'/vendor/autoload.php';

//$smarty = new Smarty();
$arMonth = array(
	"01" => "январь",
	"02" => "февраль",
	"03" => "март",
	"04" => "апрель",
	"05" => "май",
	"06" => "июнь",
	"07" => "июль",
	"08" => "август",
	"09" => "сентябрь",
	"10" => "октябрь",
	"11" => "ноябрь",
	"12" => "декабрь",
);

$loader = new Twig_Loader_Filesystem(__DIR__."/templates/");
$twig = new Twig_Environment($loader, array(
    'cache' => __DIR__.'/templates_c/',
    "debug"=>true,
    'auto_reload' => true,
));

//$memcache_obj = new Memcache;
//$memcache_obj->connect('127.0.0.1', 11211) or die(«Could not connect»);

$nonFormatUrl = "/activity?maxResults=2000&streams=update-date+BETWEEN+%s+%s&streams=user+IS+%s";
$arUserLogin = \Jira\Config::getJiraLoginData();
$user_login = $arUserLogin["jira_access_user"];
$pass_login = $arUserLogin["jira_access_pass"];
$jira_address = $arUserLogin["jira_access_url"];
$nonFormatUrl = $jira_address.$nonFormatUrl;

//моем получить статистику за определенный месяц


$group = isset($_GET["group"]) ? htmlspecialchars($_GET["group"]) : "";
$arUsers = \Jira\User::getList($group);
//echo "<pre>"; print_r($arUsers); echo "</pre>";

$calendar = new \Jira\Calendar();
//если хотим получить информацию только по 1 пользователю
if (isset($_GET["user"])){
	$arUsers = [htmlspecialchars($_GET["user"]) => array()];
}

$month =  ($_GET["month"]) ? $_GET["month"] : date("m");
if (strlen($month) == 1) $month = "0".$month;
//echo $month;
//exit;

$startDate =  strtotime(date("Y-".$month."-01 00:00:00"))."000";
$endDate = strtotime(date("Y-".$month."-t 23:59:59" ))."999"; 

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
		//echo "<pre>";print_r($xml->entry[$i]) ;echo "</pre>";
		//exit;
		if (isActivity($xml->entry[$i]))
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
					"month" => $arMonth[$month],
					"detail"=>$_GET["detail"]
					));

$time = microtime(true) - $start;
echo "<br><br>";
printf('Скрипт выполнялся %.4F сек.', $time);

function sendQuery($url, $user, $pass){
		$ch = curl_init();
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_URL,  $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  //      curl_setopt($ch, CURLOPT_TIMEOUT, 10000);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERPWD, $user.":".$pass);
        $response = curl_exec($ch);
        return $response;
}

function isActivity($xml_entity){
	//echo "<pre>";print_r($xml_entity);echo "</pre>";
	$title = strip_tags($xml_entity->title);
	if (($xml_entity->content) && ((stripos($title, "Assignee") !== false) || (stripos($title, "commented") !== false) || (stripos($title, "closed") !== false) || (stripos($title, "resolved") !== false) )) {
			return true;
		}
	if (stripos($xml_entity->link[0]->attributes()->href, "focusedCommentId") !== false) {
		//echo $xml_entity->link[0]->attributes()->href;
		return true;
	}
	return false;
	
}

?>