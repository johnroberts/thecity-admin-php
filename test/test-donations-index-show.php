<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="css/style.css" />
</style>
<title>Test Donations Index Show</title>
</head>
<body>
<?php
require_once 'test-util.php' ; 
require_once dirname(__FILE__) . '/../lib/ca-main.php'; 
echo '<div class="apitest">';
echo '<h1>Test Donations Index Show</h1>
<p>Tests:</p>
<ul>
<li>Donations Index</li>
<li>Donations Show</li>
</ul>';

$ca = new CityApi();
$ca->debug = true;

echo '<h1>donations_index</h1>';
$ca->json = false;
$args = array('start_date'=>'2013-03-05', 'end_date'=>'2013-03-05');
//$args = array('start_date'=>'3/5/2013', 'end_date'=>'3/5/2013'); // doesn't find donations, City bug? 
$results = $ca->donations_index($args);
$donationid = $results['donations'][0]['id'];
echo '<pre>donationid: ' . $donationid . '</pre>';
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json(json_encode($results));
echo '</pre>';

echo '<hr>';
echo '<h1>donations_show</h1>';
$ca->json = true;
$results = $ca->donations_show($donationid);
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json($results);
echo '</pre>';

?>
</body>
</html>
