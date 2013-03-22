<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="css/style.css" />
</style>
<title>Test Pledges</title>
</head>
<body>
<?php
require_once 'test-util.php' ; 
require_once dirname(__FILE__) . '/../lib/ca-main.php'; 
echo '<div class="apitest">';
echo '<h1>Test Pledges</h1>
<p>Tests:</p>
<ul>
<li>Pledges Index</li>
<li>Pledges Show</li>
</ul>';

$ca = new CityApi();
$ca->debug = true;

echo '<h1>pledges_index</h1>';
$ca->json = false;
$results = $ca->pledges_index();
$campusid = $results['pledges'][0]['id'];
echo '<pre>pledgeid: ' . $pledgeid . '</pre>';
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json(json_encode($results));
echo '</pre>';

echo '<hr>';
echo '<h1>pledges_show</h1>';
$ca->json = true;
$results = $ca->pledges_show($pledgeid);
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json($results);
echo '</pre>';

?>
</body>
</html>
