CREATE TABLE maa_event_logging_delivery_operations (
                                     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Database-generated delivery-operation row identifier.',

                                     event_id CHAR(36) NOT NULL COMMENT 'Application event identifier used for idempotency and tracing.',

    -- Delivery channel (email/telegram/sms/webhook/push/job)
                                     channel VARCHAR(32) NOT NULL COMMENT 'Delivery channel used by the operation.',

    -- Operation type (notification_send/webhook_deliver/job_run/...)
                                     operation_type VARCHAR(64) NOT NULL COMMENT 'Type of delivery operation being tracked.',

    -- Who initiated the operation (can be NULL for pure system jobs)
                                     actor_type VARCHAR(32) NULL COMMENT 'Optional actor category supplied by the host application.',
                                     actor_id BIGINT NULL COMMENT 'Optional host-provided actor identifier; no foreign key.',

    -- Target of the delivery (optional)
                                     target_type VARCHAR(64) NULL COMMENT 'Optional type of delivery target.',
                                     target_id BIGINT NULL COMMENT 'Optional host-provided target identifier; no foreign key.',

    -- Status (queued/sent/delivered/failed/retrying/cancelled...)
                                     status VARCHAR(32) NOT NULL COMMENT 'Current lifecycle status of the delivery operation.',

    -- Retry counter
                                     attempt_no INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Zero-based delivery attempt count.',

    -- Lifecycle timestamps (optional)
                                     scheduled_at DATETIME(6) NULL COMMENT 'Optional scheduled delivery timestamp.',
                                     completed_at DATETIME(6) NULL COMMENT 'Optional timestamp when delivery completed.',

                                     correlation_id CHAR(36) NULL COMMENT 'Identifier correlating this event with related logs.',
                                     request_id VARCHAR(64) NULL COMMENT 'Host request identifier for pipeline tracing.',

    -- Provider metadata (optional)
                                     provider VARCHAR(64) NULL COMMENT 'Provider name used for the delivery attempt.',
                                     provider_message_id VARCHAR(128) NULL COMMENT 'Message identifier returned by the delivery provider.',

    -- Failure details (best-effort; no secrets)
                                     error_code VARCHAR(64) NULL COMMENT 'Provider or application code for a delivery failure.',
                                     error_message TEXT NULL COMMENT 'Best-effort delivery error detail; no secrets.',

    -- Additional metadata (MUST NOT contain secrets)
                                     metadata JSON NOT NULL COMMENT 'Structured delivery metadata; must not contain secrets.',

                                     occurred_at DATETIME(6) NOT NULL COMMENT 'Timestamp when the delivery operation occurred.',

                                     UNIQUE KEY uq_el_delivery_ops_event_id (event_id),

    -- Cursor index for stable paging / future batch processing
                                     INDEX idx_delivery_ops_time (occurred_at, id),

    -- Required search dimensions
                                     INDEX idx_delivery_ops_actor_time (actor_type, actor_id, occurred_at),
                                     INDEX idx_delivery_ops_channel_time (channel, occurred_at),
                                     INDEX idx_delivery_ops_type_time (operation_type, occurred_at),
                                     INDEX idx_delivery_ops_status_time (status, occurred_at),

    -- Deep investigations
                                     INDEX idx_delivery_ops_target_time (target_type, target_id, occurred_at),

    -- Correlation helpers
                                     INDEX idx_delivery_ops_correlation_time (correlation_id, occurred_at),
                                     INDEX idx_delivery_ops_request_time (request_id, occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Delivery/system operations for notifications, queues, jobs, webhooks. Searchable by channel/type/status+time and actor+time. Track retries & providers. No secrets in metadata.';
