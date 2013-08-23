<?php
include_once './lib.php';
include_once APP_ROOT . '/app/auth.php';

$offset = filter_var($_GET['o'], FILTER_SANITIZE_NUMBER_INT);

TxtQuick::get(1,$offset);
