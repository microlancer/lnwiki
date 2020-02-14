CREATE TABLE IF NOT EXISTS `sessions` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `sessionId` varchar(32) NOT NULL UNIQUE,
    `access` int(10) unsigned DEFAULT NULL,
    `data` text
) ENGINE=InnoDB;

-- Add index to last access time for sessions

ALTER TABLE `sessions` ADD KEY `access` (`access`);


ALTER TABLE `sessions`
ADD `activeLock` tinyint(1) NOT NULL DEFAULT '1' AFTER `access`;

create table if not exists `pages` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` varchar(32) NOT NULL,
    `createdTs` timestamp default current_timestamp,
    `content` text
   ) engine = InnoDB;

alter table `pages` add key `name` (`name`);
alter table `pages` add key `createdTs` (`createdTs`);

insert into pages (name, content) values ('', 'Welcome to lnwiki!');

CREATE TABLE IF NOT EXISTS `invoices` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `pageId` bigint(20) NOT NULL,
  `bolt11` varchar(2048) NOT NULL,
  `label` varchar(1024) NOT NULL,
  `status` int (11) NOT NULL,
  `createdTs` datetime NOT NULL,
  `updatedTs` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


alter table `invoices` add key `pageId` (`pageId`);
alter table `invoices` add key `status` (`status`);

