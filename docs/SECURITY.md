# Security
Passwords use PHP password_hash/password_verify. Database calls use PDO prepared statements. Forms use CSRF tokens. Transcript access is scoped to the authenticated owner. URL submissions use an allowlist of public media hosts. Uploads use MIME checks and random server-side filenames. Provider keys are server-side environment variables.
Before public launch at scale, add email verification, password reset, rate limiting, quotas, admin audit logs and private upload storage outside the web root. Protect storage/app.log from public access.
