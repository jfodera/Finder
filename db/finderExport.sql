

/*

Users and Recorder Codes:

- A user signs up for an account but isn't automatically a recorder. The users only become 
recorders if they have a valid recorder code which is inputted when signing up to be a recorder.


1) When a new recorder signs up, they'll provide a recorder code.
2) The code will check the recorder_codes table to validate the code.
3) If valid, a new user is created in the users table with is_recorder set to TRUE.
4) The recorder_codes table is updated to mark the code as used and link it to the new user.

Users and Lost Items:
1) When a user reports a lost item, a new entry is created in the lost_items table.
2) The user_id in lost_items links back to the users table, identifying who reported the item.

Users and Found Items:
1) Only recorders (users with is_recorder set to TRUE) can add found items.
2) When a recorder adds a found item, a new entry is created in the found_items table.
3) The recorder_id in found_items links back to the users table, identifying which recorder added the item.

Lost Items, Found Items, and Matches:

1) When the system attempts to match lost and found items, it creates entries in the matches table.
2) Each match links a lost item (lost_item_id) with a potential found item (found_item_id).
3) The user_id in the matches table refers to the user who reported the lost item.

*/

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";



--
-- Database: `finder`
--

-- --------------------------------------------------------

--
-- Table structure for table `found_items`
--

CREATE TABLE `found_items` (
  `item_id` int(11) NOT NULL,
  `item_type` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `location` varchar(255) NOT NULL,
  `found_time` datetime NOT NULL,
  `status` enum('available','claimed') DEFAULT 'available',
  `recorder_id` int(11) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `image_public_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lost_items`
--

CREATE TABLE `lost_items` (
  `item_id` int(11) NOT NULL,
  `item_type` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `location` varchar(255) NOT NULL,
  `lost_time` datetime NOT NULL,
  `status` enum('lost','found','claimed') DEFAULT 'lost',
  `user_id` int(11) DEFAULT NULL,
  `recorder_id` int(11) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `image_public_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `matches`
--

CREATE TABLE `matches` (
  `match_id` int(11) NOT NULL,
  `lost_item_id` int(11) DEFAULT NULL,
  `found_item_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `match_time` timestamp NULL DEFAULT current_timestamp(),
  `status` enum('pending','confirmed','rejected') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recorder_codes`
--

CREATE TABLE `recorder_codes` (
  `code_id` int(11) NOT NULL,
  `code` varchar(255) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `submission_cooldowns`
--

CREATE TABLE `submission_cooldowns` (
  `cooldown_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `last_submission` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(255) DEFAULT NULL,
  `is_recorder` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `found_items`
--
ALTER TABLE `found_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `recorder_id` (`recorder_id`);

--
-- Indexes for table `lost_items`
--
ALTER TABLE `lost_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `recorder_id` (`recorder_id`);

--
-- Indexes for table `matches`
--
ALTER TABLE `matches`
  ADD PRIMARY KEY (`match_id`),
  ADD KEY `lost_item_id` (`lost_item_id`),
  ADD KEY `found_item_id` (`found_item_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `recorder_codes`
--
ALTER TABLE `recorder_codes`
  ADD PRIMARY KEY (`code_id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `submission_cooldowns`
--
ALTER TABLE `submission_cooldowns`
  ADD PRIMARY KEY (`cooldown_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `found_items`
--
ALTER TABLE `found_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lost_items`
--
ALTER TABLE `lost_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `matches`
--
ALTER TABLE `matches`
  MODIFY `match_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recorder_codes`
--
ALTER TABLE `recorder_codes`
  MODIFY `code_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `submission_cooldowns`
--
ALTER TABLE `submission_cooldowns`
  MODIFY `cooldown_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `found_items`
--
ALTER TABLE `found_items`
  ADD CONSTRAINT `found_items_ibfk_1` FOREIGN KEY (`recorder_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `lost_items`
--
ALTER TABLE `lost_items`
  ADD CONSTRAINT `lost_items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `lost_items_ibfk_2` FOREIGN KEY (`recorder_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `matches`
--
ALTER TABLE `matches`
  ADD CONSTRAINT `matches_ibfk_1` FOREIGN KEY (`lost_item_id`) REFERENCES `lost_items` (`item_id`),
  ADD CONSTRAINT `matches_ibfk_2` FOREIGN KEY (`found_item_id`) REFERENCES `found_items` (`item_id`),
  ADD CONSTRAINT `matches_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `recorder_codes`
--
ALTER TABLE `recorder_codes`
  ADD CONSTRAINT `recorder_codes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `submission_cooldowns`
--
ALTER TABLE `submission_cooldowns`
  ADD CONSTRAINT `submission_cooldowns_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
