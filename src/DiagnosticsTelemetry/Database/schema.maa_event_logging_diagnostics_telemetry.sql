CREATE TABLE maa_event_logging_diagnostics_telemetry (
                                       id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Database-generated diagnostics row identifier.',

                                       event_id CHAR(36) NOT NULL COMMENT 'Application event identifier used for idempotency and tracing.',

    -- Examples: http.request, db.query, exception, cache.miss...
                                       event_key VARCHAR(255) NOT NULL COMMENT 'Technical diagnostic event key.',

    -- Portable severity; recommended values: INFO|WARNING|ERROR|CRITICAL
                                       severity VARCHAR(16) NOT NULL DEFAULT 'INFO' COMMENT 'Diagnostic severity classification.',

                                       actor_type VARCHAR(32) NOT NULL COMMENT 'Actor category supplied by the host application.',
                                       actor_id BIGINT NULL COMMENT 'Host-provided actor identifier; no foreign key.',

                                       correlation_id CHAR(36) NULL COMMENT 'Identifier correlating this event with related logs.',
                                       request_id VARCHAR(64) NULL COMMENT 'Host request identifier for pipeline tracing.',
                                       route_name VARCHAR(255) NULL COMMENT 'Application route name associated with the diagnostic.',

                                       ip_address VARCHAR(45) NULL COMMENT 'Request IP address captured according to host policy.',
                                       user_agent VARCHAR(512) NULL COMMENT 'Request user-agent value captured according to host policy.',

    -- Duration for timing metrics (optional)
                                       duration_ms INT UNSIGNED NULL COMMENT 'Optional operation duration in milliseconds.',

    -- Additional diagnostics metadata (avoid PII; never store secrets)
                                       metadata JSON NULL COMMENT 'Optional structured diagnostic metadata; avoid PII and secrets.',

                                       occurred_at DATETIME(6) NOT NULL COMMENT 'Timestamp when the diagnostic event occurred.',

                                       UNIQUE KEY uq_el_diag_telemetry_event_id (event_id),

    -- Cursor index for stable paging / future batch processing
                                       INDEX idx_diag_telemetry_time (occurred_at, id),

    -- Required search dimensions
                                       INDEX idx_diag_telemetry_actor_time (actor_type, actor_id, occurred_at),
                                       INDEX idx_diag_telemetry_event_time (event_key, occurred_at),

    -- Dashboards
                                       INDEX idx_diag_telemetry_severity_time (severity, occurred_at),

    -- Correlation helpers
                                       INDEX idx_diag_telemetry_correlation_time (correlation_id, occurred_at),
                                       INDEX idx_diag_telemetry_request_time (request_id, occurred_at),
                                       INDEX idx_diag_telemetry_route_time (route_name, occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Diagnostics telemetry for tracing/performance/technical errors (non-business). Searchable by event_key+time and actor+time. Avoid PII; prefer hashed identifiers if needed.';
