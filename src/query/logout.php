<?php

if(isset($_REQUEST["logout"])){
    if(strcmp($_REQUEST["logout"], "true")){
        $session = new SessionManager();
        $session->logout();
        return;
    }
}

?>