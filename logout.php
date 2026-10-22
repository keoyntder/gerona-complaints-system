<?php
session_start();
session_destroy();
echo "<script>top.location.href = 'index.php';</script>";
exit;