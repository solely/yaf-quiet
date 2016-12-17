create table if not exists test (
  id int unsigned PRIMARY KEY auto_increment,
  name CHAR(32) not null default '',
  sex TINYINT(1) not null default 0,
  age int unsigned not null DEFAULT 0
)ENGINE innodb DEFAULT CHAR SET utf8;