/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;

DROP DATABASE IF EXISTS       `cse3241_project`;
CREATE DATABASE IF NOT EXISTS `cse3241_project`;
USE `cse3241_project`;

DROP TABLE IF EXISTS `user`;

CREATE TABLE `user` (
    `id`           int unsigned  PRIMARY KEY,
    `user_group`   int           NOT NULL DEFAULT 1,
    `name`         varchar(30)   NOT NULL,
    `login_name`   nvarchar(100) NOT NULL,
    `password`     varchar(60)   NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `garage`;

CREATE TABLE `garage` (
    `id`           int unsigned PRIMARY KEY,
    `name`         varchar(30)  NOT NULL,
    `managed_by`   int unsigned,
    CONSTRAINT `managed_by_user_fk` FOREIGN KEY (`managed_by`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `parking_spot`;

CREATE TABLE `parking_spot` (
    `garage_id`    int unsigned NOT NULL,
    `floor_no`     int unsigned NOT NULL,
    `spot_no`      int unsigned NOT NULL,
    `state`        int          NOT NULL DEFAULT 1,
    PRIMARY KEY (`garage_id`, `floor_no`, `spot_no`),
    CONSTRAINT `garage_id_fk` FOREIGN KEY (`garage_id`) REFERENCES `garage` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
