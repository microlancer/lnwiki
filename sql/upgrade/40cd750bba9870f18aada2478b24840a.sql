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

