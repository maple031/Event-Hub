<?php
// Keep your real Atlas URI private. Do not upload it to GitHub or share screenshots containing it.
function mongo_database() {
    static $database = null;
    if ($database !== null) return $database;

    $uri = getenv('MONGODB_URI');
    if (!$uri) return null;

    try {
        $client = new MongoDB\Client($uri);
        $database = $client->selectDatabase('college_event_db');
        $database->command(['ping' => 1]);
        return $database;
    } catch (Throwable $e) {
        error_log('MongoDB connection error: ' . $e->getMessage());
        return null;
    }
}
