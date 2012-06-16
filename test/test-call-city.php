<html>
<body>
<pre>
<h1>Test CityApi::call_city</h1>

<form action="test-call-city.php" method="post">
Verb: <input type="text" name="formVerb" /><br />
URL: <input type="text" name="formURL" width="50" /><br />
<!--JSON Body: <input type="text" name="formJSONBody" width="50" /><br />-->
JSON Body: <textarea rows="4" cols="50" name="formJSONBody"></textarea><br />
<input type="submit" name="formSubmit" value="Submit" />
</form>

<?php

require_once('test-util.php'); 
require_once(dirname(__FILE__) . '/../lib/ca-main.php'); 
try 
{
	if($_POST['formSubmit'] == "Submit") 
	{
		$verb = $_POST['formVerb'];
		$url = $_POST['formURL'];
		$body = $_POST['formJSONBody'];
		$ca = new CityApi();
		$ca->debug = true;
		$results = $ca->call_city($verb, $url, $body);
		
		echo "<h1>$verb$url$body</h1>";
		
		echo '<h2>Results</h2>';
		
		echo '<h3>HTTP Status</h3>';
		echo $ca->get_last_status_code();
		
		echo '<h3>HTTP Response Start Line</h3>';
		echo $ca->get_last_response_start_line();
		
		echo '<h3>HTTP Headers</h3>';
		var_dump($ca->get_last_headers());
		
		echo '<h3>City Results</h3>';
		echo $results;
		echo '<pre>';
		echo format_json($results);
		echo '</pre>';
		
	}
}
catch (Exception $e)  
{
		echo '<h3>Exception</h3>';
		echo '<pre>';
		echo $e->getMessage();
		echo '</pre>';
}

?>

</pre>
</body>
</html>
