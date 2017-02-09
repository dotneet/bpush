CREATE TABLE send_logs (
  site_id int(11) NOT NULL,
  sent_at TIMESTAMP NOT NULL,
  target_count int(11) NOT NULL COMMENT '送信件数',
  KEY `idx_time` (`site_id`, `sent_at`),
  KEY `idx_sent_at` (`sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

