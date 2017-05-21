<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 11.05.17
 * Time: 23:50
 */

namespace Jira;

    /* Производственный календарь в формате CSV
     * http://data.gov.ru/opendata/7708660670-proizvcalendar
     */
class Calendar {
    
    private $countDays = 0;
    private $arHolidays = array();

    function __construct(){
        date_default_timezone_set("Europe/Moscow");
        $calendar_file = __DIR__.'/../data/calendar.csv';
        $file = fopen($calendar_file, "r");
        $this->countDays = date("t");
        while ($arCalendarLine = fgetcsv($file)){
            //echo "<pre>";print_r($arCalendarLine);echo "</pre>";    
            //если текущий год, то выбираем месяц и получаем выходные
            //echo date('Y')." ".$arCalendarLine[0];
            if ($arCalendarLine[0] == date('Y')){
                //echo date('m');
                //print_r($arCalendarLine);
                $arHolidays = explode(",", $arCalendarLine[date('n')]); 
                //удаляем предпраздничные дни они нас не интересуют
                foreach ($arHolidays as $key => $holiday) {
                    if (strpos($holiday, "*") !== false) unset($arHolidays[$key]);
                }
                //print_r($arHolidays);
                $this->arHolidays = $arHolidays;
                break;
            }
        }
    }

    function getWorkingDays(){
        //echo $this->countDays<>;
        return ($this->countDays - count($this->arHolidays));
    }
    /*
     * get worked days from current month
     */
    function getWorkedDays(){
        $day_number = date("d");
        //echo date('Y-m-d H:i:s') ;
        //echo date('d-m-Y');
        $arWasHolidays = array();
        //узнаем как много праздников и выходных уже прошло на текущий день
        foreach ($this->arHolidays as $holiday) {
            if ($holiday <= $day_number) $arWasHolidays[] = $holiday;
        }
        return ($day_number - count($arWasHolidays));
    }
}