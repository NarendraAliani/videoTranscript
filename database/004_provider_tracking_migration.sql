-- Run ONLY if database/001_schema.sql was imported before provider tracking was added.
ALTER TABLE transcripts
  ADD COLUMN provider_name VARCHAR(60) NULL AFTER provider_payload,
  ADD COLUMN provider_model VARCHAR(120) NULL AFTER provider_name;
CREATE TABLE provider_runs(
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  transcript_id BIGINT UNSIGNED NOT NULL,
  provider_name VARCHAR(60) NOT NULL,
  model_name VARCHAR(120) NULL,
  audio_seconds INT UNSIGNED NULL,
  cost_usd DECIMAL(14,8) NULL,
  processing_ms INT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('completed','failed') NOT NULL,
  error_message TEXT NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_provider_run_transcript FOREIGN KEY(transcript_id) REFERENCES transcripts(id) ON DELETE CASCADE,
  INDEX idx_provider_run_transcript(transcript_id),
  INDEX idx_provider_run_provider(provider_name,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
