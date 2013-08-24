<?php
include_once './lib.php';
include_once APP_ROOT . '/app/auth.php';

$reply = TxtQuick_Utils::cleaner($_POST['replytext']);

if ( TxtQuick_SMS::update_reply($reply) )
{
 print'ok';
}
