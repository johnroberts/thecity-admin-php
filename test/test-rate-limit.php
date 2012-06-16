<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="css/style.css" />
<title>Test Rate Limit</title>
</head>

<body>
<?php
require_once('test-util.php'); 
require_once(dirname(__FILE__) . '/../lib/ca-main.php'); 

echo '<div class="apitest">';

echo '<h1>Rate Limit</h1>';

$ca = new CityApi();
echo 'Before groups_count() call: <br />';
echo '<br />';

echo 'get_ratelimit_limit_by_account: <br/>';
$x = $ca->get_ratelimit_limit_by_account();
var_dump($x);
echo '<br />';

echo 'get_ratelimit_limit_by_ip: <br/>';
$x = $ca->get_ratelimit_limit_by_ip();
var_dump($x);
echo '<br />';

echo 'get_ratelimit_remaining_by_account: <br/>';
$x = $ca->get_ratelimit_remaining_by_account();
var_dump($x);
echo '<br />';

echo 'get_ratelimit_remaining_by_ip: <br/>';
$x = $ca->get_ratelimit_remaining_by_ip();
var_dump($x);
echo '<br />';

$results = $ca->groups_count();

echo 'After groups_count() call: <br />';
echo '<br />';

echo 'get_ratelimit_limit_by_account: <br/>';
$x = $ca->get_ratelimit_limit_by_account();
var_dump($x);
echo '<br />';

echo 'get_ratelimit_limit_by_ip: <br/>';
$x = $ca->get_ratelimit_limit_by_ip();
var_dump($x);
echo '<br />';

echo 'get_ratelimit_remaining_by_account: <br/>';
$x = $ca->get_ratelimit_remaining_by_account();
var_dump($x);
echo '<br />';

echo 'get_ratelimit_remaining_by_ip: <br/>';
$x = $ca->get_ratelimit_remaining_by_ip();
var_dump($x);

echo '</div>';
?>

</body>
</html>

