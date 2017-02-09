CREATE TABLE notifications (
  id int(11) NOT NULL AUTO_INCREMENT,
  site_id int(11) NOT NULL,
  subject varchar(255) NOT NULL,
  content varchar(1024) NOT NULL,
  post_url varchar(1024) NOT NULL,
  scheduled_at TIMESTAMP NULL,
  sent_at TIMESTAMP NULL,
  visible tinyint(1) NOT NULL DEFAULT true,
  received_count int(11) NOT NULL DEFAULT 0,
  jump_count int(11) NOT NULL DEFAULT 0,
  failure_reason tinyint(4) NOT NULL DEFAULT 0,
  created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX idx_site_sent_at (`site_id`, `sent_at`),
  INDEX idx_site_visible_created (`site_id`, `visible`, `created`),
  INDEX idx_scheduled_at (`scheduled_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

