<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="css/style.css" />
</style>
<title>Test Terminology</title>
</head>
<body>
<?php
require_once('test-util.php'); 
require_once(dirname(__FILE__) . '/../lib/ca-main.php'); 
echo '<div class="apitest">';
echo '<h1>Test Terminology</h1>
<p>Tests:</p>
<ul>
<li>Terminology Index</li>
<li>Terminology Show</li>
<li>Terminology Update</li>
</ul>';

$ca = new CityApi();
$ca->debug = true;

echo '<hr>';
echo '<h1>terminology_index</h1>';
$ca->json = false;
$results = $ca->terminology_index();
$native_label = key($results['labels'][0]);
echo '<pre>native_label: ' . $native_label . '</pre>';
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json(json_encode($results));
echo '</pre>';

echo '<hr>';
echo '<h1>terminology_show</h1>';
$ca->json = true;
$results = $ca->terminology_show($native_label);
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json($results);
echo '</pre>';

// change Other to Yugo Drivers
echo '<hr>';
echo '<h1>terminology_update</h1>';
$args = array('label' => 'Yugo Drivers');
$ca->json = true;
$native_label = 'Other';
$results = $ca->terminology_update($native_label, $args);
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json($results);
echo '</pre>';

// change Other back to Other
$args = array('label' => 'Other');
$results = $ca->terminology_update($native_label, $args);
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json($results);
echo '</pre>';

?>
</body>
</html>
