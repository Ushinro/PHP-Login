<?php
require_once 'app/start.php';

$user = new User();
$user->logout();

Redirect::to('index.php');