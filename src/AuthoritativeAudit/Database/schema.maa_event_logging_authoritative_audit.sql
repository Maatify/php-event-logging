CREATE TABLE maa_event_logging_authoritative_audit_outbox (
                                            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Database-generated outbox row identifier.',

    -- UUID string for idempotency / tracing across systems (portable, no BINARY dependency)
                                            event_id CHAR(36) NOT NULL COMMENT 'Application event identifier used for idempotency and tracing.',

    -- Who caused the event (ADMIN / SYSTEM / SERVICE ...)
                                            actor_type VARCHAR(32) NOT NULL COMMENT 'Actor category supplied by the host application.',
                                            actor_id BIGINT NULL COMMENT 'Host-provided actor identifier; no foreign key.',

    -- What happened (authoritative state change)
                                            action VARCHAR(128) NOT NULL COMMENT 'Authoritative state-change action name.',

    -- What was affected
                                            target_type VARCHAR(64) NOT NULL COMMENT 'Type of resource affected by the event.',
                                            target_id BIGINT NULL COMMENT 'Host-provided affected-resource identifier; no foreign key.',

    -- Risk level (portable VARCHAR; values should be controlled at app-level)
    -- Recommended values: LOW|MEDIUM|HIGH|CRITICAL
                                            risk_level VARCHAR(16) NOT NULL COMMENT 'Application-controlled risk classification for the event.',

    -- Authoritative event payload (MUST NOT contain secrets)
                                            payload JSON NOT NULL COMMENT 'Structured authoritative event payload; no secrets.',

    -- Correlation between different log domains and request pipeline
                                            correlation_id CHAR(36) NOT NULL COMMENT 'Identifier correlating this event with related logs.',

    -- Outbox enqueue timestamp (transactional)
                                            created_at DATETIME(6) NOT NULL COMMENT 'Timestamp when the event entered the transactional outbox.',

    -- Idempotency: same event MUST NOT be written twice
                                            UNIQUE KEY uq_auth_audit_outbox_event_id (event_id),

    -- Cursor index for stable paging / future batch processing (no partitioning assumptions)
                                            INDEX idx_auth_audit_outbox_time (created_at, id),

    -- Common investigations
                                            INDEX idx_auth_audit_outbox_actor_time (actor_type, actor_id, created_at),
                                            INDEX idx_auth_audit_outbox_target_time (target_type, target_id, created_at),
                                            INDEX idx_auth_audit_outbox_correlation_time (correlation_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Authoritative audit outbox (source of truth). Transactional + fail-closed. Written ONLY inside business transactions. Payload MUST NOT contain secrets (tokens/OTPs/passwords).';


CREATE TABLE maa_event_logging_authoritative_audit_log (
                                         id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Database-generated materialized-log row identifier.',

    -- Same event_id from outbox (idempotency & cross-reference)
                                         event_id CHAR(36) NOT NULL COMMENT 'Event identifier materialized from the outbox.',

    -- Who caused the event
                                         actor_type VARCHAR(32) NOT NULL COMMENT 'Actor category supplied by the host application.',
                                         actor_id BIGINT NULL COMMENT 'Host-provided actor identifier; no foreign key.',

    -- What happened (authoritative state change)
                                         action VARCHAR(128) NOT NULL COMMENT 'Authoritative state-change action name.',

    -- What was affected
                                         target_type VARCHAR(64) NOT NULL COMMENT 'Type of resource affected by the event.',
                                         target_id BIGINT NULL COMMENT 'Host-provided affected-resource identifier; no foreign key.',

    -- Optional structured changes (MUST remain minimal; never store secrets)
                                         changes JSON NULL COMMENT 'Minimal structured state changes; no secrets.',

    -- Request context
                                         ip_address VARCHAR(45) NULL COMMENT 'Request IP address captured according to host policy.',
                                         user_agent VARCHAR(512) NULL COMMENT 'Request user-agent value captured according to host policy.',

    -- Correlation to other logs / traces
                                         correlation_id CHAR(36) NULL COMMENT 'Identifier correlating this event with related logs.',

    -- Materialized timestamp (usually equals business occurred time)
                                         occurred_at DATETIME(6) NOT NULL COMMENT 'Timestamp of the authoritative event.',

                                         UNIQUE KEY uq_auth_audit_log_event_id (event_id),

    -- Cursor index for stable paging / future batch processing
                                         INDEX idx_auth_audit_log_time (occurred_at, id),

    -- Common investigations
                                         INDEX idx_auth_audit_log_actor_time (actor_type, actor_id, occurred_at),
                                         INDEX idx_auth_audit_log_target_time (target_type, target_id, occurred_at),
                                         INDEX idx_auth_audit_log_correlation_time (correlation_id, occurred_at),
                                         INDEX idx_auth_audit_log_action_time (action, occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Materialized authoritative audit log. MUST be written ONLY by outbox consumer. Not authoritative. Changes must be structured & minimal; no secrets.';
