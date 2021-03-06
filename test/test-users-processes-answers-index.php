<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="css/style.css" />
<title>Test Users Processes Answers Index</title>
</head>

<body>
<?php
require_once('test-util.php'); 
require_once(dirname(__FILE__) . '/../lib/ca-main.php'); 

echo '<h1>users_processes_answers_index</h1>';
echo '<div class="apitest">';

$ca = new CityApi();
$ca->debug = true;
$ca->json = true;

echo "<h2>Test:</h2>";
$userid = 238801;
$processid = 12343;
$results = $ca->users_processes_answers_index($userid, $processid, array('page' => 1)); 

echo "<h2>results:</h2>$results";

echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json($results);
echo '</pre>';

echo '<h2>HTTP Response Info</h2>';
echo '<pre>';
var_dump($ca->get_last_status_code());
var_dump($ca->get_last_response_start_line());
var_dump($ca->get_last_headers());
echo '</pre>';

echo '</div>';
?>

</body>

</html>
