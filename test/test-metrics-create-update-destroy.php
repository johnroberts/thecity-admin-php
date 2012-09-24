<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="css/style.css" />
</style>
<title>Test Metrics Create Update Destroy</title>
</head>
<body>
<?php
require_once('test-util.php'); 
require_once(dirname(__FILE__) . '/../lib/ca-main.php'); 
echo '<div class="apitest">';

$ca = new CityApi();
$ca->debug = true;

echo '<h1>metrics_create</h1>';
$args = array('name' => 'Drive Yugos', 'is_percent' => 'false', 'description' => 'The number of people who drive Yugos');
$ca->json = false;
$results = $ca->metrics_create($args);
$metricid = $results['id'];
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json(json_encode($results));
echo '</pre>';

echo '<h1>metrics_update</h1>';
$args = array('name' => 'Drive Yugos!!', 'description' => 'The number of people who drive Yugos!!');
$ca->json = true;
$results = $ca->metrics_update($metricid, $args);
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json($results);
echo '</pre>';


echo '<h1>metrics_destroy</h1>';
$ca->json = true;
$results = $ca->metrics_destroy($metricid);
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json($results);
echo '</pre>';



?>
</body>
</html>
