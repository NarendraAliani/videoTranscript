# BigRock deployment
1. Upload this repository to a NEW application directory; do not overwrite existing websites.
2. Create a NEW MySQL database and user in cPanel, then grant all privileges.
3. Import database/001_schema.sql in phpMyAdmin, then database/003_procedures.sql. Import only into that new database. If you imported an earlier schema before provider tracking was added, run database/004_provider_tracking_migration.sql exactly once.
4. Copy .env.example to .env and configure APP_URL and DB credentials. Keep .env private.
5. Enable PHP 8.3 with PDO MySQL, cURL, Fileinfo and mbstring. Set upload_max_filesize=25M and post_max_size=30M (or higher if you raise UPLOAD_MAX_MB).
6. Configure transcription and AI providers in .env (see PROVIDER_SETUP.md). For the recommended setup, create a Groq API key and set GROQ_API_KEY; public URL transcription can use a compatible URL API by default, or the optional local yt-dlp worker if BigRock supports Python, FFmpeg and PHP proc_open (see PROVIDER_SETUP.md).
7. Register your account, edit the email in database/002_make_admin.sql and run it to grant superadmin.
8. Add a cPanel cron job every minute: php /home/CPANEL_USER/public_html/APP_DIR/worker/process_jobs.php 5. Replace the path/PHP binary with BigRock's values.
9. Run php tests/smoke_check.php and php tests/run.php, then test a small audio upload and TXT/SRT/VTT/JSON exports.
This repository does not deploy to BigRock or create the live database. The SQL is provided for manual import as requested. If PHP CLI/cron is unavailable, queued jobs will not execute. For stronger isolation, set UPLOAD_DIR to an absolute private directory outside public_html (for example, /home/CPANEL_USER/private/videoTranscript-uploads); the application resolves absolute upload paths. If you keep the default storage/uploads path, the included storage/.htaccess denies direct web access. Never commit API keys.
