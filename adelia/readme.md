# TinyBoard v0.9 — Single‑file Image‑board Engine

*PHP 8.4 • Last updated 20 June 2025*

* [1 · Introduction](#1-introduction)
* [2 · Quick Start](#2-quick-start)
* [3 · File Layout](#3-file-layout)
* [4 · Configuration Constants](#4-configuration-constants)
* [5 · Feature Overview](#5-feature-overview)
* [6 · Security Model](#6-security-model)
* [7 · Posting Workflow](#7-posting-workflow)
* [8 · Database Schema & Indexes](#8-database-schema--indexes)
* [9 · Front‑end JavaScript](#9-front-end-javascript)
* [10 · Theming & CSS](#10-theming--css)
* [11 · Maintenance Tasks](#11-maintenance-tasks)
* [12 · Extending TinyBoard](#12-extending-tinyboard)
* [13 · FAQ](#13-faq)
* [14 · License & Credits](#14-license--credits)

---

## 1 · Introduction <a id="1-introduction"></a>

**TinyBoard** is a deliberately minimal image‑board platform consisting of:

| Component                | Lines ≈ | Purpose                                    |
| ------------------------ | ------- | ------------------------------------------ |
| `index.php`              | \~600   | routes, DB layer, HTML renderer            |
| `js/hide-form.js`        | 40      | hide/show posting form                     |
| `js/inline-expanding.js` | 60      | click‑to‑expand images                     |
| `js/style.js`            | 50      | theme swapper                              |
| `stylesheets/*.css`      | var.    | visual themes (ported from *vichan*/8chan) |

Despite its size it delivers pagination, CSRF protection, rate‑limiting, secure uploads and WAL‑optimised SQLite, targeting **PHP 8.4+** and using modern language features.

---

## 2 · Quick Start <a id="2-quick-start"></a>

1. **Clone / download**

   ```bash
   git clone https://example.com/tinyboard.git
   cd tinyboard
   ```

2. **Verify PHP**

   ```bash
   php -v
   # PHP 8.4.8 (cli) (built: Jun  9 2025)
   ```

3. **Run local server**

   ```bash
   php -S 127.0.0.1:8000
   ```

4. Visit **[http://127.0.0.1:8000](http://127.0.0.1:8000)**.

5. Default moderator password is **8899**.
   Change it:

   ```bash
   php -r 'echo password_hash("newPass", PASSWORD_DEFAULT).PHP_EOL;'
   ```

   Paste the hash into `MOD_HASH` in `index.php`.

---

## 3 · File Layout <a id="3-file-layout"></a>

| Path                     | Purpose                            |
| ------------------------ | ---------------------------------- |
| `index.php`              | Main application                   |
| `uploads/`               | Original images (auto‑created)     |
| `uploads/thumb/`         | PNG thumbnails                     |
| `js/hide-form.js`        | Hide/show post form                |
| `js/inline-expanding.js` | Inline image expansion             |
| `js/style.js`            | Theme switching                    |
| `stylesheets/*.css`      | Visual themes                      |
| `board.sqlite`           | SQLite 3 DB (created at first run) |

---

## 4 · Configuration Constants <a id="4-configuration-constants"></a>

| Constant            | Default         | Meaning                  |
| ------------------- | --------------- | ------------------------ |
| `DB_FILE`           | `board.sqlite`  | SQLite DB path           |
| `DIR_ORIG`          | `uploads`       | Original uploads         |
| `DIR_THUMB`         | `uploads/thumb` | Thumbnails               |
| `THUMB_W / THUMB_H` | `255 / 144`     | Thumb max size           |
| `MAX_SIZE`          | 5 MiB           | Upload size cap          |
| `PAGE_SIZE`         | 15              | Threads/replies per page |
| `COOLDOWN`          | 10 s            | Per‑IP post interval     |
| `MOD_HASH`          | bcrypt hash     | Moderator password       |

---

## 5 · Feature Overview <a id="5-feature-overview"></a>

* Clean single‑file routing (`action` POST param)
* Index **and** thread pagination (`?page=n`)
* Uploads: JPEG · PNG · GIF · WebP verified by MIME
* Thumbnailing via GD, EXIF stripped, 40 MP guard
* WAL‑mode SQLite + indexes for speed
* CSRF tokens on all mutating requests
* Per‑IP rate‑limit (`COOLDOWN`)
* Theme switcher (6 stock themes)
* Moderator delete (POST + password prompt)

---

## 6 · Security Model <a id="6-security-model"></a>

### 6.1 Upload Pipeline

1. Detect MIME with `finfo`.
2. Enforce `MAX_SIZE`.
3. Generate collision‑free 12‑hex IDs.
4. Reject sources > 40 MP pixels.
5. Store original & stripped‑PNG thumbnail.

### 6.2 Request Hardening

* All writes require CSRF token **and** `POST`.
* Moderator actions verified via `password_verify()`.
* Per‑IP cooldown thwarts flood spam.

### 6.3 Suggested Add‑ons

* Duplicate‑image SHA‑256 blocking
* Tripcodes (`name#secret`)
* `Strict‑Transport‑Security` when behind HTTPS

---

## 7 · Posting Workflow <a id="7-posting-workflow"></a>

### New thread

1. JS hides *New Topic* form.
2. User clicks **Start a New Thread** → form reveals.
3. Server validates → inserts row → redirect to index.

### Reply

1. User on `?thread=id`.
2. Hidden `<input name="parent" value="id">`.
3. Insert reply, bump OP’s `bumped_at`, redirect back.

### Moderator delete

1. Click **\[–]** → password prompt.
2. Hidden POST with CSRF token.
3. Server verifies hash, deletes files, overwrites body.

---

## 8 · Database Schema & Indexes <a id="8-database-schema--indexes"></a>

```sql
CREATE TABLE posts (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  parent     INTEGER,            -- NULL = thread OP
  name       TEXT    NOT NULL,
  subject    TEXT,
  body       TEXT    NOT NULL,
  image      TEXT,
  thumb      TEXT,
  created_at INTEGER NOT NULL,
  bumped_at  INTEGER NOT NULL,
  ip         TEXT    NOT NULL
);

CREATE INDEX idx_parent         ON posts(parent);
CREATE INDEX idx_parent_bumped  ON posts(parent, bumped_at DESC);
CREATE INDEX idx_created        ON posts(created_at DESC);
```

---

## 9 · Front‑end JavaScript <a id="9-front-end-javascript"></a>

| File                  | Purpose                       | Notes                                                     |
| --------------------- | ----------------------------- | --------------------------------------------------------- |
| `hide-form.js`        | Hide / show posting form      | Detects form by `name="post"` **or** hidden `action=post` |
| `inline-expanding.js` | Toggle thumbnail ⇆ full image | Vanilla JS, no dependencies                               |
| `style.js`            | Theme cycling                 | Finds `<link data-style="…">`                             |

---

## 10 · Theming & CSS <a id="10-theming--css"></a>

Add a new CSS file under `stylesheets/` then a matching link tag:

```html
<link rel="stylesheet" href="/stylesheets/solarized.css" data-style="solarized" disabled>
```

The switcher lists any stylesheet with `data-style` (except `default`).

---

## 11 · Maintenance Tasks <a id="11-maintenance-tasks"></a>

| Task                    | Command                                                           |
| ----------------------- | ----------------------------------------------------------------- |
| **Backup DB**           | `sqlite3 board.sqlite ".backup nightly-$(date +%F).bak"`          |
| **Vacuum + checkpoint** | `sqlite3 board.sqlite "PRAGMA wal_checkpoint(TRUNCATE); VACUUM;"` |
| **Logrotate**           | Configure web‑server access/error logs                            |

---

## 12 · Extending TinyBoard <a id="12-extending-tinyboard"></a>

1. Tripcodes (`name#secret`)
2. Country flags via GeoIP
3. JSON API for SPA/mobile clients
4. WebSocket live updates
5. Multi‑board support (`/chess/`, `/math/`, …)

---

## 13 · FAQ <a id="13-faq"></a>

<details>
<summary><strong>Why SQLite over MySQL/PostgreSQL?</strong></summary>

For a small educational board, WAL‑mode SQLite easily handles thousands of
posts per minute with zero external dependencies and trivial backups.

</details>

<details>
<summary><strong>How do I disable image uploads?</strong></summary>

In `handleUpload()` add:

```php
exit('Uploads disabled');
```

or hide the file field via CSS.

</details>

<details>
<summary><strong>Where is user login?</strong></summary>

TinyBoard is designed for anonymous / pseudonymous posting.
If you need accounts, add a `users` table, reuse CSRF/session code,
and gate posting accordingly.

</details>

---

## 14 · License & Credits <a id="14-license--credits"></a>

TinyBoard is released under the **MIT License**.
Theme CSS adapted from **vichan** (Apache‑2.0).
Thumbnail logic inspired by **Danbooru** (MIT).

---

*Built with ❤️ for the chess & learning community • Generated 20 Jun 2025*\`\`\`
