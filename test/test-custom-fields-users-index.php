<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="css/style.css" />
<title>Test Custom Fields Users Index</title>
</head>

<body>
<?php
require_once('test-util.php'); 
require_once(dirname(__FILE__) . '/../lib/ca-main.php'); 

echo '<div class="apitest">';
echo '<h1>custom_fields_users_index</h1>';

$ca = new CityApi();
$ca->debug = true;
$ca->json = true;

// Either id or label will work for the custom field id.
// Don't use spaces in field name, the API uses this as part of the path on this endpoint.
// If you want to have spaces in the custom field name, use the custom field id in API calls.
// $custom_fieldid = 'Marital Status'; // will fail
// $custom_fieldid = 'gk_allergies_medical'; // will work
$custom_fieldid = 3;

$args = array('page' => 2);
$results = $ca->custom_fields_users_index($custom_fieldid, $args); 

echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json($results);
echo '</pre>';

// Parameters need to be in alphabetical order.  
// The City sorts them that way before computing the signature.
// So does the CityApi library.
$args = array('search' => ' nuts', 'page' => 1); 
$results = $ca->custom_fields_users_index($custom_fieldid, $args); 

echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json($results);
echo '</pre>';

echo '</div>';
?>

</body>
</html>
