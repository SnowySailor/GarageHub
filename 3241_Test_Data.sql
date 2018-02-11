USE `cse3241_project`;

DROP PROCEDURE IF EXISTS `populate_test_data`;
DELIMITER //

-- Have to create a procedure to use a while loop
CREATE PROCEDURE populate_test_data()
BEGIN
	DELETE FROM `parking_spot`;
	DELETE FROM `garage`;
	DELETE FROM `user`;

	-- Create 6 users. All passwords are 'hello'
	INSERT INTO `user` (`id`, `name`, `login_name`, `password`) VALUES
	(0, 'user0', 'user0@sample.com', '$2y$10$h91Prab2RsL9pfuh8Vtkt.4dUEXFrwNBzybxTCOatQ0LiibyHexG2'),
	(1, 'user1', 'user1@sample.com', '$2y$10$h91Prab2RsL9pfuh8Vtkt.4dUEXFrwNBzybxTCOatQ0LiibyHexG2'),
	(2, 'user2', 'user2@sample.com', '$2y$10$h91Prab2RsL9pfuh8Vtkt.4dUEXFrwNBzybxTCOatQ0LiibyHexG2'),
	(3, 'user3', 'user3@sample.com', '$2y$10$h91Prab2RsL9pfuh8Vtkt.4dUEXFrwNBzybxTCOatQ0LiibyHexG2'),
	(4, 'user4', 'user4@sample.com', '$2y$10$h91Prab2RsL9pfuh8Vtkt.4dUEXFrwNBzybxTCOatQ0LiibyHexG2'),
	(5, 'user5', 'user5@sample.com', '$2y$10$h91Prab2RsL9pfuh8Vtkt.4dUEXFrwNBzybxTCOatQ0LiibyHexG2'),
	(6, 'user6', 'user6@sample.com', '$2y$10$h91Prab2RsL9pfuh8Vtkt.4dUEXFrwNBzybxTCOatQ0LiibyHexG2');

	SET @i = 0;
	SET @j = 0;
	SET @k = 0;
	WHILE (@i < 50) DO
		-- Assign all garages to the users
		INSERT INTO `garage` (`id`, `name`, `managed_by`) VALUES (@i, CONCAT('garage', @i), @i%7);

		-- Loop over floors, give each garage 6 floors
		WHILE (@j < 6) DO
			-- Loop over spots, give each floor 100 spots
			WHILE (@k < 100) DO
				-- Insert the spot
				INSERT INTO `parking_spot` (`floor_no`, `spot_no`, `garage_id`) VALUES (@j, @k, @i);
				SET @k = @k+1;
			END WHILE;
			SET @j = @j+1;
		END WHILE;

		SET @i = @i+1;
		SET @j = 0;
		SET @k = 0;
	END WHILE;
END;
//

-- Call procedure
CALL populate_test_data();
