CREATE TABLE owner_tokens(
  owner_id int(11) NOT NULL,
  api_token varchar(255) CHARACTER SET 'latin1',
  created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`owner_id`),
  UNIQUE uniq_token (`api_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

