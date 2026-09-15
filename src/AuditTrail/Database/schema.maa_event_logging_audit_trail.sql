
-- ==========================================================
-- 2) Audit Trail (DATA ACCESS / VIEWS / NAVIGATION / EXPORTS)
-- ----------------------------------------------------------
-- Answers: "Who accessed what sensitive thing, when?"
-- This is NOT authoritative state change.
-- ==========================================================

CREATE TABLE maa_event_logging_audit_trail (
                             id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Database-generated audit trail row identifier.',

    -- UUID per row/event (portable)
                             event_id CHAR(36) NOT NULL COMMENT 'Application event identifier used for idempotency and tracing.',

    -- Who performed the access
                             actor_type VARCHAR(32) NOT NULL COMMENT 'Actor category supplied by the host application.',
                             actor_id BIGINT NULL COMMENT 'Host-provided actor identifier; no foreign key.',

    -- Examples: customer.view, customer.export, page.visit, invoice.download
                             event_key VARCHAR(255) NOT NULL COMMENT 'Application-defined key describing the accessed event.',

    -- Accessed resource (what was touched)
                             entity_type VARCHAR(64) NOT NULL COMMENT 'Type of resource accessed by the event.',
                             entity_id BIGINT NULL COMMENT 'Host-provided resource identifier; no foreign key.',

    -- "On behalf of" / data subject (e.g., the customer whose data was viewed)
                             subject_type VARCHAR(64) NULL COMMENT 'Type of data subject represented by the event.',
                             subject_id BIGINT NULL COMMENT 'Host-provided data subject identifier; no foreign key.',

    -- Navigation context (store sanitized values; DO NOT store sensitive query strings)
                             referrer_route_name VARCHAR(255) NULL COMMENT 'Sanitized route name that led to the access.',
                             referrer_path VARCHAR(1024) NULL COMMENT 'Sanitized referrer path without query secrets.',  -- recommended: path only, no query
                             referrer_host VARCHAR(255) NULL COMMENT 'Optional host portion of the sanitized referrer.',   -- optional

    -- Correlation to request pipeline
                             correlation_id CHAR(36) NULL COMMENT 'Identifier correlating this event with related logs.',
                             request_id VARCHAR(64) NULL COMMENT 'Host request identifier for pipeline tracing.',
                             route_name VARCHAR(255) NULL COMMENT 'Application route name associated with the request.',

    -- Request context
                             ip_address VARCHAR(45) NULL COMMENT 'Request IP address captured according to host policy.',
                             user_agent VARCHAR(512) NULL COMMENT 'Request user-agent value captured according to host policy.',

    -- Extra metadata (MUST NOT contain secrets)
                             metadata JSON NOT NULL COMMENT 'Structured event metadata; must not contain secrets.',

                             occurred_at DATETIME(6) NOT NULL COMMENT 'Timestamp when the access event occurred.',

                             UNIQUE KEY uq_el_audit_trail_event_id (event_id),

    -- Cursor index for stable paging / future batch processing
                             INDEX idx_el_audit_trail_time (occurred_at, id),

    -- Common investigations (two required search dimensions: actor+time and key+time)
                             INDEX idx_el_audit_trail_actor_time (actor_type, actor_id, occurred_at),
                             INDEX idx_el_audit_trail_event_time (event_key, occurred_at),

    -- Deep investigations
                             INDEX idx_el_audit_trail_entity_time (entity_type, entity_id, occurred_at),
                             INDEX idx_el_audit_trail_subject_time (subject_type, subject_id, occurred_at),

    -- Correlation helpers
                             INDEX idx_el_audit_trail_corr_time (correlation_id, occurred_at),
                             INDEX idx_el_audit_trail_request_time (request_id, occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Audit Trail: records data exposure/navigation/views/exports. Searchable by actor+time and event_key+time. Store sanitized referrer_path (no tokens/OTP/query secrets).';
