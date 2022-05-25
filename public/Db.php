<?php
if (!defined('YOU_MAY_INCLUDE_ME')) exit;
$dsn = "pgsql:host=localhost;port=5432;dbname=ongevallen;user=ongevallen;password=ongevallen";
$dbh = new PDO($dsn);
return $dbh;