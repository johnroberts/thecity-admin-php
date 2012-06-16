<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="css/style.css" />
<title>Test Groups Create</title>
</head>

<body>
<?php
require_once('test-util.php'); 
require_once(dirname(__FILE__) . '/../lib/ca-main.php'); 

echo '<div class="apitest">';
echo '<h1>groups_create()</h1>';

$ca = new CityApi();
$ca->debug = true;
$ca->json = true;

echo '<h2>Test: </h2>';
$args = array(
	'name' => 'Test Service Group 3',
	'parent_id' => '43681',
	'group_type' => 'Service',
	'description' => 'A great group to serve in!',
	'hide_albums' => 'true',
	'target_size' => '20',
	'secure' => 'true',
);
$results = $ca->groups_create($args); 

//echo "<h2>results:</h2>$results";
//echo '<h2>var_dump results:</h2>';
//var_dump($results);

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
