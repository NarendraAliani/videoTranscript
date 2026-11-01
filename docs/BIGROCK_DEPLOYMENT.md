# BigRock deployment
1. Upload this repository to a NEW application directory; do not overwrite existing websites.
2. Create a NEW MySQL database and user in cPanel, then grant all privileges.
3. Import database/001_schema.sql in phpMyAdmin, then database/003_procedures.sql. Import only into that new database. If you imported an earlier schema before provider tracking was added, run database/004_provider_tracking_migration.sql exactly once.
4. Copy .env.example to .env and configure APP_URL and DB credentials. Keep .env private.
5. Enable PHP 8.3 with PDO MySQL, cURL, Fileinfo and mbstring. Set PHP upload_max_filesize and post_max_size to at least 25M.
6. Configure transcription and AI providers in .env (see PROVIDER_SETUP.md). For the recommended setup, create a Groq API key and set GROQ_API_KEY; public URL transcription still requires a separate compatible URL provider.
7. Register your account, edit the email in database/002_make_admin.sql and run it to grant superadmin.
8. Add a cPanel cron job every minute: php /home/CPANEL_USER/public_html/APP_DIR/worker/process_jobs.php 5. Replace the path/PHP binary with BigRock's values.
9. Run php tests/smoke_check.php and php tests/run.php, then test a small audio upload and TXT/SRT/VTT/JSON exports.
This repository does not deploy to BigRock or create the live database. The SQL is provided for manual import as requested. If PHP CLI/cron is unavailable, queued jobs will not execute. Prefer storing uploads outside public_html. Never commit API keys.
