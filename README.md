thecity-admin-php
=================

This PHP project is an API wrapper for TheCity Admin API (api.onthecity.org).

Quick Start:

1. Set your API key and user token in lib/ca-config.php.

2. Simple example

This code gets the number of groups in your City returned as JSON.  
It assumes ca-main.php is in a lib subdirectory.

```php
<?php

require_once 'lib/ca-main.php';

$ca = new CityApi();
$results = $ca->groups_count();
echo $results;

?>
```


```php
<?php

// If the global constants APIKEY and USERTOKEN are not used then they can be set via the constructor.
$apikey = '8d12345d354fcdeb97cb2a7b83561ac654d4ad53';
$usertoken = '5a2a12345c031b0a';
$ca = new CityApi($apikey, $usertoken);
$results = $ca->groups_count();
echo $results;

?>
```

The v1.1 wrapper library covers 100% of The City Admin API.  
The PDF doc is current to v0.4.  For examples of any endpoint calls not yet in the doc,
please refer to the test scripts in the test directory, which covers all endpoint calls. 