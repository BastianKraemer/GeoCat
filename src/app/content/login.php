<?php

require_once("../DBTools.php");
require_once("../SessionManager.php");
require_once("../AccountManager.php");

$session = new SessionManager();

if(isset($_REQUEST['useremail']) && isset($_REQUEST['userpassword'])):
    $useremail = $_REQUEST['useremail'];
    $userpassword = $_REQUEST['userpassword'];
    $config = require_once("../../config/config.php");
    $dbh = DBTools::connectToDatabase($config);
    $res = DBTools::fetchAll($dbh, "select * from account where email = :email;", array(':email' => $useremail));
    foreach ($res as $row):
        if($session->login($dbh, $row['account_id'], $userpassword) == true):
            echo json_encode(array("login" => true));
            return;
        endif;
    endforeach;
endif;
echo json_encode(array("login" => false));

?>