<?php

session_start();
session_destroy();
unset($_SESSION['auth']);
unset($_SESSION['rental']);

header('location: ../');
exit;
