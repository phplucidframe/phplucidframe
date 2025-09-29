-- Create sample database for LucidFrame
CREATE DATABASE IF NOT EXISTS `lucid_blog` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Grant permissions to lucidframe user for sample database
GRANT ALL PRIVILEGES ON `lucid_blog`.* TO 'lucidframe'@'%';

-- Create a sample table for testing
USE `lucid_blog`;

CREATE TABLE IF NOT EXISTS `posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data
INSERT INTO `posts` (`title`, `content`) VALUES
('Welcome to PHPLucidFrame', 'This is a sample post to test the framework with Docker and Nginx.'),
('Docker Setup Complete', 'Your PHPLucidFrame is now running with Docker, PHP, MySQL, and Nginx!');

FLUSH PRIVILEGES;
