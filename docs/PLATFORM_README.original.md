# Avalon CTF

A small PHP + MySQL CTF platform built for InfinityFree hosting: login
and signup, 7 sequentially-unlocked challenges, a points system, and a
final congratulations page.

## 1. Requirements

InfinityFree gives you PHP + MySQL on shared hosting - this project is
plain PHP (no framework, no Composer packages, no build step), so it
runs there as-is.

## 2. Upload

Upload the entire contents of this folder into `htdocs/` on your
InfinityFree account (via the File Manager or FTP), keeping the folder
structure intact.

## 3. Create the database

1. In the InfinityFree control panel, create a MySQL database.
2. Open phpMyAdmin for that database and import `database.sql`
   (this creates all tables and seeds a demo user + 7 dummy challenges).
3. Edit `config/db.php` and fill in the real `DB_HOST`, `DB_NAME`,
   `DB_USER`, `DB_PASS` values shown in the InfinityFree panel.

## 4. Logging in / signing up

Players can create their own account from `signup.php` - no admin
action needed. Validation rules (enforced server-side in `signup.php`
via `isValidUsername()` / `isValidPassword()` in
`includes/functions.php`):

- Username: 3-20 characters, letters/numbers/underscores only, must be
  unique (also enforced by a `UNIQUE` constraint on `users.username`
  as the real guarantee against two people signing up with the same
  name at the same instant).
- Password: at least 8 characters, with at least one letter and one
  number. Confirmed by re-typing it.

The seed data also creates one demo account for you to test with:

- username: `admin`
- password: `admin123`

**Change or remove this before going live.** To add an account
manually instead of through the signup page (e.g. for an admin/organizer
login), generate a hash locally (do NOT upload this script to the
server):

```
php tools/generate_password_hash.php "SomeStrongPassword"
```

Then insert a row into `users` with that hash:

```sql
INSERT INTO users (username, password_hash) VALUES ('player1', '<hash>');
```

## 5. Editing challenges

Each row in the `challenges` table has:

- `title`, `challenge_number` (1-7, controls unlock order)
- `difficulty` (`easy` / `medium` / `hard`) - points are derived from
  this automatically (25 / 50 / 75), so you never set points directly
- `description` and `player_task` - plain text, but you can drop in a
  clickable link anywhere using `[[label|https://example.com]]`. It's
  rendered by the single shared `renderTextWithLinks()` function in
  `includes/functions.php`, so the same syntax works in both fields for
  every challenge - no per-challenge code needed.
- `flag_hash` - every flag must be in the format `AVALON{...}`
  (enforced by `FLAG_FORMAT_REGEX` / `isValidFlagFormat()` in
  `includes/functions.php` - update that constant in one place if the
  format ever needs to change again). Generate the hash with the same
  tool as above: `php tools/generate_password_hash.php "AVALON{the_real_flag}"`,
  then paste the resulting hash in. The plaintext flag is never stored
  anywhere in the database or sent to the browser.

To regenerate a flag hash or update a challenge, just run an `UPDATE`
in phpMyAdmin.

## 6. Attaching files

Files live on disk under:

```
attachments/challenge1/
attachments/challenge2/
...
attachments/challenge7/
```

To attach a file to a challenge:

1. Upload the actual file into the matching `attachments/challengeN/`
   folder (InfinityFree File Manager or FTP).
2. Add a row to `challenge_attachments`:

```sql
INSERT INTO challenge_attachments (challenge_id, display_name, stored_filename, sort_order)
VALUES (<challenge id>, 'capture.pcap', 'capture.pcap', 0);
```

The challenge page picks this up automatically via
`includes/attachments.php` - no template changes needed, and this same
block is reused for every challenge.

Files are **never served directly**. `attachments/.htaccess` blocks all
direct web access to that folder, so every download must go through
`download.php`, which re-checks (from the database, every time) that
the requesting user is logged in and that the challenge the file
belongs to is currently unlocked for them.

## 7. How sequential unlocking works

- Challenge 1 is always unlocked.
- Challenge N (2-7) is unlocked only once challenge N-1 has been solved
  by that specific user (checked via the `user_progress` table).
- The "Congratulations" 8th sidebar item unlocks only once all 7 are
  solved.

This is enforced in **one place**, `isChallengeUnlocked()` in
`includes/functions.php`, and re-checked server-side on every request
that matters: `challenge.php`, `submit_flag.php`, `download.php`, and
`congrats.php`. The sidebar (`includes/sidebar.php`) only reflects this
state for the UI - hiding a link there is not what actually protects
anything, so changing `?id=` in the URL, POSTing straight to
`submit_flag.php`, or guessing a `download.php?aid=` value cannot skip
ahead or pull a file for a locked challenge.

Visually, a locked challenge is rendered as a plain `<span>` (not a
link), greyed out and with `cursor: not-allowed` - there's no lock
icon, just a lower-contrast, unclickable row. Unlocked and completed
challenges are real `<a>` links, so they get a pointer cursor and a
highlighted hover state automatically.

## 8. Security measures already in place

- **No IDOR**: every access to a challenge, a flag-submission, or a
  file download re-validates ownership/unlock status against the
  database using the server-side session user id - never a client
  supplied "is this unlocked" flag.
- **SQL injection**: all queries use PDO prepared statements with bound
  parameters (`PDO::ATTR_EMULATE_PREPARES` disabled).
- **XSS**: all dynamic values are HTML-escaped on output. The only
  place raw-ish text becomes a link is `renderTextWithLinks()`, which
  escapes first and only turns a strict `[[label|http(s)://...]]`
  pattern into an `<a>` tag - anything else is left as plain escaped
  text, so it can't be used to inject markup or a `javascript:` URL.
- **Password storage**: `password_hash()` / `password_verify()`
  (bcrypt). Login also runs `password_verify()` against a dummy hash
  when the username doesn't exist, so response timing doesn't leak
  which usernames are valid.
- **Signup validation**: username format + uniqueness and password
  strength are checked server-side (`signup.php`), with the database's
  `UNIQUE` constraint on `users.username` as the actual guarantee
  against a duplicate-signup race.
- **Flag storage**: flags are hashed the same way and compared with
  `password_verify()` - the plaintext flag is never stored in a
  retrievable form or sent to the browser. Submitted flags are also
  checked against the required `AVALON{...}` format before the hash
  comparison even runs.
- **CSRF protection**: login and flag submission both require a
  per-session token validated with `hash_equals()`.
- **Session hardening**: `HttpOnly` + `SameSite=Lax` cookies, session
  ID regenerated on login (fixation protection), full session
  destruction + cookie clear on logout.
- **Path traversal**: `download.php` strips any directory components
  from the stored filename and confirms the resolved real path is
  still inside the expected challenge folder before reading it.
- **Direct file access blocked**: `.htaccess` denies all direct HTTP
  requests into `attachments/`.
- **Basic throttling**: a 1-request-per-second guard on flag
  submissions per session to slow down brute-forcing.

### A couple of things worth doing yourself before a real competition

- Enable the free HTTPS/Let's Encrypt option in the InfinityFree panel,
  then uncomment `'cookie_secure' => true` in `includes/auth.php`.
- If you expect many simultaneous players, consider a proper rate
  limiter (e.g. attempts-per-challenge stored in the DB) instead of the
  simple per-session delay included here.
- Rotate the demo `admin` account's password or delete it entirely.

## 9. File map

```
config/db.php               - DB credentials (edit this)
includes/auth.php           - session bootstrap, login guard, CSRF helpers
includes/functions.php      - challenge/points/unlock logic, link renderer
includes/attachments.php    - modular attachment list (reused per challenge)
includes/head.php           - <head> + navbar
includes/sidebar.php        - challenge 1-7 + congratulations nav
includes/footer.php         - closing markup + JS include
assets/css/style.css        - all styling
assets/js/script.js         - AJAX flag submission + inline error message
login.php / logout.php      - auth pages
signup.php                  - new player registration with validation
index.php                   - routes to the player's current challenge
challenge.php               - the challenge window (title/desc/task/flag/files)
submit_flag.php             - AJAX endpoint, verifies flag format + hash, records progress
download.php                - AJAX-free, auth-checked file download
congrats.php                - final page, gated on all 7 solved
database.sql                - schema + seed data (demo user + 7 dummy challenges, AVALON{} flags)
attachments/challengeN/     - real challenge files go here
attachments/.htaccess       - blocks direct access to that folder
tools/generate_password_hash.php - local-only helper for hashing passwords/flags
```
