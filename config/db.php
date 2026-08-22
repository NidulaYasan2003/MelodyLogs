<?php
/**
 * MelodyLogs - Database Connection
 */

require_once __DIR__ . '/env.php';

// Read configuration from environment variables via getenv() / env()
$dbHost    = getenv('DB_HOST') ?: env('DB_HOST', '127.0.0.1');
$dbPort    = getenv('DB_PORT') ?: env('DB_PORT', '3306');
$dbName    = getenv('DB_NAME') ?: env('DB_NAME', 'melodylogs_db');
$dbUser    = getenv('DB_USER') ?: env('DB_USER', 'root');
$dbPass    = getenv('DB_PASS') !== false ? getenv('DB_PASS') : env('DB_PASS', '');
$dbCharset = getenv('DB_CHARSET') ?: env('DB_CHARSET', 'utf8mb4');

$dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset={$dbCharset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$dbCharset} COLLATE utf8mb4_unicode_ci"
];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (PDOException $e) {
    // If the database does not exist yet or connection fails, show a clean message
    $isDev = (getenv('APP_ENV') ?: env('APP_ENV', 'development')) === 'development';
    
    if ($isDev) {
        $errorMessage = $e->getMessage();
        $errorDetails = "
        <!DOCTYPE html>
        <html lang='en' data-bs-theme='dark'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Database Connection Error - MelodyLogs</title>
            <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
            <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css'>
            <style>
                body { background-color: #0b0f19; color: #e2e8f0; font-family: system-ui, -apple-system, sans-serif; }
                .error-card { background: rgba(26, 32, 44, 0.95); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 1rem; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
                .code-block { background: #070a10; color: #f87171; border-radius: 0.5rem; padding: 1rem; font-family: monospace; }
            </style>
        </head>
        <body class='d-flex align-items-center min-vh-100 py-5'>
            <div class='container'>
                <div class='row justify-content-center'>
                    <div class='col-lg-8'>
                        <div class='error-card p-4 p-md-5'>
                            <div class='d-flex align-items-center mb-4'>
                                <i class='bi bi-exclamation-triangle-fill text-danger fs-1 me-3'></i>
                                <div>
                                    <h2 class='h4 mb-1 text-white'>Database Connection Failed</h2>
                                    <p class='text-secondary mb-0'>MelodyLogs could not connect to MySQL server.</p>
                                </div>
                            </div>

                            <div class='mb-4'>
                                <label class='form-label fw-bold text-white small text-uppercase'>Error Details</label>
                                <div class='code-block'>{$errorMessage}</div>
                            </div>

                            <div class='mb-4'>
                                <h5 class='text-white h6'>Quick Troubleshooting Steps:</h5>
                                <ul class='text-secondary small'>
                                    <li>Ensure MySQL server is running (e.g. start Apache & MySQL in <strong>XAMPP Control Panel</strong>).</li>
                                    <li>Create database <code>melodylogs_db</code> and import <code>schema.sql</code> into MySQL.</li>
                                    <li>Check your database credentials in your <code>.env</code> file.</li>
                                </ul>
                            </div>

                            <div class='d-flex gap-2'>
                                <button onclick='window.location.reload()' class='btn btn-primary btn-sm px-3'>
                                    <i class='bi bi-arrow-clockwise me-1'></i> Retry Connection
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>";
        die($errorDetails);
    } else {
        error_log("Database Error: " . $e->getMessage());
        die("An unexpected database error occurred. Please contact the administrator.");
    }
}
