<?php
/*
** Example Usage
**
**
*/
require_once(dirname(__FILE__) . '/../lib/ca-main.php'); 

echo '<pre>';

$ca = new CityApi();

$results = $ca->groups_count(); // headers are NULL until after the first endpoint call
echo "\n<strong>group_count() results</strong>\n";
var_dump($results);

echo "\n<strong>last_response_start_line</strong>\n";
var_dump($ca->get_last_response_start_line());

echo '</pre>';
?> 