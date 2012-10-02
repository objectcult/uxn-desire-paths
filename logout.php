<?php
// forget the user
require_once('_functions.php');
$_SESSION['user']=NULL;
reloadReferrer();

?>