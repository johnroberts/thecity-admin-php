<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="css/style.css" />
<title>Test Checkins By ...</title>
</head>

<body>
<?php
require_once('test-util.php'); 
require_once(dirname(__FILE__) . '/../lib/ca-main.php'); 

echo '<div class="apitest">';

echo '<h1>checkins_by_barcode</h1>';
$ca = new CityApi();
$ca->debug = true;
$ca->json = true;
$barcode = '7055';
$results = $ca->checkins_by_barcode($barcode); 

echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json($results);
echo '</pre>';

echo '<h1>checkins_by_callboard_number</h1>';
$ca = new CityApi();
$ca->debug = true;
$ca->json = true;
$callboard_number = '103';
$results = $ca->checkins_by_callboard_number($callboard_number); 

echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json($results);
echo '</pre>';

echo '<h1>checkins_by_pager_number</h1>';
$ca = new CityApi();
$ca->debug = true;
$ca->json = true;
$pager_number = '103';
$results = $ca->checkins_by_pager_number($pager_number); 

echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json($results);
echo '</pre>';

echo '<h1>checkins_by_parent_receipt_barcode</h1>';
$ca = new CityApi();
$ca->debug = true;
$ca->json = true;
$parent_receipt_barcode = '103';
$results = $ca->checkins_by_parent_receipt_barcode($parent_receipt_barcode); 

echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json($results);
echo '</pre>';

echo '</div>';
?>

</body>
</html>
