<?php
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();
$cats = $db->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
echo "CATEGORIES:\n";
foreach($cats as $c) echo $c['id'] . ": " . $c['name'] . "\n";
