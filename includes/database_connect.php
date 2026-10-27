<?php
if (!isset($GLOBALS['conn']) || !$GLOBALS['conn']) {
    $conn = false;

    // 1. Check for Standard Cloud Environment Variables (Railway, Render, Clever Cloud, Docker, etc.)
    $env_host = getenv('DB_HOST') ?: getenv('MYSQLHOST') ?: (isset($_ENV['DB_HOST']) ? $_ENV['DB_HOST'] : (isset($_ENV['MYSQLHOST']) ? $_ENV['MYSQLHOST'] : null));
    $env_user = getenv('DB_USER') ?: getenv('MYSQLUSER') ?: (isset($_ENV['DB_USER']) ? $_ENV['DB_USER'] : (isset($_ENV['MYSQLUSER']) ? $_ENV['MYSQLUSER'] : null));
    $env_pass = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : (getenv('MYSQLPASSWORD') !== false ? getenv('MYSQLPASSWORD') : (isset($_ENV['DB_PASSWORD']) ? $_ENV['DB_PASSWORD'] : (isset($_ENV['MYSQLPASSWORD']) ? $_ENV['MYSQLPASSWORD'] : null)));
    $env_db   = getenv('DB_NAME') ?: getenv('MYSQLDATABASE') ?: (isset($_ENV['DB_NAME']) ? $_ENV['DB_NAME'] : (isset($_ENV['MYSQLDATABASE']) ? $_ENV['MYSQLDATABASE'] : null));
    $env_port = getenv('DB_PORT') ?: getenv('MYSQLPORT') ?: (isset($_ENV['DB_PORT']) ? $_ENV['DB_PORT'] : (isset($_ENV['MYSQLPORT']) ? $_ENV['MYSQLPORT'] : null));

    // Also support connection URL like mysql://user:pass@host:port/dbname (Railway / Render / Heroku)
    $db_url = getenv('DATABASE_URL') ?: getenv('MYSQL_URL') ?: (isset($_ENV['DATABASE_URL']) ? $_ENV['DATABASE_URL'] : null);
    if ($db_url && strpos($db_url, 'mysql://') === 0) {
        $parsed = parse_url($db_url);
        if ($parsed) {
            $env_host = isset($parsed['host']) ? $parsed['host'] : $env_host;
            $env_user = isset($parsed['user']) ? $parsed['user'] : $env_user;
            $env_pass = isset($parsed['pass']) ? $parsed['pass'] : $env_pass;
            $env_port = isset($parsed['port']) ? $parsed['port'] : $env_port;
            if (isset($parsed['path'])) {
                $env_db = ltrim($parsed['path'], '/');
            }
        }
    }

    if ($env_host) {
        $port = $env_port ? (int)$env_port : 3306;
        $user = $env_user ?: 'root';
        $pass = $env_pass !== null ? $env_pass : '';
        $db   = $env_db ?: 'pglife';
        $conn = @mysqli_connect($env_host, $user, $pass, $db, $port);
    }

    // 2. Fallback to Local XAMPP Environment (Auto-detect port 3306 or 3307)
    if (!$conn) {
        $host = '127.0.0.1';
        $db = 'pglife';
        
        $cached_port = isset($_SESSION['db_working_port']) ? $_SESSION['db_working_port'] : null;
        $cached_pass = isset($_SESSION['db_working_pass']) ? $_SESSION['db_working_pass'] : null;
        
        if ($cached_port !== null && $cached_pass !== null) {
            $conn = @mysqli_connect($host, "root", $cached_pass, $db, (int)$cached_port);
        }
        
        if (!$conn) {
            $configs = array(
                array('port' => 3307, 'pass' => ''),
                array('port' => 3306, 'pass' => ''),
                array('port' => 3306, 'pass' => 'Sachin01!'),
                array('port' => 3306, 'pass' => 'password'),
                array('port' => 3307, 'pass' => 'Sachin01!'),
                array('port' => 3307, 'pass' => 'password')
            );
            
            foreach ($configs as $cfg) {
                $conn = @mysqli_connect($host, "root", $cfg['pass'], $db, (int)$cfg['port']);
                if ($conn) {
                    if (session_status() === PHP_SESSION_ACTIVE) {
                        $_SESSION['db_working_port'] = $cfg['port'];
                        $_SESSION['db_working_pass'] = $cfg['pass'];
                    }
                    break;
                }
            }
        }
    }
    
    if (!$conn) {
        echo "Failed to connect to MySQL! Please contact the admin.";
        return;
    }
    
    $GLOBALS['conn'] = $conn;
} else {
    $conn = $GLOBALS['conn'];
}
