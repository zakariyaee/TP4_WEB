<?php
try {
    $db = new PDO('mysql:host=' . getenv('dbhost') . ';dbname=' . getenv('dbname'), getenv('dbuser'), getenv('dbpassword'));
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die('Erreur : ' . $e->getMessage());
}
