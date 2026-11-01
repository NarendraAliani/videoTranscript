# Provider setup
URL transcription: TRANSCRIPT_API_URL is a service base URL. The worker posts to /api/{platform}/transcript with JSON url and language. Supported platform path values: youtube, tiktok, instagram, facebook, linkedin, twitter, twitch, rumble, kick, podcast. The response should contain text or transcript; optional segments may contain start, end and text.
Uploads: ASR_API_URL points to an OpenAI-compatible audio transcription endpoint. Set ASR_API_KEY and ASR_MODEL.
AI: LLM_API_URL points to a chat-completions compatible endpoint. Set LLM_API_KEY and LLM_MODEL.
Keep keys in .env, never in source code or browser JavaScript.
