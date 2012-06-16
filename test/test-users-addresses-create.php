<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="css/style.css" />
</style>
<title>Test Users Addresses Create</title>
</head>

<body>
<?php
require_once('test-util.php'); 
require_once(dirname(__FILE__) . '/../lib/ca-main.php'); 

echo '<div class="apitest">';
echo '<h1>users_addresses_create</h1>';

$userid = 837592;
$ca = new CityApi();
$ca->debug = true;
$ca->json = false;
$results = $ca->users_addresses_create($userid, array('street'=>'123 Any Way', 
													 'city'=>'Fulton', 
													 'state'=>'MD', 
													 'zipcode'=>'20759', 
													 'location_type'=>'Home', ));

echo '<h2>var_dump results:</h2>';
var_dump($results);

$ca->json = true;
$results = $ca->users_addresses_create($userid, array('street'=>'123 Any Way', 
													 'city'=>'Fulton', 
													 'state'=>'MD', 
													 'zipcode'=>'20759', 
													 'location_type'=>'Work', ));
echo "<h2>results:</h2>$results";

echo '<h2>var_dump results:</h2>';
var_dump($results);

echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json($results);

echo '</pre>';
echo '</div>';
?>

</body>

</html>
