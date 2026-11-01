# VideoTranscript
Self-hosted video, podcast and audio transcription workspace.

## Stack
PHP 8.3, MySQL 8, Bootstrap 5.3, Google Fonts, Font Awesome, PDO and cURL. No React or Node.js backend.

## Features in this repository
- Responsive landing page, registration, sign-in and dashboard.
- Supported public media URL submission and audio/video upload.
- MySQL job queue with PHP CLI worker for scheduled processing.
- Timestamped transcript segments and TXT, JSON, SRT and VTT exports.
- AI summaries, chapters, translation and content repurposing through a configurable LLM.
- Searchable transcript history, usage ledger and provider status.

## Setup
Import database/001_schema.sql into a NEW dedicated database. Copy .env.example to .env and enter database/provider details. Register your account, then use database/002_make_admin.sql to grant superadmin. Configure cPanel cron to run worker/process_jobs.php. Follow docs/BIGROCK_DEPLOYMENT.md.

## Providers
URL transcription requires a compatible API implementing /api/{platform}/transcript. File transcription requires an OpenAI-compatible speech-to-text endpoint. AI tools require a chat-completions endpoint. No DRM bypass or platform access-control circumvention is included.

## Before a public SaaS launch
Email verification, password reset, subscriptions, quota enforcement, admin screens, API-key management, rate limiting and production storage hardening remain follow-up work. Configure provider credentials and BigRock cron before expecting transcription jobs to complete.
