<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="css/style.css" />
</style>
<title>Test Donations Exports Create</title>
</head>
<body>
<?php
require_once 'test-util.php' ; 
require_once dirname(__FILE__) . '/../lib/ca-main.php'; 
echo '<div class="apitest">';
echo '<h1>Test Donations Exports Create</h1>
<p>Tests:</p>
<ul>
<li>Donations Exports Create</li>
</ul>';

$ca = new CityApi();
$ca->debug = true;

echo '<hr>';
echo '<h1>donations_exports_create</h1>';
$ca->json = true;
$args = NULL;
$results = $ca->donations_exports_create();
echo '<h2>Formatted JSON results: </h2>';
echo '<pre>';
echo format_json($results);
echo '</pre>';

?>
</body>
</html>
