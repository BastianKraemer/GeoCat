<?php

require_once("../app/SessionManager.php");

$session = new SessionManager();

if(isset($_REQUEST["logout"])){
    if($_REQUEST["logout"] == "true"){
        $session->logout();
        echo "true";
    } else {
        echo "false";
    }
    return;
}

?>