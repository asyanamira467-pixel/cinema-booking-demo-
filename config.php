<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes.php';

header('Content-Type: application/json');

$query = $_GET['q'] ?? '';
$results = [];

if (!empty(trim($query))) {
  $film = new Film();
  $results = $film->searchWithPrice(trim($query), 5);
}

echo json_encode($results, JSON_UNESCAPED_UNICODE);
?>
