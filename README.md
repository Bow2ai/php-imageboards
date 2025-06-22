
 
 
 
 # Zero tolerance for lesser PHP versions !! 
 FIRST things first. THis ib is ONLY made to run in PHP 8.4.8 and above. THIS IB DOES NOT CARE ABOUT  old, unsafe , depricated lower versions of php that do not even have security support any more. In fact, when any new version of php comes out, this app will update as rapidly as possible. We have ZERO TOLERANCE for any version of php lower than the very latest version. 
 

 # ZERO TOLERANCE FOR BLOAT !!
Every other php imageboard has lots of bloat. That is absurd and makes the app easy to hack. The codebase of this ib will be 1/1000th of bloated boards. Yes, it is true, and yes, it will stay that way.  

# Roadmap

1- Get a decent board up, no bloat and security focused. Done with our first version, 435 lines of code! 

2- Next, we will dev the app giving it minimal sensible features and make it as rock solid as possible. 

3- Lastly, we will focus entirely on security- adding any new feature would introduce possible attack surface so the last step will be to only work on security and to make sure the board is made for the very latest version of php. No other imageboard app comes close to our standards. This is the only ib that ONLY works with the very latest version of php. 

 
 PSA- PHP is a mind virus, a vortex of failure. IF you are going to use php, do NOT use it for anything serious, and keep the code SMALL as possible and ONLY use the very latest version of php. One can NOT make a rock solid secure php app- several famous hackers have been saying that for years. Your server would have to be rock solid too. Nevertheless, this repo will strive to dev the most secure app possible. 

# Security insignts are gladly accepted- create a github issue. There will ALWAYS be security issues in php, we know that. UNLIKE other imageboards, this one will not be bloated and it will have a very heavy and unwavering focus on security. NO OTHER php imageboard can even come close to saying that. 




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

Whitespace tightening – normalizeWhitespace() now uses '/\n{2,}/' instead of '/\n{3,}/', so there is never a blank line, only a single br>.

Headers & php.ini flags – added ini_set('expose_php','0') and three security headers; session cookie is marked Secure automatically when HTTPS is detected.

No functional logic altered – DB schema, routing, uploads, CSRF, rendering all behave exactly as before, just with tidier output and stronger defaults for production deployment.















 
