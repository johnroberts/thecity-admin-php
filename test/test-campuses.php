<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="css/style.css" />
</style>
<title>Test Campuses</title>
</head>
<body>
<?php
require_once 'test-util.php' ; 
require_once dirname(__FILE__) . '/../lib/ca-main.php'; 
echo '<div class="apitest">';
echo '<h1>Test Campuses</h1>
<p>Tests:</p>
<ul>
<li>Campuses Index</li>
<li>Campuses Show</li>
</ul>';

$ca = new CityApi();
$ca->debug = true;

echo '<h1>campuses_index</h1>';
$ca->json = false;
$results = $ca->campuses_index();
$campusid = $results['campuses'][0]['id'];
echo '<pre>campusid: ' . $campusid . '</pre>';
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json(json_encode($results));
echo '</pre>';

echo '<hr>';
echo '<h1>campuses_show</h1>';
$ca->json = true;
$results = $ca->campuses_show($campusid);
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json($results);
echo '</pre>';

?>
</body>
</html>
