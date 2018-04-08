/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;

CREATE DATABASE IF NOT EXISTS `cse3241_project`;
USE `cse3241_project`;

CREATE TABLE IF NOT EXISTS `user` (
    `id`            int unsigned  PRIMARY KEY,
    `user_group`    int           NOT NULL DEFAULT 1,
    `name`          nvarchar(30)  NOT NULL,
    `login_name`    nvarchar(100) NOT NULL,
    `password_hash` varchar(60)   NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `garage` (
    `id`           int unsigned PRIMARY KEY,
    `name`         nvarchar(30)  NOT NULL,
    `address`      nvarchar(100) NOT NULL DEFAULT '',
    `city`         nvarchar(50)  NOT NULL DEFAULT '',
    `region`       nvarchar(50)      NULL DEFAULT '',
    `country`      nvarchar(50)  NOT NULL DEFAULT '',
    `managed_by`   int unsigned,
    CONSTRAINT `garage:managed_by` FOREIGN KEY (`managed_by`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `parking_spot` (
    `garage_id`    int unsigned NOT NULL,
    `floor_no`     int unsigned NOT NULL,
    `spot_no`      int unsigned NOT NULL,
    `state`        int          NOT NULL DEFAULT 1,
    PRIMARY KEY (`garage_id`, `floor_no`, `spot_no`),
    CONSTRAINT `parking_spot:garage_id` FOREIGN KEY (`garage_id`) REFERENCES `garage` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `report_data` (
    `garage_id`    int unsigned NOT NULL,
    `time`         datetime     NOT NULL,
    `data`         mediumtext   NOT NULL,
    PRIMARY KEY (`garage_id`, `time`),
    CONSTRAINT `report_data:garage_id` FOREIGN KEY (`garage_id`) REFERENCES `garage` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELIMITER //
CREATE EVENT IF NOT EXISTS `report_data`
ON SCHEDULE
-- Run every day starting at the next midnight
EVERY 1 DAY
STARTS (TIMESTAMP(CURRENT_DATE) + INTERVAL 1 DAY + INTERVAL 1 HOUR)
DO
BEGIN
    INSERT INTO `report_data` (`garage_id`, `time`, `data`)
    SELECT G.id, UTC_TIMESTAMP(), CONCAT('[', GROUP_CONCAT(JSON_OBJECT('Floor', P.floor_no, 'Spot', P.spot_no, 'State', state)), ']')
    FROM `garage` G INNER JOIN parking_spot P ON G.id = P.garage_id GROUP BY G.id;
END //
DELIMITER ;

/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
