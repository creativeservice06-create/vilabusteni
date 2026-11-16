<?php
// Întoarce nopțile ocupate, pentru calendar. Folosit de assets/js/calendar.js.
require __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
echo json_encode(['ocupate' => nopti_ocupate()]);
