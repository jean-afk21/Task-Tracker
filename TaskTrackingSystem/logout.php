<?php
session_start();
session_destroy();
header("Location: /TaskTrackingSystem/index.php");
exit();