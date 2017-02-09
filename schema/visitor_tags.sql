CREATE TABLE visitor_tags(
  site_id int(11) NOT NULL,
  visitor_id varchar(60) CHARACTER SET 'latin1' NOT NULL,
  tag varchar(60) NOT NULL,
  created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`site_id`, `visitor_id`, `tag`),
  INDEX idx_tag(`tag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

