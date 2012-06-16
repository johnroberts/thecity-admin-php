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
echo '<h1>users_count</h1>';

$ca = new CityApi();
$ca->debug = true;
$ca->json = true;

echo '<h2>Test: created_in_the_last_7_days</h2>';
$results = $ca->users_count('created_in_the_last_7_days');
echo "<h2>results:</h2>$results";
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json($results);
echo '</pre>';


echo '<h2>Test: created_in_the_last_90_days</h2>';
$results = $ca->users_count('created_in_the_last_90_days');
echo "<h2>results:</h2>$results";
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json($results);
echo '</pre>';

echo '<h2>Test: no filter</h2>';
$results = $ca->users_count();
echo "<h2>results:</h2>$results";
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json($results);
echo '</pre>';

echo '</div>';
?>
</body>
</html>
