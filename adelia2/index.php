<?php
declare(strict_types=1);

/*──────────────────────────────────────────────────────────*/
/*   Adelia1 – single‑file image board (2025‑06‑20)          */
/*   Hardened edition – production defaults                  */
/*   Requires PHP 8.4+ (works even without mbstring)         */
/*──────────────────────────────────────────────────────────*/

/*────────────── Configuration ─────────────────────────────*/
const
    /*‑‑‑ Debugging & logging switches (all OFF in prod) ‑‑‑*/
    DEBUG_DISPLAY = false,                      // show errors in browser
    DEBUG_LOG     = false,                      // write errors to error.txt
    LOG_FILE      = __DIR__.'/error.txt',

    /*‑‑‑ Board settings ‑‑‑*/
    DB_FILE   = __DIR__.'/board.sqlite',
    DIR_ORIG  = __DIR__.'/uploads',
    DIR_THUMB = __DIR__.'/uploads/thumb',
    THUMB_W   = 255,
    THUMB_H   = 144,
    MAX_SIZE  = 5 * 1024 * 1024,   // 5 MiB per upload
    MAX_BODY  = 10_000,            // max comment length
    MAX_SUBJ  = 100,
    MAX_NAME  = 35,
    PAGE_SIZE = 15,
    COOLDOWN  = 10,                // seconds between posts per IP
    CSRF_TOKEN = '_csrf',
    SESSION_OPTS = ['cookie_httponly'=>true, 'cookie_samesite'=>'Strict'],
    MOD_HASH = '$2b$10$E8N1k0703L7hxQ/H6lkV/u3FFE7u3.HtwgTtkOoSeWlKgN39hd7Qe'; // "8899"

/*──────── Production‑grade PHP setup ──────────────────────*/
error_reporting(E_ALL);
ini_set('display_errors',        DEBUG_DISPLAY ? '1' : '0');
ini_set('display_startup_errors',DEBUG_DISPLAY ? '1' : '0');
ini_set('expose_php',            '0');                 // no “X‑Powered‑By”
if (DEBUG_LOG) {
    ini_set('log_errors', '1');
    ini_set('error_log', LOG_FILE);
    if (!is_file(LOG_FILE)) touch(LOG_FILE);
    chmod(LOG_FILE, 0644);
} else {
    ini_set('log_errors', '0');
}

/*───── mbstring fallback (ASCII‑only) ─────*/
if (!function_exists('mb_substr')) {
    function mb_substr(string $s,int $start,?int $len=null):string{
        return $len===null?substr($s,$start):substr($s,$start,$len);
    }
}
if (!function_exists('mb_strlen')) {
    function mb_strlen(string $s):int{ return strlen($s); }
}

/*────────────── Compatibility check ───────*/
if (PHP_VERSION_ID < 80400) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    exit("PHP 8.4+ required – you are running ".PHP_VERSION."\n");
}

/*────────────── Environment ───────────────*/
$secureCookie = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_start(SESSION_OPTS + ['cookie_secure'=>$secureCookie]);

/* helper: HTML escape */
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
/* helper: m/d/y */
function d8(int $t): string   { return date('m/d/y', $t); }

/**
 * Collapse excessive white‑space so posts render compactly.
 *   • Normalise CRLF/CR ➝ LF
 *   • ≥ 2 consecutive newlines ➝ one newline  (=> one <br>)
 *   • Runs of spaces/TABs ➝ single space
 */
function normalizeWhitespace(string $s): string
{
    $s = str_replace(["\r\n", "\r"], "\n", $s);
    $s = preg_replace('/\n{2,}/', "\n", $s);   // *** tightened here ***
    $s = preg_replace('/[ \t]{2,}/', ' ', $s);
    return trim($s);
}

/* CSRF helpers */
function csrfToken(): string
{
    return $_SESSION[CSRF_TOKEN] ??= bin2hex(random_bytes(16));
}
function verifyCsrf(?string $token):void
{
    if(!$token||!hash_equals($_SESSION[CSRF_TOKEN]??'',$token)){
        http_response_code(400); exit('bad csrf token');
    }
}

/* Directory creation with local umask restore */
function ensureDir(string $dir):void
{
    if(!is_dir($dir)){
        $old=umask(0); mkdir($dir,0775,true); umask($old);
    }
}

/* Database (WAL only once) */
function db():PDO
{
    static $pdo;
    if(!$pdo){
        $pdo=new PDO('sqlite:'.DB_FILE,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        $pdo->exec('PRAGMA journal_mode = WAL');
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec(<<<SQL
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
SQL);
    }
    return $pdo;
}

/* Anti‑flood: simple per‑IP cooldown */
function canPost(string $ip):bool
{
    $pdo=db();
    $stmt=$pdo->prepare('SELECT created_at FROM posts WHERE ip=? ORDER BY id DESC LIMIT 1');
    $stmt->execute([$ip]); $last=$stmt->fetchColumn();
    return !$last || (time()-(int)$last>=COOLDOWN);
}

/* Thumbnail creator – with memory guard & EXIF strip */
function makeThumb(string $src,string $dst):void
{
    [$w,$h,$type]=getimagesize($src)?:[0,0,0];
    if(!$w||!$h) return;
    $create=match($type){
        IMAGETYPE_JPEG=>'imagecreatefromjpeg',
        IMAGETYPE_PNG =>'imagecreatefrompng',
        IMAGETYPE_GIF =>'imagecreatefromgif',
        IMAGETYPE_WEBP=>function_exists('imagecreatefromwebp')?'imagecreatefromwebp':null,
        default=>null,
    };
    if(!$create) return;
    if($w*$h>40_000_000) return;              // refuse >40 MP

    $srcImg=$create($src); if(!$srcImg) return;
    $ratio=min(THUMB_W/$w,THUMB_H/$h);
    $nw=(int)round($w*$ratio); $nh=(int)round($h*$ratio);

    $thumb=imagecreatetruecolor($nw,$nh);
    imagecopyresampled($thumb,$srcImg,0,0,0,0,$nw,$nh,$w,$h);
    imagepng($thumb,$dst,7);                  // strip metadata
    imagedestroy($srcImg); imagedestroy($thumb);
}

/* Secure image upload – returns [imagePath, thumbPath] or [null, null] */
function handleUpload(array $file):array
{
    if($file['error']!==UPLOAD_ERR_OK){ http_response_code(400); exit('upload failed'); }
    if($file['size']>MAX_SIZE){ http_response_code(400); exit('file too large'); }

    /* first quick sniff – 256 B */
    $data=file_get_contents($file['tmp_name'],false,null,0,256);
    $finfo=new finfo(FILEINFO_MIME_TYPE);
    $mime=$finfo->buffer($data);
    $extMap=[
        'image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'
    ];
    $ext=$extMap[$mime]??null;
    if(!$ext){ http_response_code(400); exit('unsupported type'); }

    /* unique 12‑hex id */
    do{ $id=bin2hex(random_bytes(6)); $image="uploads/$id.$ext"; }
    while(file_exists(__DIR__.'/'.$image));
    $thumb="uploads/thumb/$id.png";

    move_uploaded_file($file['tmp_name'],__DIR__.'/'.$image);
    chmod(__DIR__.'/'.$image,0644);

    /* second full‑file sniff */
    $mime2=$finfo->file(__DIR__.'/'.$image);
    if(!isset($extMap[$mime2])){ @unlink(__DIR__.'/'.$image); http_response_code(400); exit('unsupported type'); }

    makeThumb(__DIR__.'/'.$image,__DIR__.'/'.$thumb);
    chmod(__DIR__.'/'.$thumb,0644);
    return[$image,$thumb];
}

/*────────────── Bootstrap directories ─────────────────────*/
ensureDir(DIR_ORIG); ensureDir(DIR_THUMB);

/*────────────── ROUTING ───────────────────────────────────*/
$action=$_POST['action']??null;

/*────────── Moderator hard‑delete (POST) ──────────*/
if($action==='mod_delete'&&isset($_POST['id'],$_POST['pw'])){
    verifyCsrf($_POST[CSRF_TOKEN]??null);
    if(!password_verify($_POST['pw'],MOD_HASH)){ http_response_code(403); exit('bad password'); }
    $id=(int)$_POST['id']; $pdo=db();
    $row=$pdo->prepare('SELECT image, thumb FROM posts WHERE id=?'); $row->execute([$id]);
    if($p=$row->fetch(PDO::FETCH_ASSOC)){
        if($p['image']) @unlink(__DIR__.'/'.$p['image']);
        if($p['thumb']) @unlink(__DIR__.'/'.$p['thumb']);
        $pdo->prepare('UPDATE posts SET body="(post deleted by moderator)",image=NULL,thumb=NULL WHERE id=?')
            ->execute([$id]);
    }
    /* safe local redirect only */
    $ret=$_POST['return']??'./';
    if(!preg_match('{^[\w\./\-\?=&#]+$}',$ret)||str_contains($ret,"\n")||str_contains($ret,"\r")||str_starts_with($ret,'http')){
        $ret='./';
    }
    header('Location: '.$ret); exit;
}

/*────────── New topic / reply (POST) ─────────────*/
if($action==='post'){
    verifyCsrf($_POST[CSRF_TOKEN]??null);
    $ip=$_SERVER['REMOTE_ADDR']??'0.0.0.0';
    if(!canPost($ip)){ http_response_code(429); exit('slow down'); }

    $parent = isset($_POST['parent'])&&ctype_digit($_POST['parent']) ? (int)$_POST['parent'] : null;
    $name   = mb_substr(trim($_POST['name']),0,MAX_NAME);
    $subject= mb_substr(trim($_POST['subject']??''),0,MAX_SUBJ);

    /* tighten whitespace BEFORE length clip so MAX_BODY is final */
    $body   = normalizeWhitespace(trim($_POST['body']??''));
    $body   = mb_substr($body,0,MAX_BODY);
    if($body===''){ http_response_code(400); exit('empty body'); }

    $now=time(); $image=$thumb=null;
    if($parent===null && !empty($_FILES['file']['tmp_name']??'')){
        [$image,$thumb]=handleUpload($_FILES['file']);
    }

    $pdo=db();
    $pdo->prepare('INSERT INTO posts(parent,name,subject,body,image,thumb,created_at,bumped_at,ip)
                   VALUES(?,?,?,?,?,?,?,?,?)')
        ->execute([$parent,$name,$subject,$body,$image,$thumb,$now,$now,$ip]);
    if($parent){ $pdo->prepare('UPDATE posts SET bumped_at=? WHERE id=?')->execute([$now,$parent]); }

    header('Location: '.($parent?"?thread=$parent":'./')); exit;
}

/*────────────── VIEW (index or thread) ────────────────────*/
$tid  = isset($_GET['thread'])&&ctype_digit($_GET['thread']) ? (int)$_GET['thread'] : null;
$page = isset($_GET['page'])  &&ctype_digit($_GET['page'])   ? max(1,(int)$_GET['page']) : 1;
$pdo  = db();

if($tid){                               /* Thread view with pagination */
    $stmt=$pdo->prepare('SELECT COUNT(*) FROM posts WHERE parent=?'); $stmt->execute([$tid]);
    $totalReplies=(int)$stmt->fetchColumn(); $pages=max(1,(int)ceil($totalReplies/PAGE_SIZE));
    $offset=($page-1)*PAGE_SIZE;

    $stmt=$pdo->prepare('SELECT * FROM posts WHERE id=? OR parent=? ORDER BY id LIMIT ? OFFSET ?');
    $stmt->execute([$tid,$tid,PAGE_SIZE,$offset]);
    $posts=$stmt->fetchAll(PDO::FETCH_ASSOC);
    if(!$posts){ http_response_code(404); exit('Thread not found'); }
}else{                                   /* Index view with pagination */
    $stmt=$pdo->query('SELECT COUNT(*) FROM posts WHERE parent IS NULL');
    $totalThreads=(int)$stmt->fetchColumn(); $pages=max(1,(int)ceil($totalThreads/PAGE_SIZE));
    $offset=($page-1)*PAGE_SIZE;

    $stmt=$pdo->prepare('SELECT * FROM posts WHERE parent IS NULL ORDER BY bumped_at DESC LIMIT ? OFFSET ?');
    $stmt->execute([PAGE_SIZE,$offset]);
    $posts=$stmt->fetchAll(PDO::FETCH_ASSOC);
}

/*────────────── HTML OUTPUT ───────────────────────────────*/
header('Content-Type: text/html; charset=utf-8');
header(
    "Content-Security-Policy: ".
    "default-src 'self'; ".
    "script-src 'self' 'unsafe-inline'; ".
    "style-src  'self' 'unsafe-inline'; ".
    "img-src    'self' data:; ".
    "form-action 'self'; ".
    "frame-ancestors 'none'; ".
    "object-src 'none'"
);
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header('Permissions-Policy: geolocation=(), microphone=()');
header('X-Permitted-Cross-Domain-Policies: none');
?>
<!doctype html><html><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>/b/ - Random</title>

<link rel="stylesheet" href="/stylesheets/style.css?v=0" data-style="default">
<link rel="stylesheet" href="/stylesheets/yotsuba.css?v=0" data-style="yotsuba" disabled>
<link rel="stylesheet" href="/stylesheets/miku.css?v=0"    data-style="miku"    disabled>
<link rel="stylesheet" href="/stylesheets/1.css?v=0"       data-style="1"       disabled>
<link rel="stylesheet" href="/stylesheets/2.css?v=0"       data-style="2"       disabled>
<link rel="stylesheet" href="/stylesheets/3.css?v=0"       data-style="3"       disabled>
<link rel="stylesheet" href="/stylesheets/4.css?v=0"       data-style="4"       disabled>
<link rel="stylesheet" href="/stylesheets/font-awesome/css/font-awesome.min.css?v=0">

<script>
const active_page = <?= $tid?'"thread"':'"index"' ?>,
      board_name   = 'b',
      thread_id    = <?= $tid ?: 'null' ?>,
      csrf         = '<?= csrfToken() ?>';
function swapStyle(n){
  document.querySelectorAll('link[data-style]').forEach(l=>{
    if(l.dataset.style==='default') return;
    l.disabled = (l.dataset.style!==n);
  });
  document.body.dataset.stylesheet=n;
}
function modDel(id){
  const pw = prompt('moderator password:');
  if(!pw) return;
  const f=document.createElement('form');
  f.method='post'; f.style.display='none';
  f.innerHTML=
    `<input name="action" value="mod_delete">`+
    `<input name="id"     value="${id}">`    +
    `<input name="pw"     value="${pw}">`    +
    `<input name="<?= CSRF_TOKEN ?>" value="${csrf}">`+
    `<input name="return" value="${location.href}">`;
  document.body.appendChild(f); f.submit();
}
</script>
</head>
<body class="8chan vichan <?= $tid?'active-thread':'active-index' ?>" data-stylesheet="default">

<!-- Theme switcher (hidden by default) -->
<div class="styles" style="display:none">
  <a href="javascript:swapStyle('default')" class="selected">[Default]</a>
  <a href="javascript:swapStyle('yotsuba')">[Yotsuba]</a>
  <a href="javascript:swapStyle('miku')">[Miku]</a>
  <a href="javascript:swapStyle('1')">[1]</a>
  <a href="javascript:swapStyle('2')">[2]</a>
  <a href="javascript:swapStyle('3')">[3]</a>
  <a href="javascript:swapStyle('4')">[4]</a>
</div>

<?php if($tid): /*──────────────── Thread page ─────────────*/ ?>
<header><h1>/b/ - Random</h1></header>
<div class="banner">
  Posting mode: Reply
  <a class="unimportant" href="./">[Return]</a>
  <a class="unimportant" href="#bottom">[Bottom]</a>
</div>

<!-- Reply form -->
<form method="post" enctype="multipart/form-data">
  <input type="hidden" name="<?= CSRF_TOKEN ?>" value="<?= csrfToken() ?>">
  <input type="hidden" name="action" value="post">
  <input type="hidden" name="parent" value="<?= $tid ?>">
  <table>
    <tr><th>Name</th><td><input name="name" size="25" maxlength="<?= MAX_NAME ?>" required></td></tr>
    <tr><th>Comment</th><td>
      <textarea name="body" rows="5" cols="35" maxlength="<?= MAX_BODY ?>" required></textarea>
      <input style="margin-left:2px" type="submit" value="New Reply">
    </td></tr>
  </table>
</form><hr>

<div class="thread" id="thread_<?= $tid ?>">
<?php foreach($posts as $p): ?>
  <?php if(!$p['parent']&&$p['thumb']): ?>
  <div class="files"><div class="file">
    <a href="<?= h($p['image']) ?>" target="_blank">
      <img class="post-image" src="<?= h($p['thumb']) ?>" style="width:<?=THUMB_W?>px;height:auto">
    </a>
  </div></div>
  <?php endif; ?>
  <div class="post <?= $p['parent']?'reply':'op' ?>" id="p<?= $p['id'] ?>">
    <p class="intro">
      <span class="name"><?= h($p['name']) ?></span>
      <time><?= d8((int)$p['created_at']) ?></time>
      <span class="unimportant" style="cursor:pointer" onclick="modDel(<?= $p['id'] ?>)">[&minus;]</span>
    </p>
    <div class="body"><?= nl2br(h(normalizeWhitespace($p['body']))) ?></div>
  </div><br class="clear"><hr>
<?php endforeach; ?>
</div>

<!-- Pagination -->
<div class="pages">
<?php for($i=1;$i<=$pages;$i++): ?>
  <?php if($i===$page): ?><strong>[<?= $i ?>]</strong>
  <?php else: ?><a href="?thread=<?= $tid ?>&page=<?= $i ?>">[<?= $i ?>]</a>
  <?php endif; ?>
<?php endfor; ?>
</div>

<a name="bottom"></a>

<?php else: /*──────────────── Index page ───────────────*/ ?>

<!-- New thread form -->
<form method="post" enctype="multipart/form-data">
  <input type="hidden" name="<?= CSRF_TOKEN ?>" value="<?= csrfToken() ?>">
  <input type="hidden" name="action" value="post">
  <table>
    <tr><th>Name</th><td><input name="name" size="25" maxlength="<?= MAX_NAME ?>" required></td></tr>
    <tr><th>Subject</th><td><input name="subject" size="25" maxlength="<?= MAX_SUBJ ?>"></td></tr>
    <tr><th>Comment</th><td>
      <textarea name="body" rows="5" cols="35" maxlength="<?= MAX_BODY ?>" required></textarea>
      <input style="margin-left:2px" type="submit" value="New Topic">
    </td></tr>
    <tr><th>File</th><td><input type="file" name="file"></td></tr>
  </table>
</form><hr>

<?php foreach($posts as $op): ?>
  <div class="thread" id="thread_<?= $op['id'] ?>">
    <?php if($op['thumb']): ?>
    <div class="files"><div class="file">
      <a href="<?= h($op['image']) ?>" target="_blank">
        <img class="post-image" src="<?= h($op['thumb']) ?>" style="width:<?=THUMB_W?>px;height:auto">
      </a>
    </div></div>
    <?php endif; ?>
    <div class="post op" id="op_<?= $op['id'] ?>">
      <p class="intro">
        <span class="name"><?= h($op['name']) ?></span>
        <time><?= d8((int)$op['bumped_at']) ?></time>
        <a href="?thread=<?= $op['id'] ?>">[Reply]</a>
      </p>
      <div class="body"><?= nl2br(h(normalizeWhitespace($op['body']))) ?></div>
    </div>
<?php
  $stmt=$pdo->prepare('SELECT * FROM posts WHERE parent=? ORDER BY id DESC LIMIT 3');
  $stmt->execute([$op['id']]);
  $replies=array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
  foreach($replies as $rp):
?>
    <div class="post reply preview" id="p<?= $rp['id'] ?>">
      <p class="intro"><span class="name"><?= h($rp['name']) ?></span>
        <time><?= d8((int)$rp['created_at']) ?></time></p>
      <div class="body"><?= nl2br(h(normalizeWhitespace($rp['body']))) ?></div>
    </div>
<?php endforeach; ?>
    <br class="clear"><hr>
  </div>
<?php endforeach; ?>

<!-- Pagination -->
<div class="pages">
<?php for($i=1;$i<=$pages;$i++): ?>
  <?php if($i===$page): ?><strong>[<?= $i ?>]</strong>
  <?php else: ?><a href="?page=<?= $i ?>">[<?= $i ?>]</a>
  <?php endif; ?>
<?php endfor; ?>
</div>

<?php endif; /* end index/thread split */ ?>

<footer></footer>

<script src="/js/hide-form.js"></script>
<script src="/js/inline-expanding.js"></script>
<script src="/js/style.js"></script>
</body></html>
