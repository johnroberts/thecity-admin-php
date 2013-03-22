<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="css/style.css" />
</style>
<title>Test Skills</title>
</head>
<body>
<?php
require_once 'test-util.php' ; 
require_once dirname(__FILE__) . '/../lib/ca-main.php'; 
echo '<div class="apitest">';
echo '<h1>Test Skills</h1>
<p>Tests:</p>
<ul>
<li>Skills Index</li>
<li>Skills Show</li>
<li>Skills Create</li>
<li>Skills Destroy</li>
<li>Skills Users Index</li>
<li>Skills Users Index Ids</li>
<li>Skills Users Count</li>
</ul>';

$ca = new CityApi();
$ca->debug = true;

echo '<h1>skills_index</h1>';
$ca->json = false;
$results = $ca->skills_index();
$skillid = $results['skills'][0]['id'];
echo '<pre>skillid: ' . $skillid . '</pre>';
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json(json_encode($results));
echo '</pre>';

echo '<hr>';
echo '<h1>skills_show</h1>';
$ca->json = true;
$results = $ca->skills_show($skillid);
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json($results);
echo '</pre>';

echo '<hr>';
echo '<h1>skills_create</h1>';
$args = array('name' => 'Yugo Driver');
$ca->json = false;
$results = $ca->skills_create($args);
$skillid = $results['id'];
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json(json_encode($results));
echo '</pre>';

echo '<hr>';
echo '<h1>skills_destroy</h1>';
$ca->json = true;
$results = $ca->skills_destroy($skillid);
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json($results);
echo '</pre>';

echo '<hr>';
echo '<h1>skills_users_index</h1>';
$ca->json = true;
$skillid = 'Accounting';
//$skillid = 54785; // id or string is OK
$results = $ca->skills_users_index($skillid);
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json($results);
echo '</pre>';

echo '<hr>';
echo '<h1>skills_users_user_ids</h1>';
$ca->json = true;
$skillid = 'Accounting';
//$skillid = 54785; // id or string is OK
$results = $ca->skills_users_user_ids($skillid);
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json($results);
echo '</pre>';

echo '<hr>';
echo '<h1>skills_users_count</h1>';
$ca->json = true;
$skillid = 'Accounting';
//$skillid = 54785; // id or string is OK
$results = $ca->skills_users_count($skillid);
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json($results);
echo '</pre>';

?>
</body>
</html>
