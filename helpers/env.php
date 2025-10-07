<?php
function loadEnv($path) {
    if (!file_exists($path)) {
        // Silently ignore if .env not found
        return;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; 
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
        }
    }
}

function env($key, $default = null) {
    // Check $_ENV first, then system environment variables
    if (isset($_ENV[$key])) return $_ENV[$key];
    if (getenv($key) !== false) return getenv($key);
    return $default;
}
?>
