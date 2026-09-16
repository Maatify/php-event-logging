CREATE TABLE maa_event_logging_behavior_trace (
                                      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Database-generated behavior-trace row identifier.',

                                      event_id CHAR(36) NOT NULL COMMENT 'Application event identifier used for idempotency and tracing.',

                                      actor_type VARCHAR(32) NOT NULL COMMENT 'Actor category supplied by the host application.',
                                      actor_id BIGINT NULL COMMENT 'Host-provided actor identifier; no foreign key.',

    -- Examples: create/update/delete/bulk_action/report_run...
                                      action VARCHAR(128) NOT NULL COMMENT 'Mutation action performed by the operation.',

    -- Affected entity (optional)
                                      entity_type VARCHAR(64) NULL COMMENT 'Optional type of entity affected by the operation.',
                                      entity_id BIGINT NULL COMMENT 'Host-provided affected-entity identifier; no foreign key.',

    -- Metadata about the operation (MUST NOT contain secrets)
                                      metadata JSON NOT NULL COMMENT 'Structured operation metadata; must not contain secrets.',

                                      correlation_id CHAR(36) NULL COMMENT 'Identifier correlating this event with related logs.',
                                      request_id VARCHAR(64) NULL COMMENT 'Host request identifier for pipeline tracing.',
                                      route_name VARCHAR(255) NULL COMMENT 'Application route name associated with the operation.',

                                      ip_address VARCHAR(45) NULL COMMENT 'Request IP address captured according to host policy.',
                                      user_agent VARCHAR(512) NULL COMMENT 'Request user-agent value captured according to host policy.',

                                      occurred_at DATETIME(6) NOT NULL COMMENT 'Timestamp when the operation occurred.',

                                      UNIQUE KEY uq_el_behavior_trace_event_id (event_id),

    -- Cursor index for stable paging / future batch processing
                                      INDEX idx_el_behavior_trace_time (occurred_at, id),

    -- Required search dimensions
                                      INDEX idx_el_behavior_trace_actor_time (actor_type, actor_id, occurred_at),
                                      INDEX idx_el_behavior_trace_action_time (action, occurred_at),

    -- Deep investigations
                                      INDEX idx_el_behavior_trace_entity_time (entity_type, entity_id, occurred_at),

    -- Correlation helpers
                                      INDEX idx_el_behavior_trace_corr_time (correlation_id, occurred_at),
                                      INDEX idx_el_behavior_trace_request_time (request_id, occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Operational activity for mutations only. Searchable by actor+time and action+time. Views/reads are NOT allowed (use maa_event_logging_audit_trail).';
