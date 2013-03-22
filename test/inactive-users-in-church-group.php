<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="css/style.css" />
<title>Inactive Users in Church Group</title>
</head>

<body>
<?php
require_once('test-util.php'); 
require_once(dirname(__FILE__) . '/../lib/ca-main.php'); 

echo '<div class="apitest">';
echo '<h1>Inactive Users in Church Group</h1>';

$ca = new CityApi();
$ca->debug = true;
$ca->json = true;

$groupid = 33150;
for($i = 0; $i < 3; $i++) {
	$results = $ca->groups_roles_index($groupid, array('page' => $i+1, 'title' => 'Inactive')); 
	echo '<h2>Page ' . $i+1 . '</h2>';
	echo '<pre>';
	echo format_json($results);
	$last_status_code = $ca->get_last_status_code();
	echo 'last_status_code: ' . $last_status_code . "\n";
	echo '</pre>';
	if($last_status_code != 200)
		break;
}

$ca->json = false;
$results = $ca->groups_roles_index($groupid, array('title' => 'Inactive')); 
echo '<pre>';
echo "<h2>results:</h2>$results";
echo '<h2>var_dump results:</h2>';
var_dump($results);
echo '</pre>';

echo '</div>';
?>

</body>
</html>
