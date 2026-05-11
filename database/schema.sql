CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('passenger', 'driver', 'admin') NOT NULL DEFAULT 'passenger',
  phone VARCHAR(40) NULL,
  join_date DATE NULL,
  license_number VARCHAR(80) NULL,
  bus_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS routes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  number VARCHAR(30) NOT NULL UNIQUE,
  status VARCHAR(40) NOT NULL DEFAULT 'active',
  stops JSON NOT NULL,
  schedule JSON NOT NULL,
  fare DECIMAL(10, 2) NOT NULL DEFAULT 0,
  distance VARCHAR(40) NULL,
  duration VARCHAR(40) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS buses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  number VARCHAR(60) NOT NULL UNIQUE,
  plate VARCHAR(60) NOT NULL UNIQUE,
  make VARCHAR(80) NULL,
  capacity INT NOT NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'active',
  route_id INT NULL,
  driver_id INT NULL,
  INDEX idx_buses_route_id (route_id),
  INDEX idx_buses_driver_id (driver_id),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tickets (
  id VARCHAR(30) PRIMARY KEY,
  user_id INT NULL,
  route_id INT NOT NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'available',
  issue_date DATE NOT NULL,
  expiry_date DATE NOT NULL,
  type VARCHAR(60) NOT NULL,
  INDEX idx_tickets_user_id (user_id),
  INDEX idx_tickets_route_id (route_id),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS trips (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  route_id INT NOT NULL,
  bus_id INT NOT NULL,
  trip_date DATE NOT NULL,
  departure TIME NOT NULL,
  arrival TIME NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'scheduled',
  rating INT NULL,
  feedback TEXT NULL,
  INDEX idx_trips_user_id (user_id),
  INDEX idx_trips_route_id (route_id),
  INDEX idx_trips_bus_id (bus_id),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  route_id INT NOT NULL,
  booking_time TIME NOT NULL,
  stop VARCHAR(160) NOT NULL,
  seats INT NOT NULL DEFAULT 1,
  status VARCHAR(40) NOT NULL DEFAULT 'scheduled',
  booking_date DATE NOT NULL,
  lat DECIMAL(10, 6) NULL,
  lng DECIMAL(10, 6) NULL,
  arrived BOOLEAN NOT NULL DEFAULT FALSE,
  INDEX idx_bookings_user_id (user_id),
  INDEX idx_bookings_route_id (route_id),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS announcements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(180) NOT NULL,
  message TEXT NOT NULL,
  announcement_date DATE NOT NULL,
  author VARCHAR(120) NOT NULL,
  priority VARCHAR(40) NOT NULL DEFAULT 'low',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  type VARCHAR(60) NOT NULL,
  message TEXT NOT NULL,
  notification_time TIME NOT NULL,
  notification_date DATE NOT NULL,
  is_read BOOLEAN NOT NULL DEFAULT FALSE,
  INDEX idx_notifications_user_id (user_id),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS complaints (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  route_id INT NULL,
  type VARCHAR(80) NOT NULL,
  description TEXT NOT NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'in-review',
  complaint_date DATE NOT NULL,
  INDEX idx_complaints_user_id (user_id),
  INDEX idx_complaints_route_id (route_id),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS active_trips (
  id INT AUTO_INCREMENT PRIMARY KEY,
  driver_id INT NOT NULL,
  bus_id INT NOT NULL,
  route_id INT NOT NULL,
  start_time TIME NOT NULL,
  trip_date DATE NOT NULL,
  INDEX idx_active_trips_driver_id (driver_id),
  INDEX idx_active_trips_bus_id (bus_id),
  INDEX idx_active_trips_route_id (route_id),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS seat_reservations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  bus_id INT NOT NULL,
  seat_number INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_bus_seat (bus_id, seat_number),
  INDEX idx_seat_reservations_bus_id (bus_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chat_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  driver_id INT NOT NULL,
  passenger_id INT NOT NULL,
  sender_role ENUM('driver', 'passenger') NOT NULL,
  message TEXT NOT NULL,
  sent_time TIME NOT NULL,
  sent_date DATE NOT NULL,
  is_read BOOLEAN NOT NULL DEFAULT FALSE,
  INDEX idx_chat_messages_driver_id (driver_id),
  INDEX idx_chat_messages_passenger_id (passenger_id),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
