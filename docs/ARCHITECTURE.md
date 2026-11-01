# Architecture
PHP 8.3 provides the UI, sessions, authentication, validation and exports. MySQL stores users, transcripts, segments, jobs, usage and AI outputs. A PHP CLI worker claims queued jobs. URL transcription uses a configurable platform API adapter; uploads use an OpenAI-compatible speech-to-text endpoint; AI tools use a configurable chat-completions endpoint.

Flow: submit media -> validate -> insert transcript and queue job -> cron worker calls provider -> save text and segments -> user exports or generates AI content.

The URL provider base URL must implement /api/{platform}/transcript and accept JSON url and language. Response should include text or transcript and optional segments containing start, end and text. ASR_API_URL is a full audio transcription endpoint. LLM_API_URL is a full chat-completions endpoint.

No DRM circumvention or access-control bypass is implemented.
