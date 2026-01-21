<?php
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=sharma_salon", "root", "");
    echo "CONNECTED TO 127.0.0.1";
} catch (Exception $e) {
    echo "FAILED 127.0.0.1: " . $e->getMessage();
}
