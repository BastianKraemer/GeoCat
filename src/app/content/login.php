<?php

require_once("../DBTools.php");
require_once("../SessionManager.php");
require_once("../AccountManager.php");

if(isset($_REQUEST['useremail']) && isset($_REQUEST['userpassword'])):
    $useremail = $_REQUEST['useremail'];
    $userpassword = $_REQUEST['userpassword'];
    $config = require_once("../../config/config.php");
    $dbh = DBTools::connectToDatabase($config);
    $sql = "select * from \"Account\" where email = :email;";
    $res = DBTools::fetchAll($dbh, $sql, array(':email' => $useremail));
    foreach ($res as $row):
        //if(password_verify($_REQUEST['userpassword'], $row['password'])):
        if(strcmp($userpassword, $row['password']) == 0):
            SessionManager::login($dbh, $row['account_id'], $userpassword);
            echo json_encode(array("login" => true));
            return;
        endif;
    endforeach;
endif;
echo json_encode(array("login" => false));

?>