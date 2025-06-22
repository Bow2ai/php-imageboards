
 
**Adelia Imageboard**

Most php imageboard apps are a bloated pile of crap. Now that Ai is here, i can make the imageboard that i always dreamed of. One without the nonsense. THIS repo is made for people who want to have wholsesome educational sites using the basic imageboard model. As such, it is a cross of Forums, Imageboards and Wiki. 

Each folder represents a stable dev milestone. Versioning that way will keep the code separated to allow quick rollbacks and comparisons. For early dev sqlite3 is used, our more developed version will use postgres as it is more secure. 

## Requirements

* **PHP 8.4.8 or newer**
* SQLite3 extension enabled
* Write permissions for the `uploads/` and `uploads/thumb/` directories

This application is designed exclusively for modern PHP releases. Older versions of php are unsupported and may expose security risks.

---

## Key Principles

1. **Zero Tolerance for Legacy PHP**
   We only support the latest PHP with full security support—no compatibility layers or deprecated code.

2. **Minimal Bloat**
   At around 500 lines of code per version, our codebase is a fraction of typical imageboards, reducing attack surface and simplifying audits.

3. **Security-First**
   Every feature is chosen for necessity and implemented with defense-in-depth in mind.

---

## Roadmap

1. **v1.x (Adelia)** — Basic board functionality, no extra features, maximum security focus. (\~435 lines)
2. **v2.x (Adelia1)** — Incremental, minimal features with rock-solid stability and performance. (\~505 lines)
3. **v3.x (Adelia2)** — Security hardening only, production-ready defaults, and whitespace normalization. (\~472 lines)
4. **Future Milestones** — Security hardening only. No new user-facing features to avoid expanding attack surface. Continuous updates for each new PHP release.

---

## Version Overview

### Adelia (v1.x)

* Single-file PHP board (`/adelia`) using `declare(strict_types=1)` and minimal dependencies
* Requires PHP 8.4+ with built-in version check and 500 response on older versions
* SQLite3 backend with WAL journaling and a simple `posts` table schema
* Session management with `HttpOnly` and `SameSite=Strict` cookies
* CSRF protection with per-session tokens and token verification on POST actions
* IP-based rate limiting (10 sec cooldown between posts)
* Secure file uploads: double MIME sniffing, 5 MiB size limit, unique IDs, metadata stripping
* Automatic 255×144 PNG thumbnail generation with oversized-image guard (≤40 MP)
* Moderator deletion endpoint protected by bcrypt-hashed password
* Thread and index pagination (15 threads/posts per page)
* Clean HTML output with theme-switcher hooks
* \~435 lines of self-contained, well-documented PHP code

### Adelia1 (v2.x)

* Configurable debugging & logging (`DEBUG_DISPLAY`, `DEBUG_LOG`) with optional error.txt logging
* Fully functional without `mbstring`, with ASCII-only `mb_substr`/`mb_strlen` fallbacks
* Field length caps and server-side truncation for `name` (35), `subject` (100), and `body` (10,000)
* Second full-file MIME sniff after upload to ensure type consistency
* Sanitized moderator redirect logic preventing external or newline-injected URLs
* Enhanced CSRF and session handling (HTTP‑only, `SameSite=Strict` cookies)
* Hardened HTTP headers: Content-Security-Policy, `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`
* Same thumbnail, WAL-backed SQLite, rate‑limit, and pagination logic as v1, with logging support
* \~505 lines of self-contained PHP for rock-solid security and auditability

### Adelia2 (v3.x)

* Production-grade defaults: `expose_php` disabled and error logging off by default
* Session cookies marked `Secure` when HTTPS is detected, along with `HttpOnly` and `SameSite=Strict`
* Whitespace normalization (`normalizeWhitespace`): CRLF→LF, collapse ≥2 newlines to one, and shrink multi-space runs
* Tightened output trimming to prevent blank lines and excessive spacing before rendering
* Configurable mbstring fallbacks remain in place for environments without `mbstring`
* Dual MIME sniff on uploads, 5 MiB limit, unique IDs, and metadata stripping unchanged
* Hardened headers: Content-Security-Policy, `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`,
  `Referrer-Policy: same-origin`, `Permissions-Policy`, and `X-Permitted-Cross-Domain-Policies`
* SQLite3 WAL mode, IP rate limiting, CSRF tokens, pagination, and moderator delete flow as before
* \~472 lines of polished, production-ready PHP code

---

## Usage

1. Clone the repository.
2. Choose a version folder (`adelia`, `adelia1`, or `adelia2`).
3. Ensure your server meets the requirements above.
4. Configure your web server to serve `index.php` in that folder.
5. Create `uploads/` and `uploads/thumb/` directories with write permissions.
6. Start posting!

---

## Contributing & Security

Security insights and reports are welcome. Please open an issue for any vulnerabilities or suggestions. We maintain minimal code and rapid updates to stay ahead of PHP security challenges. Nowadays, github issue posting is a bit silly when you can just feed the code to ai and have your desired feature instantly coded for you. NEVERTHELESS, if you think of any minimal features that would benefit everyone, feel free to post a github issue. I will go one step further, in fact. IF you want an issue that i do not want to implememet, and IF you do not have access to Ai, i can have ai code your desisred feature into the app and i will give you the full code. LOOK at vichan issues-- about 5 years of people wanting features or compatibility with latest php versions and getting zero results. THIS repo is not like that, and THIS repo will maintain things the right way. 

---

**Adelia** — Escape the bloat, embrace security, and run the only imageboard built for the latest PHP.
