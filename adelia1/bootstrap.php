<?php
/* bootstrap.php — shared by all entry points */
declare(strict_types=1);

/* ── load credentials if present ───────────────────────── */
$CFG = [
    'driver'   => 'sqlite',           // default
    'dsn'      => 'sqlite:' . __DIR__ . '/board.sqlite',
    'user'     => null,
    'pass'     => null,
    'dev'      => false,              // set true → drop tables every run
];

$envFile = __DIR__ . '/.env.php';
if (is_file($envFile)) {
    /** @noinspection PhpIncludeInspection */
    $ENV = include $envFile;          // returns array
    $CFG = array_replace($CFG, $ENV);
}

/* ── create PDO ─────────────────────────────────────────── */
try {
    $pdo = new PDO($CFG['dsn'], $CFG['user'], $CFG['pass'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    if ($CFG['driver'] === 'mysql') {
        die("MariaDB connection failed: " . $e->getMessage());
    }
    // fallback to sqlite if mysql creds not yet supplied
    $CFG['driver'] = 'sqlite';
    $CFG['dsn']    = 'sqlite:' . __DIR__ . '/board.sqlite';
    $pdo = new PDO($CFG['dsn'], null, null, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
}

/* ── auto‑create / migrate schema ───────────────────────── */
$dbDriver = $CFG['driver'];

$tableDDL = $dbDriver === 'mysql'
? /* MySQL flavour */
<<<SQL
CREATE TABLE IF NOT EXISTS posts (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  parent     INT UNSIGNED NULL,
  name       VARCHAR(64)  NOT NULL,
  subject    VARCHAR(100),
  body       TEXT NOT NULL,
  image      VARCHAR(255),
  thumb      VARCHAR(255),
  created_at INT UNSIGNED NOT NULL,
  bumped_at  INT UNSIGNED NOT NULL,
  ip         VARBINARY(16) NOT NULL,
  KEY idx_parent          (parent),
  KEY idx_parent_bumped   (parent, bumped_at DESC),
  KEY idx_created         (created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL
: /* SQLite flavour (unchanged) */
<<<SQL
CREATE TABLE IF NOT EXISTS posts (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  parent     INTEGER,
  name       TEXT NOT NULL,
  subject    TEXT,
  body       TEXT NOT NULL,
  image      TEXT,
  thumb      TEXT,
  created_at INTEGER NOT NULL,
  bumped_at  INTEGER NOT NULL,
  ip         TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_parent         ON posts(parent);
CREATE INDEX IF NOT EXISTS idx_parent_bumped  ON posts(parent, bumped_at DESC);
CREATE INDEX IF NOT EXISTS idx_created        ON posts(created_at DESC);
SQL;

if ($CFG['dev']) {
    $pdo->exec("DROP TABLE IF EXISTS posts");
}
foreach (array_filter(explode(';', $tableDDL)) as $sql) {
    if (trim($sql) !== '') $pdo->exec($sql);
}

/* ── export helper that every file can call ────────────── */
function db(): PDO
{
    global $pdo;
    return $pdo;
}
