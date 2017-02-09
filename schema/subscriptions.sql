CREATE TABLE subscriptions(
  id int(11) NOT NULL AUTO_INCREMENT,
  site_id int(11) NOT NULL,
  visitor_id varchar(60) CHARACTER SET 'latin1',
  endpoint_arn varchar(255) CHARACTER SET 'latin1',
  endpoint varchar(512) CHARACTER SET 'latin1',
  auth_token varchar(80) CHARACTER SET 'latin1',
  p256dh varchar(256) CHARACTER SET 'latin1',
  subscription_id varchar(255) CHARACTER SET 'latin1',
  ip_address varchar(40) NULL DEFAULT NULL,
  user_agent varchar(512) NULL DEFAULT NULL,
  locale varchar(32) NULL DEFAULT NULL,
  created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY unique_site_id_subscription_id (`site_id`, `subscription_id`),
  UNIQUE KEY unique_site_id_visitor_id (`site_id`, `visitor_id`),
  INDEX idx_subscription_id (`subscription_id`),
  INDEX idx_endpoint (`endpoint`),
  INDEX idx_endpoint_arn (`endpoint_arn`),
  INDEX idx_visitor_id (`visitor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

