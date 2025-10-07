<?php
// env.php - Reads local .env if present, otherwise uses system environment variables
function loadEnv($path) {
    if (!file_exists($path)) {
        // Silently skip if .env is missing (production will use system env vars)
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
        }
    }
}

function env($key, $default = null) {
    // First check $_ENV, then system environment, then fallback
    if (isset($_ENV[$key])) return $_ENV[$key];
    $val = getenv($key);
    return $val !== false ? $val : $default;
}
?>
