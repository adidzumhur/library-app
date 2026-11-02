<?php
/**
 * Configuration template.
 *
 * Copy this file to config.php (same folder) and fill in real values.
 * config.php is listed in .gitignore and must NEVER be committed.
 */
return [
    'db' => [
        'host'     => 'localhost',
        'port'     => 3306,
        'name'     => 'library_db',
        'user'     => 'root',
        'password' => '',
    ],
    'jwt' => [
        'secret'     => 'change-me-to-a-long-random-string',
        'expires_in' => 3600, // seconds
    ],
    'external_api' => [
        // Open Library does not require an API key
        'open_library_base_url' => 'https://openlibrary.org',
        'timeout'               => 5, // seconds
    ],
];
