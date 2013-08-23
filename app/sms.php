<?php
include_once './lib.php';

$t = new TxtQuick_SMS( $_POST ); 
$t->process();
