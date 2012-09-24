<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="css/style.css" />
<title>Test Get All Users</title>
</head>

<body>
<?php
require_once('test-util.php'); 
require_once(dirname(__FILE__) . '/../lib/ca-main.php'); 

echo '<div class="apitest">';
echo '<h1>Get All Users</h1>';

$ca = new CityApi();
$ca->debug = true;
$ca->json = false;

/*
echo '<h2>Test: users_count</h2>';
$results = $ca->users_count('ljkd');
echo "<h2>results:</h2>";
echo '<pre>';
echo "Count:" . $results['count'];
echo '</pre>';
*/

// get page 1
$ca->debug = true;
$ca->json = false;
$results = $ca->users_index(array('page'=>'1'));
$total_pages = $results['total_pages'];
$total_entries = $results['total_entries'];
$per_page = $results['per_page'];
echo "<h2>Got $total_pages pages, $total_entries users, $per_page per page</h2>";
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json(json_encode($results));
echo '</pre>';

// get remaining pages
for ($page = 2; $page <= $total_pages ; $page++)
{
	$results = $ca->users_index(array('page'=>$page));
	echo "<h2>Page $page:</h2>";
	echo '<h2>Formatted JSON results: </h2>';
	echo '<pre>';
	echo format_json(json_encode($results));
	echo '</pre>';
}

/*
$ca->debug = true;
//$ca->json = true;
//$results = $ca->users_index(array('page'=>'1'));
$results = $ca->users_index();
echo "<h2>results:</h2>$results";
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json($results);
echo '</pre>';

$user_count = $results['count'];
*/

echo '</div>';
?>
</body>
</html>
