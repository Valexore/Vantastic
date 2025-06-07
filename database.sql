--databases


CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','manager','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verification_token` varchar(255) DEFAULT NULL,
  `verification_token_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Terminals table
CREATE TABLE `terminals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `location` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Destinations table
CREATE TABLE `destinations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `base_fare` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert sample destinations
INSERT INTO `destinations` (`name`, `base_fare`) VALUES
('Manila Terminal', 350.00),
('Infanta Terminal', 350.00),
('Real, Quezon Province', 350.00),
('Sta. Maria, Laguna', 350.00),
('Baras, Rizal', 350.00),
('Pinugay, Antipolo', 350.00),
('Boso-Boso, Antipolo', 350.00),
('Padilla, Antipolo', 350.00),
('Cogeo Avenue, Antipolo', 350.00),
('Marikina', 350.00),
('Rizal', 350.00),
('Quezon City', 350.00),
('Sta. Mesa, Manila', 350.00),
('Famy, Laguna', 350.00),
('Mabitac, Laguna', 350.00),
('Pililla, Rizal', 350.00),
('Tanay, Rizal', 350.00),
('Morong, Rizal', 350.00),
('Teresa, Rizal', 350.00),
('Antipolo, Rizal', 350.00),
('Masinag, Antipolo', 350.00),
('San Juan, Manila', 350.00),
('Legarda, Sampaloc, Manila', 350.00),
('Sta. Teresita, Sampaloc, Manila', 350.00);

-- Tickets table
CREATE TABLE `tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `terminal_id` int(11) NOT NULL,
  `destination_id` int(11) NOT NULL,
  `passenger_count` int(11) NOT NULL,
  `baggage_count` int(11) NOT NULL DEFAULT 0,
  `travel_date` date NOT NULL,
  `travel_time` time NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0,
  `status` enum('upcoming','completed','cancelled') NOT NULL DEFAULT 'upcoming',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`terminal_id`) REFERENCES `terminals`(`id`),
  FOREIGN KEY (`destination_id`) REFERENCES `destinations`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Ratings table
CREATE TABLE `ratings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `stars` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert sample terminals
INSERT INTO `terminals` (`name`, `location`) VALUES
('Manila Terminal', 'Manila, Philippines'),
('Quezon City Terminal', 'Quezon City, Philippines');








-- database for the manager van management
-- Add these to your existing database schema

-- Vans table
CREATE TABLE `vans` (
  `id` varchar(20) NOT NULL,
  `license_plate` varchar(20) NOT NULL,
  `model` varchar(100) NOT NULL,
  `terminal_id` int(11) NOT NULL,
  `status` enum('active','maintenance','inactive') NOT NULL DEFAULT 'active',
  `driver_name` varchar(100) DEFAULT NULL,
  `last_maintenance` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`terminal_id`) REFERENCES `terminals`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




-- Add reports table
CREATE TABLE IF NOT EXISTS reports (
  id INT AUTO_INCREMENT PRIMARY KEY,
  report_type ENUM('daily', 'weekly', 'monthly', 'yearly') NOT NULL,
  period_start DATE NOT NULL,
  period_end DATE NOT NULL,
  total_sales DECIMAL(10,2) NOT NULL,
  total_tickets INT NOT NULL,
  completed_tickets INT NOT NULL,
  cancelled_tickets INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add settings table
CREATE TABLE IF NOT EXISTS settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(50) NOT NULL UNIQUE,
  setting_value TEXT NOT NULL,
  description VARCHAR(255),
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default settings
INSERT INTO settings (setting_key, setting_value, description) VALUES
('company_name', 'VanTastic', 'The name of the company'),
('base_fare', '350.00', 'Base fare amount in pesos'),
('fare_per_km', '10.00', 'Additional fare per kilometer'),
('max_passengers', '12', 'Maximum passengers per van'),
('maintenance_interval', '90', 'Days between van maintenance checks'),
('cancellation_fee', '0.00', 'Fee for cancelled tickets'),
('admin_email', 'admin@vantastic.com', 'Primary admin email address');