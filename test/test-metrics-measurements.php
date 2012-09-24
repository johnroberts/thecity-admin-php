<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="css/style.css" />
</style>
<title>Test Metrics Measurements</title>
</head>
<body>
<?php
require_once('test-util.php'); 
require_once(dirname(__FILE__) . '/../lib/ca-main.php'); 
echo '<div class="apitest">';
echo '<h1>Test Metrics Measurements</h1>
<p>Tests:</p>
<ul>
<li>Metrics Measurements Index</li>
<li>Metrics Measurements Show</li>
<li>Metrics Measurements Create</li>
<li>Metrics Measurements Update</li>
<li>Metrics Measurements Destroy</li>
<li>Metrics Measurements Last</li>
<li>Metrics Measurements Values</li>
</ul>';

$ca = new CityApi();
$ca->debug = true;

echo '<h1>metrics_measurements_index</h1>';
$metricid = 1;
$ca->json = false;
$results = $ca->metrics_measurements_index($metricid);
$measurementid = $results['measurements'][0]['id'];
echo '<pre>measurementid: ' . $measurementid . '</pre>';
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json(json_encode($results));
echo '</pre>';

echo '<h1>metrics_measurements_show</h1>';
$ca->json = true;
$results = $ca->metrics_measurements_show($metricid, $measurementid);
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json($results);
echo '</pre>';

// create a metric
echo '<h1>metrics_create</h1>';
$args = array('name' => 'Drive Yugos', 'is_percent' => 'false', 'description' => 'The number of people who drive Yugos');
$ca->json = false;
$results = $ca->metrics_create($args);
$metricid = $results['id'];
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json(json_encode($results));
echo '</pre>';

// create a measurement for this metric
echo '<h1>metrics_measurements_create</h1>';
$ca->json = false;
$args = array('value' => '42', 'date' => '2012-09-22', 'time' => '4:00 PM');
$results = $ca->metrics_measurements_create($metricid, $args);
$measurementid = $results['id'];
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json(json_encode($results));
echo '</pre>';

// update the measurement for this metric
echo '<h1>metrics_measurements_update</h1>';
$ca->json = true;
$args = array('value' => '28');
$results = $ca->metrics_measurements_update($metricid, $measurementid, $args);
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json($results);
echo '</pre>';

// destroy the measurement for this metric
echo '<h1>metrics_measurements_destroy</h1>';
$ca->json = true;
$results = $ca->metrics_measurements_destroy($metricid, $measurementid);
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';

echo format_json($results);
echo '</pre>';

// destroy the metric
echo '<h1>metrics_destroy</h1>';
$ca->json = true;
$results = $ca->metrics_destroy($metricid);
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json($results);
echo '</pre>';

echo '<h1>metrics_measurements_last</h1>';
$ca->json = true;
$metricid = 1;
$results = $ca->metrics_measurements_last($metricid);
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json($results);
echo '</pre>';

echo '<h1>metrics_measurements_values</h1>';
$ca->json = true;
$metricid = 1;
$results = $ca->metrics_measurements_values($metricid);
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json($results);
echo '</pre>';

?>
</body>
</html>
