<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="css/style.css" />
</style>
<title>Test Users Show</title>
</head>
<body>
<?php
require_once('test-util.php'); 
require_once(dirname(__FILE__) . '/../lib/ca-main.php'); 
echo '<div class="apitest">';
echo '<h1>users_show</h1>';

$ca = new CityApi();
$ca->debug = true;
$ca->json = true;
$results = $ca->users_show('224870');

echo '<h2>var_dump results:</h2>';
echo '<pre>';
var_dump($results);
echo'</pre>';

echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json($results);
echo '</pre>';

echo '<h2>var_dump last response start line:</h2>';
var_dump($ca->get_last_response_start_line());
echo '<h2>var_dump last headers:</h2>';
echo '<pre>';
echo var_dump($ca->get_last_headers());
echo '</pre>';

echo "<h2>Test with optional parameters:</h2>";
//$args = array( 'include_participation' => 'true', 'include_custom_fields' => 'true'); // this fails with signature authentication error
$args = array( 'include_custom_fields' => 'true', 'include_participation' => 'true');  // args in alphabetical order
$results = $ca->users_show('224870', $args);
echo "<h2>results:</h2>$results";

echo '<h2>var_dump results:</h2>';
echo '<pre>';
var_dump($results);
echo'</pre>';

echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json($results);
echo '</pre>';

echo '<h2>var_dump last response start line:</h2>';
var_dump($ca->get_last_response_start_line());
echo '<h2>var_dump last headers:</h2>';
echo '<pre>';
echo var_dump($ca->get_last_headers());
echo '</pre>';

$ca->json = false;
$results = $ca->users_show('224870');
echo '<h2>var_dump PHP results</h2>';
echo '<pre>';
echo var_dump($results);
echo '</pre>';

echo '<h2>var_dump last response start line:</h2>';
var_dump($ca->get_last_response_start_line());
echo '<h2>var_dump last headers:</h2>';
echo '<pre>';
echo var_dump($ca->get_last_headers());
echo '</pre>';

?>
</body>
</html>
