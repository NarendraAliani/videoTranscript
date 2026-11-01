# Provider setup

VideoTranscript separates **public URL extraction**, **uploaded-file speech recognition**, and **AI text generation**. Each can be changed without rewriting the application.

## Recommended low-cost setup

### 1. Uploaded audio/video: Groq
Create a Groq API key and set:
- UPLOAD_ASR_PROVIDER=groq
- GROQ_API_KEY=your_key
- GROQ_ASR_URL=https://api.groq.com/openai/v1/audio/transcriptions
- GROQ_ASR_MODEL=whisper-large-v3-turbo

The recommended model is multilingual and is currently listed at $0.04 per audio hour. The provider adapter requests verbose JSON to preserve segment timestamps. Provider file limits and rate limits depend on your account; this project defaults to a 25 MB maximum to accommodate free-tier account limits; raise both UPLOAD_MAX_MB and ASR_MAX_FILE_MB to 100 only after confirming your provider account supports it. See the official Groq speech-to-text docs: https://console.groq.com/docs/speech-to-text

### 2. Uploaded-file fallback: OpenAI
To switch, set UPLOAD_ASR_PROVIDER=openai and OPENAI_API_KEY. The default model is gpt-transcribe at https://api.openai.com/v1/audio/transcriptions. It is OpenAI's current recommended file-transcription model and returns transcript text, but not timestamped segments; SRT/VTT exports are therefore unavailable for this fallback. OpenAI lists gpt-transcribe at $0.0045/minute. Legacy whisper-1 and gpt-4o transcription models are deprecated, so this project does not select them by default.

### 3. AI summaries and repurposing: Groq
Set AI_PROVIDER=groq, GROQ_API_KEY and GROQ_CHAT_MODEL=openai/gpt-oss-20b. You can use a separate LLM endpoint/key by setting AI_PROVIDER=custom, LLM_API_URL, LLM_API_KEY and LLM_MODEL. The custom endpoint must implement the OpenAI chat-completions request/response shape.

### 4. Public video and podcast URLs
Set TRANSCRIPT_API_URL to the base URL of a URL-transcription service implementing:
POST /api/{platform}/transcript
JSON body: {"url":"https://...","language":"auto"}
Optional header: Authorization: Bearer {TRANSCRIPT_API_TOKEN}
Supported platform path values: youtube, tiktok, instagram, facebook, linkedin, twitter, twitch, rumble, kick, podcast.

For a temporary compatible backend, an account with TranscriptMagic API access can use TRANSCRIPT_API_URL=https://api.transcriptmagic.com and its own API token in TRANSCRIPT_API_TOKEN. This is an optional external dependency, not a built-in scraping engine; the URL provider remains replaceable.

The response should include text or transcript. Optional segments should contain start, end and text. TRANSCRIPT_API_URL is intentionally independent from the uploaded-file ASR provider. You can use a compatible URL extraction service initially, then replace it with a different service or your own extraction workers later. This repository does not bypass DRM, login walls, private media restrictions or platform access controls.

## Cost recording
Successful processing writes the provider, model, audio duration, estimated USD cost and processing time to provider_runs. Groq cost estimation currently uses $0.04/audio-hour for whisper-large-v3-turbo. OpenAI gpt-transcribe is listed at $0.0045/minute. Its API response does not include media duration, so the current usage panel does not estimate its per-run cost unless duration metadata becomes available. These are estimates from configured public list rates, not invoices; custom URL-provider charges are not estimated.

## Secrets
Keep all keys in the server-side .env file. Never commit real API keys or expose them in JavaScript. Rotate any key that is accidentally exposed.
