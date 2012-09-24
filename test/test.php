<?php
/*
** Example Usage
**
**
*/
require_once(dirname(__FILE__) . '/../lib/ca-main.php'); 

echo '<pre>';

$ca = new CityApi();
echo "\n<strong>ca before</strong>\n";
var_dump($ca);

$results = $ca->groups_count();
echo "\n<strong>group_count() results</strong>\n";
var_dump($results);

echo "\n<strong>ca after</strong>\n";
var_dump($ca);

$results = $ca->users_count();
echo "\n<strong>users_count() results</strong>\n";
var_dump($results);

echo "\n<strong>last_response_start_line</strong>\n";
var_dump($ca->get_last_response_start_line());

echo "\n<strong>json_encode last_response_start_line</strong>\n";
echo json_encode($ca->get_last_response_start_line());
echo "\n";

echo "\n<strong>last_headers</strong>\n";
var_dump($ca->get_last_headers());

echo "\n<strong>json_encode last_headers</strong>\n";
echo json_encode($ca->get_last_headers());
echo "\n";

$ca->json = true;
$results = $ca->groups_count();
echo "\n<strong>json groups_count() results</strong>\n";
var_dump($results);

echo '</pre>';
?> 