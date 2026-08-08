# Gandhinagar Jilla Central Attendance

Simple PHP + MySQL version designed to avoid the iPhone folder-upload problem.

Files:
- `index.php` — complete member attendance + admin dashboard
- `database.sql` — database tables + the supplied 126 members (mobile numbers are SHA-256 hashed)

Upload these two files to your PHP hosting. Import `database.sql` in phpMyAdmin, then configure DB_HOST, DB_NAME, DB_USER, DB_PASS, ADMIN_USER and ADMIN_PASS as hosting environment variables.

Do not publish raw mobile numbers or passwords. Use HTTPS.
