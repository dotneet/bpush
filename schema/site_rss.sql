CREATE TABLE site_rss (
  site_id int(11) NOT NULL,
  feed_url varchar(1024) NOT NULL,
  last_modified TIMESTAMP NOT NULL,
  created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

