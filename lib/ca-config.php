<?php
/** 
 * File:       ca-config.php
 *
 * @author John Roberts <john@john-roberts.net> 
 * @version 0.4
 *
 */

// API initialization portion of the CityApi class
//
// Modify this file to include your API key and user token.
//
// You can also configure debug message output handling. 
//

define("APIKEY", "Your City API key here"); // The City API key to use by default
define("USERTOKEN", "Your user token here"); // The City API user token by default

// Called by the CityApi class to output a debug message.
// Allows you to configure the debug message behavior.  By default it emits a paragraph element 
// with class "ca-debug" containing the message, and writes the message to the PHP error log.
function ca_debug_message($msg)
{
    echo '<p class="ca-debug">' . $msg . '<p>';
    error_log($msg);
}

?>
