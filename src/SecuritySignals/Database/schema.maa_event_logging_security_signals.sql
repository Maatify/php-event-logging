-- ==========================================================
-- 3) Security Signals (NON-AUTH / BEST-EFFORT)
-- ----------------------------------------------------------
-- Answers: "What security-relevant signals happened?"
-- MUST NOT affect control-flow. MUST tolerate failure.
-- ==========================================================

CREATE TABLE maa_event_logging_security_signals (
                                  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Database-generated security-signal row identifier.',

    -- UUID per signal/event (portable)
                                  event_id CHAR(36) NOT NULL COMMENT 'Application event identifier used for idempotency and tracing.',

    -- Actor involved (can be anonymous for pre-auth signals)
                                  actor_type VARCHAR(32) NOT NULL COMMENT 'Actor category supplied by the host application.',
                                  actor_id BIGINT NULL COMMENT 'Host-provided actor identifier; no foreign key.',

    -- Examples: login_failed, permission_denied, session_invalid...
                                  signal_type VARCHAR(100) NOT NULL COMMENT 'Security-relevant signal type reported by the application.',

    -- Portable severity; recommended values: INFO|WARNING|ERROR|CRITICAL
                                  severity VARCHAR(16) NOT NULL COMMENT 'Security signal severity classification.',

    -- Correlation to request pipeline
                                  correlation_id CHAR(36) NULL COMMENT 'Identifier correlating this event with related logs.',
                                  request_id VARCHAR(64) NULL COMMENT 'Host request identifier for pipeline tracing.',
                                  route_name VARCHAR(255) NULL COMMENT 'Application route name associated with the signal.',

    -- Request context
                                  ip_address VARCHAR(45) NULL COMMENT 'Request IP address captured according to host policy.',
                                  user_agent VARCHAR(512) NULL COMMENT 'Request user-agent value captured according to host policy.',

    -- Additional info (MUST NOT contain secrets)
                                  metadata JSON NOT NULL COMMENT 'Structured signal metadata; must not contain secrets.',

                                  occurred_at DATETIME(6) NOT NULL COMMENT 'Timestamp when the security signal occurred.',

                                  UNIQUE KEY uq_el_security_signals_event_id (event_id),

    -- Cursor index for stable paging / future batch processing
                                  INDEX idx_el_security_signals_time (occurred_at, id),

    -- Required search dimensions
                                  INDEX idx_el_security_signals_actor_time (actor_type, actor_id, occurred_at),
                                  INDEX idx_el_security_signals_type_time (signal_type, occurred_at),

    -- Dashboards / alerting
                                  INDEX idx_el_security_signals_severity_time (severity, occurred_at),

    -- Correlation helpers
                                  INDEX idx_el_security_signals_corr_time (correlation_id, occurred_at),
                                  INDEX idx_el_security_signals_request_time (request_id, occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Security signals for detection/alerting (non-authoritative). Best-effort; MUST NOT block user actions. Metadata MUST NOT contain secrets (passwords, OTP codes, tokens).';
