# Provider decision (initial)

| Workload | Recommended default | Why |
|---|---|---|
| Uploaded media transcription | Groq whisper-large-v3-turbo | Multilingual Whisper model; low published per-hour rate |
| Accuracy fallback | Groq whisper-large-v3 or OpenAI gpt-4o-mini-transcribe | Allows a provider/model switch without application changes |
| AI summaries and repurposing | Groq chat completions | Reuses the Groq key and OpenAI-compatible API shape |
| Public video/podcast URL extraction | Configurable URL transcription API | Extraction is a separate problem from speech-to-text; keep it swappable |

This is an initial economic configuration, not a claim that one provider is best for every language or content type. Benchmark representative Hindi, Gujarati and English samples before charging customers. Review provider terms, retention, quotas, regional availability and current prices before production.
