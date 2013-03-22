<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="css/style.css" />
</style>
<title>Test Funds Index Show</title>
</head>
<body>
<?php
require_once 'test-util.php' ; 
require_once dirname(__FILE__) . '/../lib/ca-main.php'; 
echo '<div class="apitest">';
echo '<h1>Test Funds Index Show</h1>
<p>Tests:</p>
<ul>
<li>Funds Index</li>
<li>Funds Show</li>
</ul>';

$ca = new CityApi();
$ca->debug = true;

echo '<h1>funds_index</h1>';
$ca->json = false;
$results = $ca->funds_index();
$fund_id = $results['funds'][0]['id'];
echo '<pre>fund_id: ' . $fund_id . '</pre>';
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json(json_encode($results));
echo '</pre>';

echo '<hr>';
echo '<h1>funds_show</h1>';
$ca->json = true;
$results = $ca->funds_show($fund_id);
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json($results);
echo '</pre>';

?>
</body>
</html>
