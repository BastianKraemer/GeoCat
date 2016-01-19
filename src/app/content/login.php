<?php

require_once("../DBTools.php");
require_once("../SessionManager.php");
require_once("../AccountManager.php");

$session = new SessionManager();
$account = new AccountManager();

if(isset($_REQUEST['useremail']) && isset($_REQUEST['userpassword'])):
    $useremail = $_REQUEST['useremail'];
    $userpassword = $_REQUEST['userpassword'];
    $config = require_once("../../config/config.php");
    $dbh = DBTools::connectToDatabase($config);
    $res = DBTools::fetchAll($dbh, "select * from account where email = :email;", array(':email' => $useremail));
    foreach ($res as $row):
        if($session->login($dbh, $row['account_id'], $userpassword)):
            if(isset($_REQUEST['rememberme']) && $_REQUEST['rememberme'] == 'on'):
                $session->createCookie("GEOCAT", array("email" => $useremail, "pass" => $account->getPBKDF2Hash($userpassword)[0]), (60*60*24*30));
            endif;
            echo json_encode(array("login" => true));
        endif;
        return;
    endforeach;
endif;
echo json_encode(array("login" => false));

?>