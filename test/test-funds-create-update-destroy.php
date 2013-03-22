<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="css/style.css" />
</style>
<title>Test Funds Create Update Destroy</title>
</head>
<body>
<?php
require_once 'test-util.php' ; 
require_once dirname(__FILE__) . '/../lib/ca-main.php'; 
echo '<div class="apitest">';
echo '<h1>Test Funds Create Update Destroy</h1>
<p>Tests:</p>
<ul>
<li>Funds Create</li>
<li>Funds Update</li>
<li>Funds Destroy</li>
</ul>';

$ca = new CityApi();
$ca->debug = true;

echo '<hr>';
echo '<h1>funds_create</h1>';
$ca->json = false;
$args = array(
	'name' => 'My Test Fund',
	'external_id' => 'TF101',
	'group_id' => '33150', // church group_id
	'campus_name' => 'My Campus',
	'givable' => 'true',
	//'fund_state' => '',
	//'pledge_description' => '',
	//'pledge_inactive_date' => '',
	//'pledge_state' => '',
	//'pledge_type' => '',
	//'tax_deductable' => '',
	);
$results = $ca->funds_create($args);
$fund_id = $results['id'];
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json(json_encode($results));
echo '</pre>';

echo '<hr>';
echo '<h1>funds_update</h1>';
$ca->json = true;
$args = array(
	'name' => 'My Test Fund new name',
	'external_id' => 'TF101a',
	'group_id' => '33150', // church group_id
	'campus_name' => 'My Campus new name',
	'givable' => 'false',
	//'fund_state' => '',
	//'pledge_description' => '',
	//'pledge_inactive_date' => '',
	//'pledge_state' => '',
	//'pledge_type' => '',
	//'tax_deductable' => '',
	);
$results = $ca->funds_update($fund_id, $args);
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json($results);
echo '</pre>';

echo '<hr>';
echo '<h1>funds_destroy</h1>';
$ca->json = true;
$results = $ca->funds_destroy($fund_id);
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json($results);
echo '</pre>';
?>
</body>
</html>
