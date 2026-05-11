SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE chat_messages;
TRUNCATE TABLE seat_reservations;
TRUNCATE TABLE complaints;
TRUNCATE TABLE active_trips;
TRUNCATE TABLE notifications;
TRUNCATE TABLE announcements;
TRUNCATE TABLE bookings;
TRUNCATE TABLE trips;
TRUNCATE TABLE tickets;
TRUNCATE TABLE buses;
TRUNCATE TABLE routes;
TRUNCATE TABLE users;
SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO users (id, name, email, password_hash, role, phone, join_date, license_number, bus_id) VALUES
(1, 'John Passenger', 'user@smartbus.com', 'ef92b778bafe771e89245b89ecbc08a44a4e166c06659911881f383d4473e94f', 'passenger', '082 111 2222', '2025-03-01', NULL, NULL),
(2, 'Sarah User', 'sarah@smartbus.com', 'ef92b778bafe771e89245b89ecbc08a44a4e166c06659911881f383d4473e94f', 'passenger', '082 333 4444', '2025-04-15', NULL, NULL),
(3, 'Mike Driver', 'driver@smartbus.com', '494d022492052a06f8f81949639a1d148c1051fa3d4e4688fbd96efe649cd382', 'driver', '082 555 6666', NULL, 'DL-2024-001', NULL),
(4, 'Linda Driver', 'linda@smartbus.com', '494d022492052a06f8f81949639a1d148c1051fa3d4e4688fbd96efe649cd382', 'driver', '082 777 8888', NULL, 'DL-2024-002', NULL),
(5, 'Admin User', 'admin@smartbus.com', '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9', 'admin', '011 222 3333', NULL, NULL, NULL);

INSERT INTO routes (id, name, number, status, stops, schedule, fare, distance, duration) VALUES
(1, 'City Centre - Soweto', 'R1', 'active', JSON_ARRAY('City Centre', 'Park Station', 'Newtown', 'Crown Mines', 'Soweto'), JSON_ARRAY('06:00','07:00','08:00','09:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00','18:00'), 15.00, '22 km', '45 min'),
(2, 'Sandton - Alexandra', 'R2', 'active', JSON_ARRAY('Sandton Station', 'Marlboro', 'Wynberg', 'Alexandra'), JSON_ARRAY('06:30','07:30','08:30','09:30','11:00','13:00','15:00','17:00','19:00'), 12.00, '14 km', '30 min'),
(3, 'Pretoria - Centurion', 'R3', 'active', JSON_ARRAY('Pretoria CBD', 'Sunnyside', 'Arcadia', 'Hatfield', 'Centurion'), JSON_ARRAY('05:30','06:30','07:30','08:30','09:30','12:00','14:00','16:00','18:00'), 18.00, '28 km', '55 min'),
(4, 'Cape Town - Bellville', 'R4', 'suspended', JSON_ARRAY('Cape Town Station', 'Salt River', 'Goodwood', 'Bellville'), JSON_ARRAY('07:00','09:00','12:00','15:00','18:00'), 20.00, '25 km', '50 min');

INSERT INTO buses (id, number, plate, make, capacity, status, route_id, driver_id) VALUES
(1, 'BUS-001', 'CA 123 GP', 'Mercedes', 40, 'active', 1, 3),
(2, 'BUS-002', 'CA 456 GP', 'Volvo', 36, 'active', 2, 4),
(3, 'BUS-003', 'CA 789 GP', 'MAN', 45, 'maintenance', NULL, NULL);

UPDATE users SET bus_id = 1 WHERE id = 3;
UPDATE users SET bus_id = 2 WHERE id = 4;

INSERT INTO tickets (id, user_id, route_id, status, issue_date, expiry_date, type) VALUES
('SB001', 1, 1, 'active', '2025-05-01', '2025-12-31', 'Monthly'),
('SB002', 2, 2, 'active', '2025-04-15', '2025-12-31', 'Monthly'),
('SB003', 1, 3, 'used', '2025-01-10', '2025-01-31', 'Single Trip'),
('SB010', NULL, 1, 'available', '2025-05-09', '2026-05-09', 'Single Trip'),
('SB011', NULL, 2, 'available', '2025-05-09', '2026-05-09', 'Monthly');

INSERT INTO trips (id, user_id, route_id, bus_id, trip_date, departure, arrival, status, rating, feedback) VALUES
(1, 1, 1, 1, '2025-05-08', '08:00:00', '08:47:00', 'completed', 4, 'Good trip, driver was on time'),
(2, 1, 2, 2, '2025-05-07', '07:30:00', '08:02:00', 'completed', 5, 'Excellent service!'),
(3, 1, 1, 1, '2025-05-06', '09:00:00', '09:52:00', 'completed', 3, 'Bus was slightly delayed'),
(4, 2, 2, 2, '2025-05-08', '11:00:00', '11:33:00', 'completed', 4, '');

INSERT INTO bookings (id, user_id, route_id, booking_time, stop, seats, status, booking_date, lat, lng, arrived) VALUES
(1, 1, 1, '08:00:00', 'Park Station', 1, 'scheduled', '2025-05-09', -26.204100, 28.047300, FALSE),
(2, 2, 2, '07:30:00', 'Marlboro', 2, 'scheduled', '2025-05-09', -26.087000, 28.101000, FALSE);

INSERT INTO notifications (id, user_id, type, message, notification_time, notification_date, is_read) VALUES
(1, 1, 'delay', 'Route R1 bus is delayed by 10 minutes due to traffic near Crown Mines.', '08:15:00', '2025-05-09', FALSE),
(2, 1, 'announcement', 'New bus stop added at Park Station. Route R1 now stops at the new platform.', '09:00:00', '2025-05-08', FALSE),
(3, 1, 'info', 'Your monthly ticket SB001 expires on 31 December 2025.', '12:00:00', '2025-05-07', TRUE);

INSERT INTO announcements (id, title, message, announcement_date, author, priority) VALUES
(1, 'New Weekend Schedule', 'Weekend bus schedules have been updated for June 2026. Please check the routes page for new times.', '2025-05-09', 'SmartBus Admin', 'high'),
(2, 'Maintenance - BUS-003', 'BUS-003 (Route R4) will be under maintenance from 10-15 May. Alternative buses have been arranged.', '2025-05-08', 'SmartBus Admin', 'medium'),
(3, 'Safety Reminder', 'Please keep your belongings safe and report any suspicious activity to the driver immediately.', '2025-05-06', 'SmartBus Admin', 'low');

INSERT INTO complaints (id, user_id, route_id, type, description, status, complaint_date) VALUES
(1, 1, NULL, 'delay', 'Bus arrived 20 minutes late on Route R1 on 07 May.', 'in-review', '2025-05-07'),
(2, 2, NULL, 'cleanliness', 'The bus was very dirty inside.', 'resolved', '2025-05-05');

INSERT INTO seat_reservations (bus_id, seat_number) VALUES
(1, 2), (1, 5), (1, 8), (1, 12), (1, 15), (1, 18), (1, 20), (1, 23),
(2, 1), (2, 3), (2, 7), (2, 9), (2, 11), (2, 16);

INSERT INTO chat_messages (driver_id, passenger_id, sender_role, message, sent_time, sent_date, is_read) VALUES
(3, 1, 'driver', 'Hi John, bus is running on time today!', '07:45:00', '2025-05-09', TRUE),
(3, 1, 'passenger', 'Thanks Mike, I am at Park Station waiting.', '07:47:00', '2025-05-09', TRUE);
