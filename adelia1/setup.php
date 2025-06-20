<?php
declare(strict_types=1);
if (is_file(__DIR__.'/.env.php')) {
    header('Location: ./'); exit;
}
?>
<!doctype html><html><head><meta charset="utf-8">
<title>TinyBoard – Setup</title></head><body>
<h2>TinyBoard · MariaDB Setup</h2>
<form method="post">
<label>DB host : <input name="host" value="localhost"></label><br>
<label>DB name : <input name="name"></label><br>
<label>User    : <input name="user"></label><br>
<label>Password: <input type="password" name="pass"></label><br>
<label><input type="checkbox" name="dev"> dev mode (drop tables each start)</label><br>
<button>Save &amp; continue</button>
</form>
<?php
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $host = $_POST['host'] ?? 'localhost';
    $name = $_POST['name'] ?? '';
    $user = $_POST['user'] ?? '';
    $pass = $_POST['pass'] ?? '';
    $dev  = isset($_POST['dev']);
    if ($name==='') die('DB name required');

    $env = [
        'driver' => 'mysql',
        'dsn'    => "mysql:host=$host;dbname=$name;charset=utf8mb4",
        'user'   => $user,
        'pass'   => $pass,
        'dev'    => $dev
    ];
    $code = "<?php\nreturn ".var_export($env,true).";\n";
    file_put_contents(__DIR__.'/.env.php', $code);
    chmod(__DIR__.'/.env.php', 0600);
    header('Location: ./'); exit;
}
?>
</body></html>
