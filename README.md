# PSA- PHP is a mind virus, a vortex of failure. IF you are going to use php, do NOT use it for anything serious, and keep the code SMALL as possible and ONLY use the very latest version of php.




 # Adelia- 

What if you took vichan, made it one 435 line page of php code, sqlite3, security, and made it to only work with php8.4.8++ ? Well run this board and find out! It's awesome, and makes you wonder why vichan insists on extreme bloat and the largest attack surface possible. Requires an uploads and uploads/thumb dir.  You can drop in any  existing vichan js or css ! Have ai audit the small index.php and change anything you want! Bam! Escape from the vortex of unsafe code and malicious culture that is vichan. NOTE- use Adelia1 it is better. This version (adelia) is good if you want to feed it to Ai and veer off in another direction. As such it is a starter kit to get you going fast with dev. 


# Adelia1- 

 • Field length caps and server‑side truncation
 
  • Double MIME sniff for uploads
  
  • Moderator redirect sanitised
  
  • CSP + nosniff + XFO headers


Awesome app... 505 lines of code! While Adelia is quite secure, Adelia1 has slightly improved security hardening. Hey, its php- there is ALWAYS room to make it more secure. In either the server settings or the php code itself, there is ALWAYS room to make php apps more secure. THAT is why php is rapidly going out of style. Want real security? Use a RUST coded imageboard. IF you want to use php, use a VERY small codebase like adelia or adelia1 , have ai audit the code every so often, and never use php for anything serious.  

# adelia2-

472 lines. May or may not be better than adelia1, depending on how server is set up. Personally, i like adelia1 better. White‑space handling	Any run of two or more newlines is collapsed to one newline, so a pasted wall of blank lines now renders with the minimum possible vertical gap (one br>). Security / prod hardening	• expose_php disabled
• session cookie made Secure automatically when the site is served over HTTPS, kept Strict/HttpOnly otherwise
• extra defence‑in‑depth headers (Referrer‑Policy, Permissions‑Policy, X‑Permitted‑Cross‑Domain‑Policies)

Whitespace tightening – normalizeWhitespace() now uses '/\n{2,}/' instead of '/\n{3,}/', so there is never a blank line, only a single <br>.

Headers & php.ini flags – added ini_set('expose_php','0') and three security headers; session cookie is marked Secure automatically when HTTPS is detected.

No functional logic altered – DB schema, routing, uploads, CSRF, rendering all behave exactly as before, just with tidier output and stronger defaults for production deployment.















 
# Note- since php is not that great, i am not going to make any more imageboards besides the "adelia" series. I will work on them every so often. Mostly, i will be working with Rust -, because php is just silly. 
