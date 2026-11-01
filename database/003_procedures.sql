-- Optional operational procedures for MySQL 8.0+
DELIMITER //
CREATE PROCEDURE sp_transcript_usage_summary(IN p_user_id BIGINT UNSIGNED)
BEGIN
  SELECT
    COUNT(DISTINCT t.id) AS transcript_count,
    COALESCE(SUM(CASE WHEN t.status='completed' THEN 1 ELSE 0 END),0) AS completed_count,
    COALESCE(SUM(CASE WHEN t.status IN ('queued','processing') THEN 1 ELSE 0 END),0) AS pending_count,
    COALESCE((SELECT SUM(u.units) FROM usage_ledger u WHERE u.user_id=p_user_id),0) AS units_consumed
  FROM transcripts t
  WHERE t.user_id=p_user_id;
END//
CREATE PROCEDURE sp_recover_stale_jobs(IN p_stale_minutes INT)
BEGIN
  UPDATE jobs
  SET status='queued', available_at=NOW(), updated_at=NOW(),
      error_message='Recovered stale worker lease'
  WHERE status='processing'
    AND updated_at < TIMESTAMPADD(MINUTE, -GREATEST(p_stale_minutes,5), NOW())
    AND attempts < 3;

  UPDATE transcripts t
  JOIN jobs j ON j.transcript_id=t.id
  SET t.status='queued', t.updated_at=NOW()
  WHERE j.status='queued' AND t.status='processing';
END//
DELIMITER ;
