<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="css/style.css" />
</style>
<title>Test Churches</title>
</head>
<body>
<?php
require_once 'test-util.php' ; 
require_once dirname(__FILE__) . '/../lib/ca-main.php'; 
echo '<div class="apitest">';
echo '<h1>Test Churches</h1>
<p>Tests:</p>
<ul>
<li>Churches Index</li>
</ul>';

$ca = new CityApi();
$ca->debug = true;

echo '<h1>churches_index</h1>';
$ca->json = false;
$results = $ca->churches_index();
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json(json_encode($results));
echo '</pre>';

?>
</body>
</html>
