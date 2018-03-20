/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;

CREATE DATABASE IF NOT EXISTS `cse3241_project`;
USE `cse3241_project`;

CREATE TABLE IF NOT EXISTS `user` (
    `id`           int unsigned  PRIMARY KEY,
    `user_group`   int           NOT NULL DEFAULT 1,
    `name`         nvarchar(30)  NOT NULL,
    `login_name`   nvarchar(100) NOT NULL,
    `password`     varchar(60)   NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `garage` (
    `id`           int unsigned PRIMARY KEY,
    `name`         nvarchar(30)  NOT NULL,
    `address`      nvarchar(100) NOT NULL,
    `city`         nvarchar(50)  NOT NULL,
    `region`       nvarchar(50)      NULL,
    `country`      nvarchar(50)  NOT NULL,
    `managed_by`   int unsigned,
    CONSTRAINT `managed_by_user_fk` FOREIGN KEY (`managed_by`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `parking_spot` (
    `garage_id`    int unsigned NOT NULL,
    `floor_no`     int unsigned NOT NULL,
    `spot_no`      int unsigned NOT NULL,
    `state`        int          NOT NULL DEFAULT 1,
    PRIMARY KEY (`garage_id`, `floor_no`, `spot_no`),
    CONSTRAINT `garage_id_fk` FOREIGN KEY (`garage_id`) REFERENCES `garage` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
