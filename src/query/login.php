<?php

require_once("../app/DBTools.php");
require_once("../app/SessionManager.php");
require_once("../app/AccountManager.php");

$session = new SessionManager();
$account = new AccountManager();

if(isset($_REQUEST['useremail']) && isset($_REQUEST['userpassword'])):
    $useremail = $_REQUEST['useremail'];
    $userpassword = $_REQUEST['userpassword'];
    $config = require("../config/config.php");
    $dbh = DBTools::connectToDatabase($config);
    $sql = "SELECT * FROM Account WHERE ";
    $isValid = $account->isValidEMailAddr($useremail);
    $sql = $sql . ($isValid ? "email = :email;" : "username = :username;");
    $res = DBTools::fetchAll($dbh, $sql, array(($isValid ? ':email' : ':username') => $useremail));
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
