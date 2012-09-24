<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="css/style.css" />
<title>Test Users Count</title>
</head>

<body>
<?php
require_once('test-util.php'); 
require_once(dirname(__FILE__) . '/../lib/ca-main.php'); 

echo '<div class="apitest">';
echo '<h1>users_index</h1>';

$ca = new CityApi();

/*
$results = $ca->users_index();
var_dump($results);
echo '<br />';

$ca->json = true;
$results = $ca->users_index();
echo $results;

$ca->json = false;
$results = $ca->users_index(array('page'=>'1', 'filter'=>'created_in_the_last_7_days'));
var_dump($results);
echo '<br />';

$ca->json = false;
$results = $ca->users_index(array('filter'=>'created_in_the_last_7_days', 'page'=>'1'));
var_dump($results);
echo '<br />';

$results = $ca->users_index(array('filter'=>'created_in_the_last_7_days'));
var_dump($results);
echo '<br />';

$results = $ca->users_index(array('page'=>'1'));
var_dump($results);
echo '<br />';
*/

$ca->debug = true;
$ca->json = true;
$results = $ca->users_index(array('page'=>'1', 'filter'=>'created_in_the_last_7_days'));
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json($results);
echo '</pre>';

$results = $ca->users_index(array('filter'=>'created_in_the_last_7_days', 'page'=>'1'));
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json($results);
echo '</pre>';

echo '</div>';
?>
</body>
</html>
