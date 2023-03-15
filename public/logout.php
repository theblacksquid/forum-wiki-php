<?php

session_start();

unset($_SESSION['authToken']);

header('Location: /');

?>
