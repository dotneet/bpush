CREATE TABLE owners(
  id int(11) NOT NULL AUTO_INCREMENT,
  mail varchar(255) NOT NULL,
  password varchar(255) NOT NULL,
  confirm_token varchar(128) NULL,
  status tinyint(4) NOT NULL,
  grade tinyint(4) NOT NULL,
  suspended TIMESTAMP NULL,
  created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE uniq_token (`confirm_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

