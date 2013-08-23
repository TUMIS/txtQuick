<?php
if(($_SERVER['PHP_AUTH_USER'] !== CFG_USER) && ($_SERVER['PHP_AUTH_PW'] !== CFG_PASS))
{
  //Send headers to cause a browser to request
  //username and password from user
  header("WWW-Authenticate: " .
  "Basic realm=\"txtQuick\"");
    
  header("HTTP/1.0 401 Unauthorized");

  print("Unable to display.");

  die();
}

