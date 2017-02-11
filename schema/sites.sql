CREATE TABLE sites (
  id int(11) NOT NULL AUTO_INCREMENT,
  owner_id int(11) NOT NULL,
  app_key varchar(128) CHARACTER SET latin1 NULL,
  name varchar(255) NOT NULL,
  url varchar(1024) NOT NULL,
  icon varchar(255) NULL,
  badge varchar(1024)  CHARACTER SET latin1 NULL,
  use_list_page tinyint(4) NOT NULL,
  use_rss tinyint(4) NOT NULL DEFAULT 0,
  remove_at TIMESTAMP NULL,
  created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE idx_app_key (`app_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

