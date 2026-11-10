# Security Checklist

## Authentication
- [x] Passwords stored using password_hash() with PASSWORD_DEFAULT.
- [x] Password verification uses password_verify().
- [x] Session ID regenerated after login.
- [x] Session idle timeout after 30 minutes.
- [x] Remember-me tokens generated using cryptographically secure randomness.
- [x] Remember-me tokens stored as SHA-256 hashes in the database.
- [x] Remember-me token rotated after successful restoration.
- [x] Authentication routes protected.

## CSRF
- [x] Cryptographically random session CSRF token.
- [x] CSRF token included in application POST forms.
- [x] Every registered POST route validated centrally.
- [x] Invalid or missing tokens return HTTP 403.
- [x] Token comparison uses hash_equals().

## XSS
- [x] Dynamic view output uses the e() helper.
- [x] e() uses htmlspecialchars() with ENT_QUOTES and UTF-8.
- [x] Flash messages and form validation messages are escaped.

## SQL Injection
- [x] Database access uses PDO.
- [x] PDO emulated prepares disabled.
- [x] User-supplied query values must use prepared statements and bound parameters.

## Security Headers
- [x] X-Content-Type-Options: nosniff
- [x] X-Frame-Options: DENY
- [x] X-XSS-Protection: 1; mode=block
- [x] Referrer-Policy: strict-origin-when-cross-origin
- [x] Content-Security-Policy configured.

## Login Rate Limiting
- [x] Maximum five failed login attempts per IP.
- [x] Fifteen-minute lockout window.
- [x] Remaining lockout time displayed to the user.
- [x] Successful login clears the IP's failed attempts.
- [x] Rate-limit filenames use hashed IP addresses.
- [x] File access protected with file locking.

## File Uploads
- [x] JPEG, PNG, and GIF extensions only.
- [x] MIME type checked using finfo_file().
- [x] Image content checked using getimagesize().
- [x] Maximum upload size of 2 MB.
- [x] Randomized filenames.
- [x] Upload directory blocks executable files.
- [x] Directory listing disabled.

## Deployment Notes
- [ ] Configure HTTPS in production.
- [ ] Keep database credentials outside version control.
- [ ] Ensure storage/ratelimit is writable by the web server.
- [ ] Ensure public/uploads is writable by the web server.
- [ ] Test all authentication, CRUD, upload, and CSRF flows after deployment.