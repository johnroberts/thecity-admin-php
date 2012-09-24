<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="css/style.css" />
</style>
<title>Test Tags</title>
</head>
<body>
<?php
require_once('test-util.php'); 
require_once(dirname(__FILE__) . '/../lib/ca-main.php'); 
echo '<div class="apitest">';
echo '<h1>Test Tags</h1>
<p>Tests:</p>
<ul>
<li>Tags Index</li>
<li>Tags Show</li>
<li>Tags Create</li>
<li>Tags Update</li>
<li>Tags Destroy</li>
<li>Tags Groups Index</li>
</ul>';

$ca = new CityApi();
$ca->debug = true;

echo '<hr>';
echo '<h1>tags_index</h1>';
$ca->json = false;
$results = $ca->tags_index();
$tagid = $results['tags'][0]['id'];
echo '<pre>tagid: ' . $tagid . '</pre>';
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json(json_encode($results));
echo '</pre>';

echo '<hr>';
echo '<h1>tags_show</h1>';
$ca->json = true;
$results = $ca->tags_show($tagid);
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json($results);
echo '</pre>';

echo '<hr>';
echo '<h1>tags_create</h1>';
$args = array('name' => 'Yugo Drivers');
$ca->json = false;
$results = $ca->tags_create($args);
$tagid2 = $results['id'];
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json(json_encode($results));
echo '</pre>';

echo '<hr>';
echo '<h1>tags_update</h1>';
$args = array('name' => 'Yugo Drivers United!!');
$ca->json = true;
$results = $ca->tags_update($tagid2, $args);
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json($results);
echo '</pre>';

echo '<hr>';
echo '<h1>tags_destroy</h1>';
$ca->json = true;
$results = $ca->tags_destroy($tagid2);
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json($results);
echo '</pre>';

echo '<hr>';
echo '<h1>tags_groups_index</h1>';
$ca->json = true;
$results = $ca->tags_groups_index($tagid);
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json($results);
echo '</pre>';

?>
</body>
</html>
