<?php

require_once("../app/DBTools.php");
require_once("../app/SessionManager.php");
require_once("../app/AccountManager.php");

$session = new SessionManager();

if(isset($_POST['user']) && isset($_POST['password'])){
    $useremail = $_POST['user'];
    $userpassword = $_POST['password'];
    $config = require("../config/config.php");
    $dbh = DBTools::connectToDatabase($config);

    $isValid = AccountManager::isValidEMailAddr($useremail);
    $sql = "SELECT account_id, username FROM Account WHERE " . ($isValid ? "email = :email;" : "username = :username;");
    $res = DBTools::fetchAssoc($dbh, $sql, array(($isValid ? ':email' : ':username') => $useremail));
    if(!empty($res)){
        if($session->login($dbh, $res['account_id'], $userpassword)){
            echo json_encode(array("status" => "true", "username" => $res['username']));
            return;
        }
    }
}
echo json_encode(array("status" => "false"));

?>
