-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 07, 2025 at 11:20 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `stagex_db`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_add_show_genre` (IN `in_show_id` INT, IN `in_genre_id` INT)   BEGIN
    INSERT INTO show_genres (show_id, genre_id)
    VALUES (in_show_id, in_genre_id);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_approve_theater` (IN `in_theater_id` INT)   BEGIN
    UPDATE theaters
    SET status = 'Đã hoạt động'
    WHERE theater_id = in_theater_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_can_delete_seat_category` (IN `in_category_id` INT)   BEGIN
    SELECT COUNT(*) AS cnt
    FROM seats s
    JOIN performances p ON s.theater_id = p.theater_id
    WHERE s.category_id = in_category_id
      AND p.status = 'Đang mở bán';
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_can_delete_theater` (IN `in_theater_id` INT)   BEGIN
    SELECT COUNT(*) AS cnt
    FROM performances
    WHERE theater_id = in_theater_id
      AND status = 'Đang mở bán';
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_count_performances_by_show` (IN `in_show_id` INT)   BEGIN
    SELECT COUNT(*) AS performance_count
    FROM performances
    WHERE show_id = in_show_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_count_tickets_by_booking` (IN `in_booking_id` INT)   BEGIN
    SELECT COUNT(*) AS ticket_count
    FROM tickets
    WHERE booking_id = in_booking_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_create_booking` (IN `p_user_id` INT, IN `p_performance_id` INT, IN `p_total` DECIMAL(10,2))   BEGIN
    -- Insert a new booking with default status 'Đang xử lý'
    INSERT INTO bookings (
        user_id,
        performance_id,
        total_amount,
        booking_status,
        created_at
    )
    VALUES (
        p_user_id,
        p_performance_id,
        p_total,
        'Đang xử lý',
        NOW()
    );

    -- Return the newly created booking ID
    SELECT LAST_INSERT_ID() AS booking_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_create_genre` (IN `in_name` VARCHAR(100))   BEGIN
    INSERT INTO genres (genre_name) VALUES (in_name);
    SELECT LAST_INSERT_ID() AS genre_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_create_payment` (IN `in_booking_id` INT, IN `in_amount` DECIMAL(10,3), IN `in_status` VARCHAR(20), IN `in_txn_ref` VARCHAR(255), IN `in_payment_method` VARCHAR(50))   BEGIN
    INSERT INTO payments (booking_id, amount, status, vnp_txn_ref, payment_method, created_at, updated_at)
    VALUES (in_booking_id, in_amount, in_status, in_txn_ref, in_payment_method, NOW(), NOW());
    SELECT LAST_INSERT_ID() AS payment_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_create_performance` (IN `in_show_id` INT, IN `in_theater_id` INT, IN `in_performance_date` DATE, IN `in_start_time` TIME, IN `in_end_time` TIME, IN `in_price` DECIMAL(10,3))   BEGIN
    -- Create the performance and mark it as open for sale.  After
    -- inserting the performance record, copy all seats from the
    -- selected theatre into the seat_performance table so that each
    -- performance maintains its own seat availability.  Seats without
    -- a category (i.e. null category_id) are still copied; the
    -- application will hide these seats from customers.
    DECLARE new_pid INT;
    INSERT INTO performances (show_id, theater_id, performance_date, start_time, end_time, price, status)
    VALUES (in_show_id, in_theater_id, in_performance_date, in_start_time, in_end_time, in_price, 'Đang mở bán');
    SET new_pid = LAST_INSERT_ID();
    INSERT INTO seat_performance (seat_id, performance_id, status)
    SELECT s.seat_id, new_pid, 'trống'
    FROM seats s
    WHERE s.theater_id = in_theater_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_create_review` (IN `in_show_id` INT, IN `in_user_id` INT, IN `in_rating` INT, IN `in_content` TEXT)   BEGIN
    INSERT INTO reviews (show_id, user_id, rating, content, created_at)
    VALUES (in_show_id, in_user_id, in_rating, in_content, NOW());
    SELECT LAST_INSERT_ID() AS review_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_create_seat_category` (IN `in_name` VARCHAR(100), IN `in_base_price` DECIMAL(10,3), IN `in_color_class` VARCHAR(50))   BEGIN
    INSERT INTO seat_categories (category_name, base_price, color_class)
    VALUES (in_name, in_base_price, in_color_class);
    SELECT LAST_INSERT_ID() AS category_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_create_show` (IN `in_title` VARCHAR(255), IN `in_description` TEXT, IN `in_duration` INT, IN `in_director` VARCHAR(255), IN `in_poster` VARCHAR(255), IN `in_status` VARCHAR(50))   BEGIN
    INSERT INTO shows (title, description, duration_minutes, director, poster_image_url, status, created_at)
    VALUES (in_title, in_description, in_duration, in_director, in_poster, in_status, NOW());
    SELECT LAST_INSERT_ID() AS show_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_create_theater` (IN `in_name` VARCHAR(255), IN `in_rows` INT, IN `in_cols` INT)   BEGIN
    -- Declare local variables at the start of the procedure.  MySQL/MariaDB
    -- requires that DECLARE statements appear before any other SQL
    -- statements within the BEGIN...END block.
    DECLARE new_tid INT;
    DECLARE r INT DEFAULT 1;
    DECLARE c INT;

    -- Insert the new theatre and compute its identifier and total seats.  New
    -- theatres are created with status 'Chờ xử lý' to prevent them from
    -- being selected for performances until the admin approves them.
    INSERT INTO theaters (name, total_seats, status)
    VALUES (in_name, in_rows * in_cols, 'Chờ xử lý');
    SET new_tid = LAST_INSERT_ID();

    -- Generate seats for each row and column.  Row characters start at 'A'
    -- (ASCII 65) and increment for each row.  Seat numbers start at 1.
    WHILE r <= in_rows DO
        SET c = 1;
        WHILE c <= in_cols DO
            -- Generate each seat with both the physical seat_number and an
            -- initial real_seat_number equal to the physical number.  The
            -- real_seat_number will be recalculated when categories are
            -- assigned or seats removed, ensuring contiguous labelling.
            INSERT INTO seats (theater_id, row_char, seat_number, real_seat_number, category_id)
            VALUES (new_tid, CHAR(64 + r), c, c, NULL);
            SET c = c + 1;
        END WHILE;
        SET r = r + 1;
    END WHILE;

    -- Return the new theatre ID for confirmation.
    SELECT new_tid AS theater_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_create_ticket` (IN `p_booking_id` INT, IN `p_seat_id` INT, IN `p_ticket_code` VARCHAR(20))   BEGIN
    -- Declare local variables at the top of the procedure.  MySQL
    -- requires that DECLARE statements appear before any other SQL
    -- statements.  v_performance_id will hold the performance
    -- associated with the booking.
    DECLARE v_performance_id INT;

    -- Insert a new ticket.  Tickets no longer determine seat availability;
    -- seat_performance tracks the status of each seat for a performance.
    INSERT INTO tickets (booking_id, seat_id, ticket_code, status, created_at)
    VALUES (p_booking_id, p_seat_id, p_ticket_code, 'Đang chờ', NOW());

    -- Determine the performance_id for this booking and update
    -- seat_performance so that the seat is marked as booked.  A
    -- performance_id of NULL indicates that the booking reference is
    -- invalid, in which case no update is performed.
    SELECT performance_id INTO v_performance_id
    FROM bookings
    WHERE booking_id = p_booking_id;
    IF v_performance_id IS NOT NULL THEN
        UPDATE seat_performance
        SET status = 'đã đặt'
        WHERE seat_id = p_seat_id
          AND performance_id = v_performance_id;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_create_user` (IN `in_email` VARCHAR(255), IN `in_password` VARCHAR(255), IN `in_account_name` VARCHAR(100), IN `in_user_type` VARCHAR(20), IN `in_verified` TINYINT(1))   BEGIN
    INSERT INTO users (email, password, account_name, user_type, status, is_verified)
    VALUES (in_email, in_password, in_account_name, in_user_type, 'hoạt động', in_verified);
    SELECT LAST_INSERT_ID() AS user_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_delete_genre` (IN `in_id` INT)   BEGIN
    DELETE FROM genres WHERE genre_id = in_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_delete_performance_if_ended` (IN `in_performance_id` INT)   BEGIN
    DELETE FROM performances
    WHERE performance_id = in_performance_id AND status = 'Đã kết thúc';
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_delete_review` (IN `in_review_id` INT)   BEGIN
    DELETE FROM reviews WHERE review_id = in_review_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_delete_seat_category` (IN `in_category_id` INT)   BEGIN
    DELETE FROM seat_categories WHERE category_id = in_category_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_delete_show` (IN `in_show_id` INT)   BEGIN
    DELETE FROM shows WHERE show_id = in_show_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_delete_show_genres` (IN `in_show_id` INT)   BEGIN
    DELETE FROM show_genres WHERE show_id = in_show_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_delete_staff` (IN `in_user_id` INT)   BEGIN
    DELETE FROM users
    WHERE user_id = in_user_id
      AND user_type = 'Nhân viên';
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_delete_theater` (IN `in_theater_id` INT)   BEGIN
    DELETE FROM theaters WHERE theater_id = in_theater_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_expire_pending_payments` ()   BEGIN
    -- First update payment status and associated booking status
    UPDATE payments p
    JOIN bookings b ON p.booking_id = b.booking_id
    SET p.status = 'Thất bại',
        p.updated_at = NOW(),
        b.booking_status = 'Đã hủy'
    WHERE p.status = 'Đang chờ'
      AND TIMESTAMPDIFF(MINUTE, p.created_at, NOW()) >= 15;

    -- Cancel any tickets linked to expired payments
    UPDATE tickets t
    JOIN payments p2 ON p2.booking_id = t.booking_id
    SET t.status = 'Đã hủy'
    WHERE p2.status = 'Thất bại'
      AND TIMESTAMPDIFF(MINUTE, p2.created_at, NOW()) >= 15
      AND t.status IN ('Đang chờ', 'Hợp lệ');

    -- Free up seat_performance seats for expired bookings
    UPDATE seat_performance sp
    JOIN tickets t2 ON sp.seat_id = t2.seat_id
    JOIN payments p3 ON p3.booking_id = t2.booking_id
    JOIN bookings b2 ON b2.booking_id = p3.booking_id
    SET sp.status = 'trống'
    WHERE p3.status = 'Thất bại'
      AND TIMESTAMPDIFF(MINUTE, p3.created_at, NOW()) >= 15
      AND sp.performance_id = b2.performance_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_get_all_bookings` ()   BEGIN
    SELECT b.*, u.email
    FROM bookings b
    JOIN users u ON b.user_id = u.user_id
    ORDER BY b.created_at DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_get_all_genres` ()   BEGIN
    -- Order genres by their primary key to display increasing IDs in admin views
    SELECT * FROM genres ORDER BY genre_id ASC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_get_all_performances_detailed` ()   BEGIN
    SELECT p.*, s.title, t.name AS theater_name
    FROM performances p
    JOIN shows s ON p.show_id = s.show_id
    JOIN theaters t ON p.theater_id = t.theater_id
    ORDER BY p.performance_date, p.start_time;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_get_all_reviews` ()   BEGIN
    -- Return all reviews joined with the reviewer account name and show title.  Include
    -- the show_id explicitly so that application code can filter by show.  Without
    -- selecting the show_id the caller would not be able to determine which
    -- vở diễn each review belongs to.
    SELECT r.*, r.show_id AS show_id, u.account_name AS account_name, s.title
    FROM reviews r
    JOIN users u ON r.user_id = u.user_id
    JOIN shows s ON r.show_id = s.show_id
    ORDER BY r.created_at DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_get_all_seat_categories` ()   BEGIN
    SELECT * FROM seat_categories ORDER BY category_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_get_all_shows` ()   BEGIN
    SELECT s.*, GROUP_CONCAT(g.genre_name SEPARATOR ', ') AS genres
    FROM shows s
    LEFT JOIN show_genres sg ON s.show_id = sg.show_id
    LEFT JOIN genres g ON sg.genre_id = g.genre_id
    GROUP BY s.show_id
    ORDER BY s.created_at DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_get_all_theaters` ()   BEGIN
    -- Order theatres by their primary key to ensure increasing ID ordering
    SELECT * FROM theaters ORDER BY theater_id ASC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_get_average_rating_by_show` (IN `in_show_id` INT)   BEGIN
    SELECT AVG(rating) AS avg_rating
    FROM reviews
    WHERE show_id = in_show_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_get_booked_seat_ids` (IN `in_performance_id` INT)   BEGIN
    -- Return the IDs of seats that are booked for a given performance.
    -- Under the new seating model, seat availability is tracked in the
    -- seat_performance table.  A seat is considered booked when its
    -- status in seat_performance is 'đã đặt'.  We no longer rely on
    -- tickets and bookings to determine availability.
    SELECT sp.seat_id
    FROM seat_performance sp
    WHERE sp.performance_id = in_performance_id
      AND sp.status = 'đã đặt';
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_get_bookings_by_user` (IN `in_user_id` INT)   BEGIN
    SELECT * FROM bookings
    WHERE user_id = in_user_id
    ORDER BY created_at DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_get_booking_with_tickets` (IN `in_booking_id` INT)   BEGIN
    -- Return booking details with tickets.  Use real_seat_number for
    -- seat numbering so that seat labels remain contiguous after
    -- category assignments.  Alias real_seat_number as seat_number to
    -- maintain compatibility with existing application code.
    SELECT b.*, t.ticket_id, t.ticket_code, s.row_char, s.real_seat_number AS seat_number,
           sc.category_name, sc.color_class,
           (p.price + sc.base_price) AS ticket_price
    FROM bookings b
    LEFT JOIN tickets t ON b.booking_id = t.booking_id
    LEFT JOIN seats s ON t.seat_id = s.seat_id
    LEFT JOIN seat_categories sc ON s.category_id = sc.category_id
    LEFT JOIN performances p ON b.performance_id = p.performance_id
    WHERE b.booking_id = in_booking_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_get_genre_ids_by_show` (IN `in_show_id` INT)   BEGIN
    SELECT genre_id
    FROM show_genres
    WHERE show_id = in_show_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_get_latest_reviews` (IN `in_limit` INT)   BEGIN
    SELECT r.*, u.account_name AS account_name, s.title AS show_title
    FROM reviews r
    JOIN users u ON r.user_id = u.user_id
    JOIN shows s ON r.show_id = s.show_id
    ORDER BY r.created_at DESC
    LIMIT in_limit;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_get_payments_by_booking` (IN `in_booking_id` INT)   BEGIN
    SELECT * FROM payments WHERE booking_id = in_booking_id ORDER BY created_at ASC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_get_payment_by_txn` (IN `in_txn_ref` VARCHAR(255))   BEGIN
    SELECT * FROM payments WHERE vnp_txn_ref = in_txn_ref LIMIT 1;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_get_performances_by_show` (IN `in_show_id` INT)   BEGIN
    SELECT p.*, t.name AS theater_name
    FROM performances p
    JOIN theaters t ON p.theater_id = t.theater_id
    -- Only return performances that are currently open for sale (Đang mở bán)
    WHERE p.show_id = in_show_id AND p.status = 'Đang mở bán'
    ORDER BY p.performance_date, p.start_time;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_get_performance_by_id` (IN `in_performance_id` INT)   BEGIN
    SELECT p.*, t.name AS theater_name
    FROM performances p
    JOIN theaters t ON p.theater_id = t.theater_id
    WHERE p.performance_id = in_performance_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_get_performance_detailed_by_id` (IN `in_performance_id` INT)   BEGIN
    SELECT p.*, s.title, t.name AS theater_name
    FROM performances p
    JOIN shows s ON p.show_id = s.show_id
    JOIN theaters t ON p.theater_id = t.theater_id
    WHERE p.performance_id = in_performance_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_get_reviews_by_show` (IN `in_show_id` INT)   BEGIN
    -- Join reviews with users using the account_name field.  Alias the account
    -- name as account_name so application code can access it uniformly.
    SELECT r.*, u.account_name AS account_name
    FROM reviews r
    JOIN users u ON r.user_id = u.user_id
    WHERE r.show_id = in_show_id
    ORDER BY r.created_at DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_get_seats_for_theater` (IN `in_theater_id` INT)   BEGIN
    -- Use a left join so that seats without a category assignment are
    -- included in the results.  Ordering by row and seat_number
    -- provides a deterministic grid.  Explicitly select columns to
    -- expose the new real_seat_number field and hide the legacy status
    -- column which no longer exists.
    SELECT
        s.seat_id,
        s.theater_id,
        s.category_id,
        s.row_char,
        s.seat_number,
        s.real_seat_number,
        s.created_at,
        c.category_name,
        c.base_price,
        c.color_class
    FROM seats s
    LEFT JOIN seat_categories c ON s.category_id = c.category_id
    WHERE s.theater_id = in_theater_id
    ORDER BY s.row_char, s.seat_number;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_get_seat_category_by_id` (IN `in_category_id` INT)   BEGIN
    SELECT * FROM seat_categories WHERE category_id = in_category_id LIMIT 1;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_get_seat_category_by_price` (IN `in_base_price` DECIMAL(10,3))   BEGIN
    SELECT * FROM seat_categories WHERE base_price = in_base_price LIMIT 1;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_get_show_by_id` (IN `in_show_id` INT)   BEGIN
    SELECT s.*, GROUP_CONCAT(g.genre_name SEPARATOR ', ') AS genres
    FROM shows s
    LEFT JOIN show_genres sg ON s.show_id = sg.show_id
    LEFT JOIN genres g ON sg.genre_id = g.genre_id
    WHERE s.show_id = in_show_id
    GROUP BY s.show_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_get_staff_users` ()   BEGIN
    SELECT * FROM users WHERE user_type = 'Nhân viên' ORDER BY user_id ASC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_get_user_bookings_detailed` (IN `in_user_id` INT)   BEGIN
    -- Use the real seat number when generating seat labels for the
    -- bookings summary.  Seats are joined via tickets and then
    -- concatenated as row_char + real_seat_number.  Ordering by
    -- row_char and physical seat_number preserves a consistent
    -- representation even when some seats are hidden from customers.
    SELECT b.*, GROUP_CONCAT(CONCAT(s.row_char, s.real_seat_number) ORDER BY s.row_char, s.seat_number SEPARATOR ', ') AS seats
    FROM bookings b
    LEFT JOIN tickets t ON b.booking_id = t.booking_id
    LEFT JOIN seats s ON t.seat_id = s.seat_id
    WHERE b.user_id = in_user_id
    GROUP BY b.booking_id
    ORDER BY b.created_at DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_get_user_by_account_name` (IN `in_account_name` VARCHAR(100))   BEGIN
    SELECT * FROM users WHERE account_name = in_account_name LIMIT 1;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_get_user_by_email` (IN `in_email` VARCHAR(255))   BEGIN
    SELECT * FROM users WHERE email = in_email LIMIT 1;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_get_user_by_id` (IN `in_user_id` INT)   BEGIN
    SELECT * FROM users WHERE user_id = in_user_id LIMIT 1;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_get_user_detail_by_id` (IN `in_user_id` INT)   BEGIN
    SELECT * FROM user_detail WHERE user_id = in_user_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_modify_theater_size` (IN `in_theater_id` INT, IN `in_add_rows` INT, IN `in_add_cols` INT)   BEGIN
    DECLARE maxRowChar CHAR(1);
    DECLARE oldRows INT;
    DECLARE oldCols INT;
    DECLARE r INT;
    DECLARE c INT;
    DECLARE addc INT;
    -- Determine existing number of rows (by highest row_char) and columns
    SELECT MAX(row_char) INTO maxRowChar FROM seats WHERE theater_id = in_theater_id;
    IF maxRowChar IS NULL THEN
        SET oldRows = 0;
    ELSE
        SET oldRows = ASCII(maxRowChar) - 64;
    END IF;
    SELECT MAX(seat_number) INTO oldCols FROM seats WHERE theater_id = in_theater_id;
    IF oldCols IS NULL THEN
        SET oldCols = 0;
    END IF;
    -- Add rows at the end when in_add_rows is positive
    IF in_add_rows > 0 THEN
        SET r = oldRows + 1;
        WHILE r <= oldRows + in_add_rows DO
            SET c = 1;
            WHILE c <= oldCols DO
                INSERT INTO seats (theater_id, row_char, seat_number, real_seat_number, category_id)
                VALUES (in_theater_id, CHAR(64 + r), c, c, NULL);
                SET c = c + 1;
            END WHILE;
            SET r = r + 1;
        END WHILE;
    END IF;
    -- Remove rows from the end when in_add_rows is negative
    IF in_add_rows < 0 THEN
        DELETE FROM seats
        WHERE theater_id = in_theater_id
          AND (ASCII(row_char) - 64) > oldRows + in_add_rows;
    END IF;
    -- Add columns at the end when in_add_cols is positive
    IF in_add_cols > 0 THEN
        SET addc = 1;
        WHILE addc <= in_add_cols DO
            INSERT INTO seats (theater_id, row_char, seat_number, real_seat_number, category_id)
            SELECT in_theater_id, row_char, oldCols + addc, oldCols + addc, NULL
            FROM (SELECT DISTINCT row_char FROM seats WHERE theater_id = in_theater_id) AS row_list;
            SET addc = addc + 1;
        END WHILE;
    END IF;
    -- Remove columns from the end when in_add_cols is negative
    IF in_add_cols < 0 THEN
        DELETE FROM seats
        WHERE theater_id = in_theater_id
          AND seat_number > oldCols + in_add_cols;
    END IF;
    -- Update the total seats count for the theatre
    CALL proc_update_theater_seat_counts();
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_set_user_otp` (IN `in_user_id` INT, IN `in_otp_code` VARCHAR(10), IN `in_expires` DATETIME)   BEGIN
    UPDATE users
    SET otp_code = in_otp_code,
        otp_expires_at = in_expires
    WHERE user_id = in_user_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_update_booking_status` (IN `in_booking_id` INT, IN `in_booking_status` VARCHAR(20))   BEGIN
    -- Update only the booking_status.  Payment status is now stored in the payments table.
    UPDATE bookings
    SET booking_status = in_booking_status
    WHERE booking_id = in_booking_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_update_genre` (IN `in_id` INT, IN `in_name` VARCHAR(100))   BEGIN
    UPDATE genres
    SET genre_name = in_name
    WHERE genre_id = in_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_update_payment_status` (IN `in_txn_ref` VARCHAR(255), IN `in_status` VARCHAR(20), IN `in_bank_code` VARCHAR(255), IN `in_pay_date` VARCHAR(255))   BEGIN
    -- Cập nhật trạng thái cho giao dịch thanh toán cụ thể.  Trạng thái
    -- mới, mã ngân hàng và ngày thanh toán được lưu lại cùng thời điểm
    -- cập nhật.  Dòng này chỉ thay đổi bản ghi của giao dịch có mã
    -- tham chiếu (vnp_txn_ref) tương ứng.
    UPDATE payments
    SET status = in_status,
        vnp_bank_code = in_bank_code,
        vnp_pay_date = in_pay_date,
        updated_at = NOW()
    WHERE vnp_txn_ref = in_txn_ref;

    -- Nếu trạng thái được cập nhật thành 'Thất bại' thì hủy booking và
    -- giải phóng toàn bộ vé và ghế liên quan ngay lập tức.  Điều này
    -- xử lý trường hợp người dùng hủy thanh toán thủ công hoặc cổng
    -- thanh toán trả về kết quả thất bại, thay vì chờ thủ tục hết hạn.
    IF in_status = 'Thất bại' THEN
        -- Đánh dấu booking liên quan là đã hủy
        UPDATE bookings b
        JOIN payments p ON p.booking_id = b.booking_id
        SET b.booking_status = 'Đã hủy'
        WHERE p.vnp_txn_ref = in_txn_ref;

        -- Đánh dấu tất cả các vé đang chờ hoặc hợp lệ của booking này là đã hủy
        UPDATE tickets t
        JOIN payments p2 ON p2.booking_id = t.booking_id
        SET t.status = 'Đã hủy'
        WHERE p2.vnp_txn_ref = in_txn_ref
          AND t.status IN ('Đang chờ','Hợp lệ');

        -- Trả ghế về trạng thái trống cho suất diễn tương ứng
        UPDATE seat_performance sp
        JOIN tickets t2 ON sp.seat_id = t2.seat_id
        JOIN payments p3 ON p3.booking_id = t2.booking_id
        JOIN bookings b2 ON b2.booking_id = p3.booking_id
        SET sp.status = 'trống'
        WHERE p3.vnp_txn_ref = in_txn_ref
          AND sp.performance_id = b2.performance_id;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_update_performance_statuses` ()   BEGIN
    UPDATE performances
    SET status = 'Đã kết thúc'
    WHERE status NOT IN ('Đã kết thúc','Đã hủy')
      AND (
        performance_date < CURDATE()
        OR (performance_date = CURDATE() AND end_time IS NOT NULL AND end_time < CURTIME())
      );
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_update_performance_status_single` (IN `in_performance_id` INT, IN `in_status` VARCHAR(20))   BEGIN
    UPDATE performances
    SET status = in_status
    WHERE performance_id = in_performance_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_update_seat_category` (IN `in_category_id` INT, IN `in_name` VARCHAR(100), IN `in_base_price` DECIMAL(10,3), IN `in_color_class` VARCHAR(50))   BEGIN
    UPDATE seat_categories
    SET category_name = in_name,
        base_price    = in_base_price,
        color_class   = in_color_class
    WHERE category_id = in_category_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_update_seat_category_range` (IN `in_theater_id` INT, IN `in_row_char` CHAR(1), IN `in_start_seat` INT, IN `in_end_seat` INT, IN `in_category_id` INT)   BEGIN
    -- Assign or clear the category for the specified seat range.
    UPDATE seats
    SET category_id = IF(in_category_id = 0, NULL, in_category_id)
    WHERE theater_id = in_theater_id
      AND row_char = in_row_char
      AND seat_number BETWEEN in_start_seat AND in_end_seat;

    -- Recalculate real_seat_number values for the entire row after
    -- updating categories.  Seats with a NULL category_id should have
    -- real_seat_number set to 0 so that they are hidden from
    -- customer‑facing seat maps.  Seats with a category are
    -- re‑numbered contiguously in ascending order by their physical
    -- seat_number.
    SET @rn := 0;
    -- First, assign sequential numbers to seats with non‑NULL category.
    UPDATE seats s
    JOIN (
        SELECT seat_id, (@rn := @rn + 1) AS new_num
        FROM seats
        WHERE theater_id = in_theater_id
          AND row_char = in_row_char
          AND category_id IS NOT NULL
        ORDER BY seat_number
    ) AS ordered ON s.seat_id = ordered.seat_id
    SET s.real_seat_number = ordered.new_num;

    -- Next, mark seats without a category as 0.
    UPDATE seats
    SET real_seat_number = 0
    WHERE theater_id = in_theater_id
      AND row_char = in_row_char
      AND category_id IS NULL;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_update_show_details` (IN `in_show_id` INT, IN `in_title` VARCHAR(255), IN `in_description` TEXT, IN `in_duration` INT, IN `in_director` VARCHAR(255), IN `in_poster` VARCHAR(255))   BEGIN
    UPDATE shows
    SET title            = in_title,
        description      = in_description,
        duration_minutes = in_duration,
        director         = in_director,
        poster_image_url = in_poster
    WHERE show_id = in_show_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_update_show_statuses` ()   BEGIN
    -- Synchronise show status with the statuses of its performances.  For each show:
    --  - set to "Sắp chiếu" when it has no performances
    --  - set to "Đã kết thúc" when all performances have status "Đã kết thúc"
    --  - otherwise set to "Đang chiếu"
    UPDATE shows s
    SET s.status = (
        CASE
            WHEN (SELECT COUNT(*) FROM performances p WHERE p.show_id = s.show_id) = 0 THEN 'Sắp chiếu'
            WHEN (SELECT COUNT(*) FROM performances p WHERE p.show_id = s.show_id AND p.status <> 'Đã kết thúc') = 0 THEN 'Đã kết thúc'
            ELSE 'Đang chiếu'
        END
    );
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_update_staff_user` (IN `in_user_id` INT, IN `in_account_name` VARCHAR(100), IN `in_email` VARCHAR(255), IN `in_status` VARCHAR(50))   BEGIN
    UPDATE users
    SET account_name = in_account_name,
        email        = in_email,
        status       = in_status
    WHERE user_id = in_user_id AND user_type = 'Nhân viên';
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_update_theater` (IN `in_theater_id` INT, IN `in_name` VARCHAR(255))   BEGIN
    UPDATE theaters
    SET name = in_name
    WHERE theater_id = in_theater_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_update_theater_seat_counts` ()   BEGIN
    -- Update each theater's total_seats as the count of all seats belonging to that theater
    UPDATE theaters t
    LEFT JOIN (
        SELECT theater_id, COUNT(seat_id) AS total_seats
        FROM seats
        GROUP BY theater_id
    ) AS seat_count
    ON t.theater_id = seat_count.theater_id
    SET t.total_seats = COALESCE(seat_count.total_seats, 0);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_update_unverified_user_password_email` (IN `in_user_id` INT, IN `in_password` VARCHAR(255), IN `in_email` VARCHAR(255))   BEGIN
    UPDATE users
    SET password = in_password,
        email = in_email
    WHERE user_id = in_user_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_update_unverified_user_password_name` (IN `in_user_id` INT, IN `in_password` VARCHAR(255), IN `in_account_name` VARCHAR(100))   BEGIN
    UPDATE users
    SET password = in_password,
        account_name = in_account_name
    WHERE user_id = in_user_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_update_user_password` (IN `in_user_id` INT, IN `in_password` VARCHAR(255))   BEGIN
    UPDATE users
    SET password = in_password,
        otp_code = NULL,
        otp_expires_at = NULL
    WHERE user_id = in_user_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_upsert_user_detail` (IN `in_user_id` INT, IN `in_full_name` VARCHAR(255), IN `in_date_of_birth` DATE, IN `in_address` VARCHAR(255), IN `in_phone` VARCHAR(20))   BEGIN
    INSERT INTO user_detail (user_id, full_name, date_of_birth, address, phone)
    VALUES (in_user_id, in_full_name, in_date_of_birth, in_address, in_phone)
    ON DUPLICATE KEY UPDATE
        full_name     = VALUES(full_name),
        date_of_birth = VALUES(date_of_birth),
        address       = VALUES(address),
        phone         = VALUES(phone);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_verify_user_otp` (IN `in_user_id` INT, IN `in_otp_code` VARCHAR(10))   BEGIN
    DECLARE v INT DEFAULT 0;
    SELECT CASE
            WHEN otp_code = in_otp_code AND otp_expires_at >= NOW() THEN 1
            ELSE 0
        END AS verified
    INTO v
    FROM users
    WHERE user_id = in_user_id;
    IF v = 1 THEN
        UPDATE users
        SET is_verified = 1,
            otp_code = NULL,
            otp_expires_at = NULL
        WHERE user_id = in_user_id;
    END IF;
    SELECT v AS verified;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `performance_id` int(11) NOT NULL,
  `total_amount` decimal(10,3) NOT NULL,
  `booking_status` enum('Đang xử lý','Đã hoàn thành','Đã hủy') NOT NULL DEFAULT 'Đang xử lý',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `user_id`, `performance_id`, `total_amount`, `booking_status`, `created_at`) VALUES
(29, 4, 16, 660000.000, 'Đã hoàn thành', '2025-08-26 21:06:55'),
(30, 1, 18, 360000.000, 'Đã hoàn thành', '2025-09-06 19:25:25'),
(31, 3, 32, 545000.000, 'Đã hoàn thành', '2025-10-22 17:30:20'),
(32, 3, 16, 660000.000, 'Đã hoàn thành', '2025-09-24 11:08:10'),
(33, 2, 25, 510000.000, 'Đã hoàn thành', '2025-08-01 03:53:33'),
(34, 4, 28, 550000.000, 'Đã hoàn thành', '2025-08-07 18:49:30'),
(35, 4, 15, 435000.000, 'Đã hoàn thành', '2025-08-10 21:45:46'),
(36, 2, 16, 660000.000, 'Đã hoàn thành', '2025-10-16 17:00:02'),
(37, 4, 15, 585000.000, 'Đã hoàn thành', '2025-08-26 18:14:39'),
(38, 3, 30, 550000.000, 'Đã hoàn thành', '2025-09-03 08:19:29'),
(39, 2, 31, 515000.000, 'Đã hoàn thành', '2025-10-19 17:42:09'),
(40, 3, 34, 415000.000, 'Đã hoàn thành', '2025-08-02 05:14:21'),
(41, 4, 27, 340000.000, 'Đã hoàn thành', '2025-10-06 11:47:10'),
(42, 4, 32, 320000.000, 'Đã hoàn thành', '2025-09-20 13:06:24'),
(43, 1, 28, 475000.000, 'Đã hoàn thành', '2025-10-22 06:26:58'),
(44, 1, 35, 470000.000, 'Đã hoàn thành', '2025-10-28 16:56:57'),
(45, 2, 30, 550000.000, 'Đã hoàn thành', '2025-09-02 12:22:29'),
(46, 1, 25, 435000.000, 'Đã hoàn thành', '2025-10-10 04:58:34'),
(47, 3, 29, 515000.000, 'Đã hoàn thành', '2025-08-02 14:00:03'),
(48, 3, 21, 450000.000, 'Đã hoàn thành', '2025-08-02 05:53:38'),
(49, 1, 26, 565000.000, 'Đã hoàn thành', '2025-10-02 00:30:49'),
(50, 2, 24, 340000.000, 'Đã hoàn thành', '2025-09-22 20:00:29'),
(51, 4, 30, 700000.000, 'Đã hoàn thành', '2025-09-06 18:10:44'),
(52, 2, 29, 740000.000, 'Đã hoàn thành', '2025-09-14 07:21:00'),
(53, 3, 21, 600000.000, 'Đã hoàn thành', '2025-10-06 12:05:14'),
(54, 4, 23, 415000.000, 'Đã hoàn thành', '2025-08-01 00:49:40'),
(55, 3, 18, 510000.000, 'Đã hoàn thành', '2025-10-08 15:33:06'),
(56, 1, 17, 625000.000, 'Đã hoàn thành', '2025-10-17 06:11:45'),
(57, 4, 32, 545000.000, 'Đã hoàn thành', '2025-08-03 19:53:37'),
(58, 1, 25, 585000.000, 'Đã hoàn thành', '2025-10-12 21:31:32'),
(59, 3, 32, 470000.000, 'Đã hoàn thành', '2025-08-03 07:26:23'),
(60, 4, 35, 470000.000, 'Đã hoàn thành', '2025-08-07 05:42:18'),
(61, 2, 19, 450000.000, 'Đã hoàn thành', '2025-10-12 05:54:40'),
(62, 4, 17, 550000.000, 'Đã hoàn thành', '2025-09-22 04:33:01'),
(63, 3, 24, 490000.000, 'Đã hoàn thành', '2025-10-15 08:31:15'),
(64, 3, 27, 640000.000, 'Đã hoàn thành', '2025-09-20 04:56:18'),
(65, 4, 18, 510000.000, 'Đã hoàn thành', '2025-09-11 23:03:27'),
(66, 1, 16, 510000.000, 'Đã hoàn thành', '2025-09-03 22:29:11'),
(67, 4, 29, 665000.000, 'Đã hoàn thành', '2025-10-16 19:25:04'),
(68, 3, 15, 510000.000, 'Đã hoàn thành', '2025-10-24 14:29:48'),
(69, 2, 31, 515000.000, 'Đã hoàn thành', '2025-09-03 08:34:41'),
(70, 3, 31, 590000.000, 'Đã hoàn thành', '2025-08-22 20:03:53'),
(71, 3, 33, 545000.000, 'Đã hoàn thành', '2025-10-30 06:37:41'),
(72, 3, 31, 740000.000, 'Đã hoàn thành', '2025-08-26 16:26:34'),
(73, 1, 27, 490000.000, 'Đã hoàn thành', '2025-11-01 19:56:10'),
(74, 1, 21, 450000.000, 'Đã hoàn thành', '2025-08-19 20:25:59'),
(75, 1, 23, 490000.000, 'Đã hoàn thành', '2025-09-28 06:20:19'),
(76, 2, 25, 585000.000, 'Đã hoàn thành', '2025-10-30 05:35:56'),
(77, 4, 32, 470000.000, 'Đã hoàn thành', '2025-09-17 19:06:42'),
(78, 4, 27, 640000.000, 'Đã hoàn thành', '2025-09-03 01:28:23'),
(79, 2, 27, 415000.000, 'Đã hoàn thành', '2025-09-29 04:49:21'),
(80, 3, 35, 545000.000, 'Đã hoàn thành', '2025-10-07 13:17:23'),
(81, 1, 33, 470000.000, 'Đã hoàn thành', '2025-09-05 00:21:55'),
(82, 3, 29, 515000.000, 'Đã hoàn thành', '2025-09-04 19:45:46'),
(83, 2, 21, 375000.000, 'Đã hoàn thành', '2025-10-19 15:51:09'),
(84, 1, 20, 395000.000, 'Đã hoàn thành', '2025-10-21 17:07:45'),
(85, 3, 28, 475000.000, 'Đã hoàn thành', '2025-10-20 12:32:36'),
(86, 4, 20, 320000.000, 'Đã hoàn thành', '2025-09-05 18:42:26'),
(87, 3, 17, 625000.000, 'Đã hoàn thành', '2025-09-18 19:40:40'),
(88, 4, 34, 640000.000, 'Đã hoàn thành', '2025-08-06 00:14:45'),
(89, 1, 33, 470000.000, 'Đã hoàn thành', '2025-09-15 04:48:20'),
(90, 2, 33, 395000.000, 'Đã hoàn thành', '2025-10-02 19:14:12'),
(91, 4, 29, 590000.000, 'Đã hoàn thành', '2025-09-24 15:20:27'),
(92, 4, 33, 545000.000, 'Đã hoàn thành', '2025-09-18 15:24:17'),
(93, 1, 33, 470000.000, 'Đã hoàn thành', '2025-08-18 16:34:13'),
(94, 4, 18, 585000.000, 'Đã hoàn thành', '2025-10-11 05:47:26'),
(95, 1, 19, 375000.000, 'Đã hoàn thành', '2025-09-15 15:53:49'),
(96, 3, 32, 620000.000, 'Đã hoàn thành', '2025-10-26 13:25:17'),
(97, 4, 22, 470000.000, 'Đã hoàn thành', '2025-09-18 22:40:33'),
(98, 1, 34, 490000.000, 'Đã hoàn thành', '2025-09-01 03:06:23'),
(99, 3, 27, 565000.000, 'Đã hoàn thành', '2025-08-28 16:18:48'),
(100, 4, 20, 545000.000, 'Đã hoàn thành', '2025-08-05 05:08:38'),
(101, 2, 34, 490000.000, 'Đã hoàn thành', '2025-08-20 05:56:04'),
(102, 2, 16, 435000.000, 'Đã hoàn thành', '2025-10-14 03:54:54'),
(103, 4, 31, 665000.000, 'Đã hoàn thành', '2025-10-24 01:09:53'),
(104, 1, 29, 740000.000, 'Đã hoàn thành', '2025-08-11 00:32:19'),
(105, 3, 29, 515000.000, 'Đã hoàn thành', '2025-10-18 09:19:08'),
(106, 4, 21, 450000.000, 'Đã hoàn thành', '2025-09-09 11:00:38'),
(107, 4, 29, 740000.000, 'Đã hoàn thành', '2025-10-13 22:52:51'),
(108, 3, 35, 395000.000, 'Đã hoàn thành', '2025-08-15 22:29:28'),
(109, 2, 30, 700000.000, 'Đã hoàn thành', '2025-08-24 10:17:47'),
(110, 1, 16, 510000.000, 'Đã hoàn thành', '2025-09-09 04:34:27'),
(111, 3, 15, 510000.000, 'Đã hoàn thành', '2025-09-23 02:26:01'),
(112, 4, 15, 660000.000, 'Đã hoàn thành', '2025-10-07 20:22:51'),
(113, 1, 24, 565000.000, 'Đã hoàn thành', '2025-08-28 10:23:04'),
(114, 1, 35, 545000.000, 'Đã hoàn thành', '2025-10-12 16:30:44'),
(115, 2, 26, 490000.000, 'Đã hoàn thành', '2025-09-10 05:21:42'),
(116, 4, 22, 395000.000, 'Đã hoàn thành', '2025-09-07 22:20:06'),
(117, 1, 32, 620000.000, 'Đã hoàn thành', '2025-10-27 18:47:04'),
(118, 2, 30, 625000.000, 'Đã hoàn thành', '2025-09-16 04:20:04'),
(119, 4, 18, 660000.000, 'Đã hoàn thành', '2025-10-29 22:50:08'),
(120, 1, 26, 340000.000, 'Đã hoàn thành', '2025-09-13 04:59:52'),
(121, 3, 31, 515000.000, 'Đã hoàn thành', '2025-08-21 17:11:46'),
(122, 1, 27, 640000.000, 'Đã hoàn thành', '2025-09-27 13:22:41'),
(123, 2, 25, 510000.000, 'Đã hoàn thành', '2025-11-02 03:09:28'),
(124, 2, 27, 490000.000, 'Đã hoàn thành', '2025-10-25 02:00:11'),
(125, 2, 30, 700000.000, 'Đã hoàn thành', '2025-10-16 14:06:56'),
(126, 1, 17, 625000.000, 'Đã hoàn thành', '2025-08-15 05:09:29'),
(127, 2, 22, 470000.000, 'Đã hoàn thành', '2025-10-04 04:57:13'),
(128, 2, 27, 490000.000, 'Đã hoàn thành', '2025-08-12 02:32:23');

-- --------------------------------------------------------

--
-- Table structure for table `genres`
--

CREATE TABLE `genres` (
  `genre_id` int(11) NOT NULL,
  `genre_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `genres`
--

INSERT INTO `genres` (`genre_id`, `genre_name`, `created_at`) VALUES
(6, 'Bi kịch', '2025-10-03 16:00:14'),
(7, 'Hài kịch', '2025-10-03 16:00:24'),
(8, 'Tâm lý - Xã hội', '2025-10-03 16:00:33'),
(9, 'Hiện thực', '2025-10-03 16:00:41'),
(10, 'Dân gian', '2025-10-03 16:00:49'),
(11, 'Lãng mạn', '2025-10-03 16:01:04'),
(12, 'Giả tưởng - huyền ảo', '2025-10-03 16:01:15'),
(13, 'Huyền bí', '2025-10-03 16:01:22'),
(14, 'Chuyển thể cổ tích', '2025-10-03 16:01:35'),
(15, 'Kinh điển', '2025-10-03 16:01:42'),
(16, 'Gia đình - tình cảm', '2025-11-04 12:32:59'),
(17, 'Lịch sử', '2025-11-04 12:34:03'),
(18, 'Chính luận - Xã hội', '2025-11-04 12:34:20'),
(19, 'Châm biếm - Trào phúng', '2025-11-04 12:34:51');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `amount` decimal(10,3) NOT NULL,
  `status` enum('Đang chờ','Thành công','Thất bại') NOT NULL DEFAULT 'Đang chờ',
  `payment_method` varchar(50) DEFAULT NULL,
  `vnp_txn_ref` varchar(64) NOT NULL,
  `vnp_bank_code` varchar(20) DEFAULT NULL,
  `vnp_pay_date` varchar(14) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `booking_id`, `amount`, `status`, `payment_method`, `vnp_txn_ref`, `vnp_bank_code`, `vnp_pay_date`, `created_at`, `updated_at`) VALUES
(24, 29, 660000.000, 'Thành công', 'VNPAY', 'TXN00024', 'NCB', '20250826210655', '2025-08-26 21:06:55', '2025-08-26 21:06:55'),
(25, 30, 360000.000, 'Thành công', 'VNPAY', 'TXN00025', 'NCB', '20250906192525', '2025-09-06 19:25:25', '2025-09-06 19:25:25'),
(26, 31, 545000.000, 'Thành công', 'VNPAY', 'TXN00026', 'NCB', '20251022173020', '2025-10-22 17:30:20', '2025-10-22 17:30:20'),
(27, 32, 660000.000, 'Thành công', 'VNPAY', 'TXN00027', 'NCB', '20250924110810', '2025-09-24 11:08:10', '2025-09-24 11:08:10'),
(28, 33, 510000.000, 'Thành công', 'VNPAY', 'TXN00028', 'NCB', '20250801035333', '2025-08-01 03:53:33', '2025-08-01 03:53:33'),
(29, 34, 550000.000, 'Thành công', 'VNPAY', 'TXN00029', 'NCB', '20250807184930', '2025-08-07 18:49:30', '2025-08-07 18:49:30'),
(30, 35, 435000.000, 'Thành công', 'VNPAY', 'TXN00030', 'NCB', '20250810214546', '2025-08-10 21:45:46', '2025-08-10 21:45:46'),
(31, 36, 660000.000, 'Thành công', 'VNPAY', 'TXN00031', 'NCB', '20251016170002', '2025-10-16 17:00:02', '2025-10-16 17:00:02'),
(32, 37, 585000.000, 'Thành công', 'VNPAY', 'TXN00032', 'NCB', '20250826181439', '2025-08-26 18:14:39', '2025-08-26 18:14:39'),
(33, 38, 550000.000, 'Thành công', 'VNPAY', 'TXN00033', 'NCB', '20250903081929', '2025-09-03 08:19:29', '2025-09-03 08:19:29'),
(34, 39, 515000.000, 'Thành công', 'VNPAY', 'TXN00034', 'NCB', '20251019174209', '2025-10-19 17:42:09', '2025-10-19 17:42:09'),
(35, 40, 415000.000, 'Thành công', 'VNPAY', 'TXN00035', 'NCB', '20250802051421', '2025-08-02 05:14:21', '2025-08-02 05:14:21'),
(36, 41, 340000.000, 'Thành công', 'VNPAY', 'TXN00036', 'NCB', '20251006114710', '2025-10-06 11:47:10', '2025-10-06 11:47:10'),
(37, 42, 320000.000, 'Thành công', 'VNPAY', 'TXN00037', 'NCB', '20250920130624', '2025-09-20 13:06:24', '2025-09-20 13:06:24'),
(38, 43, 475000.000, 'Thành công', 'VNPAY', 'TXN00038', 'NCB', '20251022062658', '2025-10-22 06:26:58', '2025-10-22 06:26:58'),
(39, 44, 470000.000, 'Thành công', 'VNPAY', 'TXN00039', 'NCB', '20251028165657', '2025-10-28 16:56:57', '2025-10-28 16:56:57'),
(40, 45, 550000.000, 'Thành công', 'VNPAY', 'TXN00040', 'NCB', '20250902122229', '2025-09-02 12:22:29', '2025-09-02 12:22:29'),
(41, 46, 435000.000, 'Thành công', 'VNPAY', 'TXN00041', 'NCB', '20251010045834', '2025-10-10 04:58:34', '2025-10-10 04:58:34'),
(42, 47, 515000.000, 'Thành công', 'VNPAY', 'TXN00042', 'NCB', '20250802140003', '2025-08-02 14:00:03', '2025-08-02 14:00:03'),
(43, 48, 450000.000, 'Thành công', 'VNPAY', 'TXN00043', 'NCB', '20250802055338', '2025-08-02 05:53:38', '2025-08-02 05:53:38'),
(44, 49, 565000.000, 'Thành công', 'VNPAY', 'TXN00044', 'NCB', '20251002003049', '2025-10-02 00:30:49', '2025-10-02 00:30:49'),
(45, 50, 340000.000, 'Thành công', 'VNPAY', 'TXN00045', 'NCB', '20250922200029', '2025-09-22 20:00:29', '2025-09-22 20:00:29'),
(46, 51, 700000.000, 'Thành công', 'VNPAY', 'TXN00046', 'NCB', '20250906181044', '2025-09-06 18:10:44', '2025-09-06 18:10:44'),
(47, 52, 740000.000, 'Thành công', 'VNPAY', 'TXN00047', 'NCB', '20250914072100', '2025-09-14 07:21:00', '2025-09-14 07:21:00'),
(48, 53, 600000.000, 'Thành công', 'VNPAY', 'TXN00048', 'NCB', '20251006120514', '2025-10-06 12:05:14', '2025-10-06 12:05:14'),
(49, 54, 415000.000, 'Thành công', 'VNPAY', 'TXN00049', 'NCB', '20250801004940', '2025-08-01 00:49:40', '2025-08-01 00:49:40'),
(50, 55, 510000.000, 'Thành công', 'VNPAY', 'TXN00050', 'NCB', '20251008153306', '2025-10-08 15:33:06', '2025-10-08 15:33:06'),
(51, 56, 625000.000, 'Thành công', 'VNPAY', 'TXN00051', 'NCB', '20251017061145', '2025-10-17 06:11:45', '2025-10-17 06:11:45'),
(52, 57, 545000.000, 'Thành công', 'VNPAY', 'TXN00052', 'NCB', '20250803195337', '2025-08-03 19:53:37', '2025-08-03 19:53:37'),
(53, 58, 585000.000, 'Thành công', 'VNPAY', 'TXN00053', 'NCB', '20251012213132', '2025-10-12 21:31:32', '2025-10-12 21:31:32'),
(54, 59, 470000.000, 'Thành công', 'VNPAY', 'TXN00054', 'NCB', '20250803072623', '2025-08-03 07:26:23', '2025-08-03 07:26:23'),
(55, 60, 470000.000, 'Thành công', 'VNPAY', 'TXN00055', 'NCB', '20250807054218', '2025-08-07 05:42:18', '2025-08-07 05:42:18'),
(56, 61, 450000.000, 'Thành công', 'VNPAY', 'TXN00056', 'NCB', '20251012055440', '2025-10-12 05:54:40', '2025-10-12 05:54:40'),
(57, 62, 550000.000, 'Thành công', 'VNPAY', 'TXN00057', 'NCB', '20250922043301', '2025-09-22 04:33:01', '2025-09-22 04:33:01'),
(58, 63, 490000.000, 'Thành công', 'VNPAY', 'TXN00058', 'NCB', '20251015083115', '2025-10-15 08:31:15', '2025-10-15 08:31:15'),
(59, 64, 640000.000, 'Thành công', 'VNPAY', 'TXN00059', 'NCB', '20250920045618', '2025-09-20 04:56:18', '2025-09-20 04:56:18'),
(60, 65, 510000.000, 'Thành công', 'VNPAY', 'TXN00060', 'NCB', '20250911230327', '2025-09-11 23:03:27', '2025-09-11 23:03:27'),
(61, 66, 510000.000, 'Thành công', 'VNPAY', 'TXN00061', 'NCB', '20250903222911', '2025-09-03 22:29:11', '2025-09-03 22:29:11'),
(62, 67, 665000.000, 'Thành công', 'VNPAY', 'TXN00062', 'NCB', '20251016192504', '2025-10-16 19:25:04', '2025-10-16 19:25:04'),
(63, 68, 510000.000, 'Thành công', 'VNPAY', 'TXN00063', 'NCB', '20251024142948', '2025-10-24 14:29:48', '2025-10-24 14:29:48'),
(64, 69, 515000.000, 'Thành công', 'VNPAY', 'TXN00064', 'NCB', '20250903083441', '2025-09-03 08:34:41', '2025-09-03 08:34:41'),
(65, 70, 590000.000, 'Thành công', 'VNPAY', 'TXN00065', 'NCB', '20250822200353', '2025-08-22 20:03:53', '2025-08-22 20:03:53'),
(66, 71, 545000.000, 'Thành công', 'VNPAY', 'TXN00066', 'NCB', '20251030063741', '2025-10-30 06:37:41', '2025-10-30 06:37:41'),
(67, 72, 740000.000, 'Thành công', 'VNPAY', 'TXN00067', 'NCB', '20250826162634', '2025-08-26 16:26:34', '2025-08-26 16:26:34'),
(68, 73, 490000.000, 'Thành công', 'VNPAY', 'TXN00068', 'NCB', '20251101195610', '2025-11-01 19:56:10', '2025-11-01 19:56:10'),
(69, 74, 450000.000, 'Thành công', 'VNPAY', 'TXN00069', 'NCB', '20250819202559', '2025-08-19 20:25:59', '2025-08-19 20:25:59'),
(70, 75, 490000.000, 'Thành công', 'VNPAY', 'TXN00070', 'NCB', '20250928062019', '2025-09-28 06:20:19', '2025-09-28 06:20:19'),
(71, 76, 585000.000, 'Thành công', 'VNPAY', 'TXN00071', 'NCB', '20251030053556', '2025-10-30 05:35:56', '2025-10-30 05:35:56'),
(72, 77, 470000.000, 'Thành công', 'VNPAY', 'TXN00072', 'NCB', '20250917190642', '2025-09-17 19:06:42', '2025-09-17 19:06:42'),
(73, 78, 640000.000, 'Thành công', 'VNPAY', 'TXN00073', 'NCB', '20250903012823', '2025-09-03 01:28:23', '2025-09-03 01:28:23'),
(74, 79, 415000.000, 'Thành công', 'VNPAY', 'TXN00074', 'NCB', '20250929044921', '2025-09-29 04:49:21', '2025-09-29 04:49:21'),
(75, 80, 545000.000, 'Thành công', 'VNPAY', 'TXN00075', 'NCB', '20251007131723', '2025-10-07 13:17:23', '2025-10-07 13:17:23'),
(76, 81, 470000.000, 'Thành công', 'VNPAY', 'TXN00076', 'NCB', '20250905002155', '2025-09-05 00:21:55', '2025-09-05 00:21:55'),
(77, 82, 515000.000, 'Thành công', 'VNPAY', 'TXN00077', 'NCB', '20250904194546', '2025-09-04 19:45:46', '2025-09-04 19:45:46'),
(78, 83, 375000.000, 'Thành công', 'VNPAY', 'TXN00078', 'NCB', '20251019155109', '2025-10-19 15:51:09', '2025-10-19 15:51:09'),
(79, 84, 395000.000, 'Thành công', 'VNPAY', 'TXN00079', 'NCB', '20251021170745', '2025-10-21 17:07:45', '2025-10-21 17:07:45'),
(80, 85, 475000.000, 'Thành công', 'VNPAY', 'TXN00080', 'NCB', '20251020123236', '2025-10-20 12:32:36', '2025-10-20 12:32:36'),
(81, 86, 320000.000, 'Thành công', 'VNPAY', 'TXN00081', 'NCB', '20250905184226', '2025-09-05 18:42:26', '2025-09-05 18:42:26'),
(82, 87, 625000.000, 'Thành công', 'VNPAY', 'TXN00082', 'NCB', '20250918194040', '2025-09-18 19:40:40', '2025-09-18 19:40:40'),
(83, 88, 640000.000, 'Thành công', 'VNPAY', 'TXN00083', 'NCB', '20250806001445', '2025-08-06 00:14:45', '2025-08-06 00:14:45'),
(84, 89, 470000.000, 'Thành công', 'VNPAY', 'TXN00084', 'NCB', '20250915044820', '2025-09-15 04:48:20', '2025-09-15 04:48:20'),
(85, 90, 395000.000, 'Thành công', 'VNPAY', 'TXN00085', 'NCB', '20251002191412', '2025-10-02 19:14:12', '2025-10-02 19:14:12'),
(86, 91, 590000.000, 'Thành công', 'VNPAY', 'TXN00086', 'NCB', '20250924152027', '2025-09-24 15:20:27', '2025-09-24 15:20:27'),
(87, 92, 545000.000, 'Thành công', 'VNPAY', 'TXN00087', 'NCB', '20250918152417', '2025-09-18 15:24:17', '2025-09-18 15:24:17'),
(88, 93, 470000.000, 'Thành công', 'VNPAY', 'TXN00088', 'NCB', '20250818163413', '2025-08-18 16:34:13', '2025-08-18 16:34:13'),
(89, 94, 585000.000, 'Thành công', 'VNPAY', 'TXN00089', 'NCB', '20251011054726', '2025-10-11 05:47:26', '2025-10-11 05:47:26'),
(90, 95, 375000.000, 'Thành công', 'VNPAY', 'TXN00090', 'NCB', '20250915155349', '2025-09-15 15:53:49', '2025-09-15 15:53:49'),
(91, 96, 620000.000, 'Thành công', 'VNPAY', 'TXN00091', 'NCB', '20251026132517', '2025-10-26 13:25:17', '2025-10-26 13:25:17'),
(92, 97, 470000.000, 'Thành công', 'VNPAY', 'TXN00092', 'NCB', '20250918224033', '2025-09-18 22:40:33', '2025-09-18 22:40:33'),
(93, 98, 490000.000, 'Thành công', 'VNPAY', 'TXN00093', 'NCB', '20250901030623', '2025-09-01 03:06:23', '2025-09-01 03:06:23'),
(94, 99, 565000.000, 'Thành công', 'VNPAY', 'TXN00094', 'NCB', '20250828161848', '2025-08-28 16:18:48', '2025-08-28 16:18:48'),
(95, 100, 545000.000, 'Thành công', 'VNPAY', 'TXN00095', 'NCB', '20250805050838', '2025-08-05 05:08:38', '2025-08-05 05:08:38'),
(96, 101, 490000.000, 'Thành công', 'VNPAY', 'TXN00096', 'NCB', '20250820055604', '2025-08-20 05:56:04', '2025-08-20 05:56:04'),
(97, 102, 435000.000, 'Thành công', 'VNPAY', 'TXN00097', 'NCB', '20251014035454', '2025-10-14 03:54:54', '2025-10-14 03:54:54'),
(98, 103, 665000.000, 'Thành công', 'VNPAY', 'TXN00098', 'NCB', '20251024010953', '2025-10-24 01:09:53', '2025-10-24 01:09:53'),
(99, 104, 740000.000, 'Thành công', 'VNPAY', 'TXN00099', 'NCB', '20250811003219', '2025-08-11 00:32:19', '2025-08-11 00:32:19'),
(100, 105, 515000.000, 'Thành công', 'VNPAY', 'TXN00100', 'NCB', '20251018091908', '2025-10-18 09:19:08', '2025-10-18 09:19:08'),
(101, 106, 450000.000, 'Thành công', 'VNPAY', 'TXN00101', 'NCB', '20250909110038', '2025-09-09 11:00:38', '2025-09-09 11:00:38'),
(102, 107, 740000.000, 'Thành công', 'VNPAY', 'TXN00102', 'NCB', '20251013225251', '2025-10-13 22:52:51', '2025-10-13 22:52:51'),
(103, 108, 395000.000, 'Thành công', 'VNPAY', 'TXN00103', 'NCB', '20250815222928', '2025-08-15 22:29:28', '2025-08-15 22:29:28'),
(104, 109, 700000.000, 'Thành công', 'VNPAY', 'TXN00104', 'NCB', '20250824101747', '2025-08-24 10:17:47', '2025-08-24 10:17:47'),
(105, 110, 510000.000, 'Thành công', 'VNPAY', 'TXN00105', 'NCB', '20250909043427', '2025-09-09 04:34:27', '2025-09-09 04:34:27'),
(106, 111, 510000.000, 'Thành công', 'VNPAY', 'TXN00106', 'NCB', '20250923022601', '2025-09-23 02:26:01', '2025-09-23 02:26:01'),
(107, 112, 660000.000, 'Thành công', 'VNPAY', 'TXN00107', 'NCB', '20251007202251', '2025-10-07 20:22:51', '2025-10-07 20:22:51'),
(108, 113, 565000.000, 'Thành công', 'VNPAY', 'TXN00108', 'NCB', '20250828102304', '2025-08-28 10:23:04', '2025-08-28 10:23:04'),
(109, 114, 545000.000, 'Thành công', 'VNPAY', 'TXN00109', 'NCB', '20251012163044', '2025-10-12 16:30:44', '2025-10-12 16:30:44'),
(110, 115, 490000.000, 'Thành công', 'VNPAY', 'TXN00110', 'NCB', '20250910052142', '2025-09-10 05:21:42', '2025-09-10 05:21:42'),
(111, 116, 395000.000, 'Thành công', 'VNPAY', 'TXN00111', 'NCB', '20250907222006', '2025-09-07 22:20:06', '2025-09-07 22:20:06'),
(112, 117, 620000.000, 'Thành công', 'VNPAY', 'TXN00112', 'NCB', '20251027184704', '2025-10-27 18:47:04', '2025-10-27 18:47:04'),
(113, 118, 625000.000, 'Thành công', 'VNPAY', 'TXN00113', 'NCB', '20250916042004', '2025-09-16 04:20:04', '2025-09-16 04:20:04'),
(114, 119, 660000.000, 'Thành công', 'VNPAY', 'TXN00114', 'NCB', '20251029225008', '2025-10-29 22:50:08', '2025-10-29 22:50:08'),
(115, 120, 340000.000, 'Thành công', 'VNPAY', 'TXN00115', 'NCB', '20250913045952', '2025-09-13 04:59:52', '2025-09-13 04:59:52'),
(116, 121, 515000.000, 'Thành công', 'VNPAY', 'TXN00116', 'NCB', '20250821171146', '2025-08-21 17:11:46', '2025-08-21 17:11:46'),
(117, 122, 640000.000, 'Thành công', 'VNPAY', 'TXN00117', 'NCB', '20250927132241', '2025-09-27 13:22:41', '2025-09-27 13:22:41'),
(118, 123, 510000.000, 'Thành công', 'VNPAY', 'TXN00118', 'NCB', '20251102030928', '2025-11-02 03:09:28', '2025-11-02 03:09:28'),
(119, 124, 490000.000, 'Thành công', 'VNPAY', 'TXN00119', 'NCB', '20251025020011', '2025-10-25 02:00:11', '2025-10-25 02:00:11'),
(120, 125, 700000.000, 'Thành công', 'VNPAY', 'TXN00120', 'NCB', '20251016140656', '2025-10-16 14:06:56', '2025-10-16 14:06:56'),
(121, 126, 625000.000, 'Thành công', 'VNPAY', 'TXN00121', 'NCB', '20250815050929', '2025-08-15 05:09:29', '2025-08-15 05:09:29'),
(122, 127, 470000.000, 'Thành công', 'VNPAY', 'TXN00122', 'NCB', '20251004045713', '2025-10-04 04:57:13', '2025-10-04 04:57:13'),
(123, 128, 490000.000, 'Thành công', 'VNPAY', 'TXN00123', 'NCB', '20250812023223', '2025-08-12 02:32:23', '2025-08-12 02:32:23');

-- --------------------------------------------------------

--
-- Table structure for table `performances`
--

CREATE TABLE `performances` (
  `performance_id` int(11) NOT NULL,
  `show_id` int(11) DEFAULT NULL,
  `theater_id` int(11) DEFAULT NULL,
  `performance_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time DEFAULT NULL,
  `status` enum('Đang mở bán','Đã hủy','Đã kết thúc') DEFAULT 'Đang mở bán',
  `price` decimal(10,0) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `performances`
--

INSERT INTO `performances` (`performance_id`, `show_id`, `theater_id`, `performance_date`, `start_time`, `end_time`, `status`, `price`, `created_at`, `updated_at`) VALUES
(15, 8, 1, '2025-10-23', '19:30:00', NULL, 'Đã kết thúc', 180000, '2025-08-01 00:00:00', '2025-08-01 00:00:00'),
(16, 8, 2, '2025-10-26', '20:00:00', NULL, 'Đã kết thúc', 180000, '2025-08-01 00:00:00', '2025-08-01 00:00:00'),
(17, 8, 1, '2025-11-27', '19:30:00', NULL, 'Đã kết thúc', 200000, '2025-08-01 00:00:00', '2025-08-01 00:00:00'),
(18, 8, 3, '2025-11-30', '18:00:00', NULL, 'Đã kết thúc', 180000, '2025-08-01 00:00:00', '2025-08-01 00:00:00'),
(19, 9, 2, '2025-11-11', '19:00:00', NULL, 'Đang mở bán', 150000, '2025-08-01 00:00:00', '2025-08-01 00:00:00'),
(20, 9, 3, '2025-11-13', '20:00:00', NULL, 'Đang mở bán', 160000, '2025-08-01 00:00:00', '2025-08-01 00:00:00'),
(21, 9, 1, '2025-11-18', '19:00:00', NULL, 'Đang mở bán', 150000, '2025-08-01 00:00:00', '2025-08-01 00:00:00'),
(22, 9, 2, '2025-11-21', '18:30:00', NULL, 'Đang mở bán', 160000, '2025-08-01 00:00:00', '2025-08-01 00:00:00'),
(23, 10, 3, '2025-11-14', '19:00:00', NULL, 'Đang mở bán', 170000, '2025-08-01 00:00:00', '2025-08-01 00:00:00'),
(24, 10, 1, '2025-11-15', '20:00:00', NULL, 'Đang mở bán', 170000, '2025-08-01 00:00:00', '2025-08-01 00:00:00'),
(25, 10, 2, '2025-11-19', '19:00:00', NULL, 'Đang mở bán', 180000, '2025-08-01 00:00:00', '2025-08-01 00:00:00'),
(26, 10, 1, '2025-11-20', '20:00:00', NULL, 'Đang mở bán', 170000, '2025-08-01 00:00:00', '2025-08-01 00:00:00'),
(27, 10, 3, '2025-11-22', '18:30:00', NULL, 'Đang mở bán', 170000, '2025-08-01 00:00:00', '2025-08-01 00:00:00'),
(28, 11, 1, '2025-11-16', '19:30:00', NULL, 'Đang mở bán', 200000, '2025-08-01 00:00:00', '2025-08-01 00:00:00'),
(29, 11, 2, '2025-11-20', '20:00:00', NULL, 'Đang mở bán', 220000, '2025-08-01 00:00:00', '2025-08-01 00:00:00'),
(30, 11, 1, '2025-11-23', '19:00:00', NULL, 'Đang mở bán', 200000, '2025-08-01 00:00:00', '2025-08-01 00:00:00'),
(31, 10, 3, '2025-11-25', '18:30:00', NULL, 'Đang mở bán', 220000, '2025-08-01 00:00:00', '2025-08-01 00:00:00'),
(32, 12, 2, '2025-11-17', '19:00:00', NULL, 'Đang mở bán', 160000, '2025-08-01 00:00:00', '2025-08-01 00:00:00'),
(33, 12, 1, '2025-11-19', '20:00:00', NULL, 'Đang mở bán', 160000, '2025-08-01 00:00:00', '2025-08-01 00:00:00'),
(34, 12, 3, '2025-11-24', '20:00:00', NULL, 'Đang mở bán', 170000, '2025-08-01 00:00:00', '2025-08-01 00:00:00'),
(35, 12, 2, '2025-11-26', '19:00:00', NULL, 'Đang mở bán', 160000, '2025-08-01 00:00:00', '2025-08-01 00:00:00'),
(41, 18, 1, '2025-11-12', '19:30:00', '21:10:00', 'Đang mở bán', 250000, '2025-11-04 13:08:55', '2025-11-04 13:08:55'),
(42, 18, 2, '2025-11-14', '20:00:00', '21:40:00', 'Đang mở bán', 200000, '2025-11-04 13:09:41', '2025-11-04 13:09:41'),
(43, 18, 3, '2025-11-15', '20:00:00', '21:40:00', 'Đang mở bán', 200000, '2025-11-04 13:10:13', '2025-11-04 13:10:13'),
(44, 18, 1, '2025-11-17', '20:30:00', '22:10:00', 'Đang mở bán', 180000, '2025-11-04 13:10:59', '2025-11-04 13:10:59'),
(45, 19, 2, '2025-11-16', '19:30:00', '21:15:00', 'Đang mở bán', 300000, '2025-11-04 13:11:48', '2025-11-04 13:11:48'),
(46, 19, 1, '2025-11-17', '18:00:00', '19:45:00', 'Đang mở bán', 280000, '2025-11-04 13:12:33', '2025-11-04 13:12:33'),
(47, 19, 3, '2025-11-19', '20:00:00', '21:45:00', 'Đang mở bán', 300000, '2025-11-04 13:13:11', '2025-11-04 13:13:11'),
(48, 19, 1, '2025-11-21', '19:30:00', '21:15:00', 'Đang mở bán', 250000, '2025-11-04 13:13:48', '2025-11-04 13:13:48'),
(49, 13, 1, '2025-11-23', '19:30:00', '21:05:00', 'Đang mở bán', 350000, '2025-11-04 13:41:51', '2025-11-04 13:41:51'),
(50, 13, 2, '2025-11-24', '20:00:00', '21:35:00', 'Đang mở bán', 300000, '2025-11-04 13:42:37', '2025-11-04 13:42:37'),
(51, 17, 3, '2025-11-28', '19:30:00', '21:25:00', 'Đang mở bán', 350000, '2025-11-04 13:43:57', '2025-11-04 13:43:57'),
(52, 17, 2, '2025-11-29', '20:00:00', '21:55:00', 'Đang mở bán', 280000, '2025-11-04 13:44:19', '2025-11-04 13:44:19');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `show_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `content` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`review_id`, `show_id`, `user_id`, `rating`, `content`, `created_at`) VALUES
(1, 9, 2, 5, 'Vở diễn rất hay, diễn viên diễn xuất sắc và câu chuyện lôi cuốn.', '2025-08-05 11:00:00'),
(11, 10, 3, 4, 'Vở diễn rất hay, diễn viên diễn xuất sắc và câu chuyện lôi cuốn.', '2025-09-25 11:00:00'),
(30, 11, 4, 5, 'Vở diễn đem lại nhiều cảm xúc, từ tiếng cười đến nước mắt.', '2025-10-28 11:00:00'),
(31, 19, 2, 5, 'Một trải nghiệm sân khấu vừa kỳ lạ vừa cuốn hút', '2025-11-04 13:34:09'),
(32, 18, 2, 5, 'Ngọt ngào, lãng mạn, và đầy mộng mơ.', '2025-11-04 13:34:49'),
(33, 19, 3, 4, 'Câu chuyện hơi chậm ở giữa nhưng tổng thể rất cảm động.', '2025-11-04 13:36:23'),
(34, 9, 3, 4, 'Ban đầu hơi khó hiểu, nhưng càng xem càng thấy hấp dẫn', '2025-11-04 13:36:47'),
(35, 18, 3, 3, 'Không hợp gu lắm, hơi chậm và chán', '2025-11-04 13:37:10'),
(36, 12, 3, 5, 'Đạo diễn giữ được tinh thần nguyên tác mà vẫn mới mẻ.', '2025-11-04 13:37:36'),
(38, 18, 4, 4, 'Không hợp gu mình lắm nhưng cũng đáng để xem', '2025-11-04 13:39:22');

-- --------------------------------------------------------

--
-- Table structure for table `seats`
--

CREATE TABLE `seats` (
  `seat_id` int(11) NOT NULL,
  `theater_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `row_char` varchar(5) NOT NULL,
  `seat_number` int(11) NOT NULL,
  `real_seat_number` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `seats`
--

INSERT INTO `seats` (`seat_id`, `theater_id`, `category_id`, `row_char`, `seat_number`, `real_seat_number`, `created_at`) VALUES
(1, 1, 1, 'A', 1, 1, '2025-09-24 16:19:02'),
(2, 1, 1, 'A', 2, 2, '2025-09-24 16:19:02'),
(3, 1, 1, 'A', 3, 3, '2025-09-24 16:19:02'),
(4, 1, 1, 'A', 4, 4, '2025-09-24 16:19:02'),
(5, 1, 1, 'A', 5, 5, '2025-09-24 16:19:02'),
(6, 1, 1, 'A', 6, 6, '2025-09-24 16:19:02'),
(7, 1, 1, 'A', 7, 7, '2025-09-24 16:19:02'),
(8, 1, 1, 'A', 8, 8, '2025-09-24 16:19:02'),
(9, 1, 1, 'A', 9, 9, '2025-09-24 16:19:02'),
(10, 1, 1, 'A', 10, 10, '2025-09-24 16:19:02'),
(11, 1, 1, 'B', 1, 1, '2025-09-24 16:19:02'),
(12, 1, 1, 'B', 2, 2, '2025-09-24 16:19:02'),
(13, 1, 1, 'B', 3, 3, '2025-09-24 16:19:02'),
(14, 1, 1, 'B', 4, 4, '2025-09-24 16:19:02'),
(15, 1, 1, 'B', 5, 5, '2025-09-24 16:19:02'),
(16, 1, 1, 'B', 6, 6, '2025-09-24 16:19:02'),
(17, 1, 1, 'B', 7, 7, '2025-09-24 16:19:02'),
(18, 1, 1, 'B', 8, 8, '2025-09-24 16:19:02'),
(19, 1, 1, 'B', 9, 9, '2025-09-24 16:19:02'),
(20, 1, 1, 'B', 10, 10, '2025-09-24 16:19:02'),
(21, 1, 2, 'C', 1, 1, '2025-09-24 16:19:02'),
(22, 1, 2, 'C', 2, 2, '2025-09-24 16:19:02'),
(23, 1, 2, 'C', 3, 3, '2025-09-24 16:19:02'),
(24, 1, 2, 'C', 4, 4, '2025-09-24 16:19:02'),
(25, 1, 2, 'C', 5, 5, '2025-09-24 16:19:02'),
(26, 1, 2, 'C', 6, 6, '2025-09-24 16:19:02'),
(27, 1, 2, 'C', 7, 7, '2025-09-24 16:19:02'),
(28, 1, 2, 'C', 8, 8, '2025-09-24 16:19:02'),
(29, 1, 2, 'C', 9, 9, '2025-09-24 16:19:02'),
(30, 1, 2, 'C', 10, 10, '2025-09-24 16:19:02'),
(31, 1, 3, 'D', 1, 1, '2025-09-24 16:19:02'),
(32, 1, 3, 'D', 2, 2, '2025-09-24 16:19:02'),
(33, 1, 3, 'D', 3, 3, '2025-09-24 16:19:02'),
(34, 1, 3, 'D', 4, 4, '2025-09-24 16:19:02'),
(35, 1, 3, 'D', 5, 5, '2025-09-24 16:19:02'),
(36, 1, 3, 'D', 6, 6, '2025-09-24 16:19:02'),
(37, 1, 3, 'E', 1, 1, '2025-09-24 16:19:02'),
(38, 1, 3, 'E', 2, 2, '2025-09-24 16:19:02'),
(39, 1, 3, 'E', 3, 3, '2025-09-24 16:19:02'),
(40, 1, 3, 'E', 4, 4, '2025-09-24 16:19:02'),
(41, 1, 3, 'E', 5, 5, '2025-09-24 16:19:02'),
(42, 1, 3, 'E', 6, 6, '2025-09-24 16:19:02'),
(43, 1, 3, 'F', 1, 1, '2025-09-24 16:19:02'),
(44, 1, 3, 'F', 2, 2, '2025-09-24 16:19:02'),
(45, 1, 3, 'F', 3, 3, '2025-09-24 16:19:02'),
(46, 1, 3, 'F', 4, 4, '2025-09-24 16:19:02'),
(47, 1, 3, 'F', 5, 5, '2025-09-24 16:19:02'),
(48, 1, 3, 'F', 6, 6, '2025-09-24 16:19:02'),
(49, 1, 3, 'F', 7, 7, '2025-09-24 16:19:02'),
(50, 1, 3, 'F', 8, 8, '2025-09-24 16:19:02'),
(51, 1, 3, 'F', 9, 9, '2025-09-24 16:19:02'),
(52, 1, 3, 'F', 10, 10, '2025-09-24 16:19:02'),
(53, 2, 1, 'A', 1, 1, '2025-09-24 16:19:02'),
(54, 2, 1, 'A', 2, 2, '2025-09-24 16:19:02'),
(55, 2, 1, 'A', 3, 3, '2025-09-24 16:19:02'),
(56, 2, 1, 'A', 4, 4, '2025-09-24 16:19:02'),
(57, 2, 1, 'A', 5, 5, '2025-09-24 16:19:02'),
(58, 2, 1, 'A', 6, 6, '2025-09-24 16:19:02'),
(59, 2, 1, 'B', 1, 1, '2025-09-24 16:19:02'),
(60, 2, 1, 'B', 2, 2, '2025-09-24 16:19:02'),
(61, 2, 1, 'B', 3, 3, '2025-09-24 16:19:02'),
(62, 2, 1, 'B', 4, 4, '2025-09-24 16:19:02'),
(63, 2, 1, 'B', 5, 5, '2025-09-24 16:19:02'),
(64, 2, 1, 'B', 6, 6, '2025-09-24 16:19:02'),
(65, 2, 2, 'C', 1, 1, '2025-09-24 16:19:02'),
(66, 2, 2, 'C', 2, 2, '2025-09-24 16:19:02'),
(67, 2, 2, 'C', 3, 3, '2025-09-24 16:19:02'),
(68, 2, 2, 'C', 4, 4, '2025-09-24 16:19:02'),
(69, 2, 2, 'C', 5, 5, '2025-09-24 16:19:02'),
(70, 2, 2, 'C', 6, 6, '2025-09-24 16:19:02'),
(71, 2, 3, 'D', 1, 1, '2025-09-24 16:19:02'),
(72, 2, 3, 'D', 2, 2, '2025-09-24 16:19:02'),
(73, 2, 3, 'D', 3, 3, '2025-09-24 16:19:02'),
(74, 2, 3, 'D', 4, 4, '2025-09-24 16:19:02'),
(75, 2, 3, 'D', 5, 5, '2025-09-24 16:19:02'),
(76, 2, 3, 'D', 6, 6, '2025-09-24 16:19:02'),
(77, 3, 1, 'A', 1, 1, '2025-09-24 16:19:02'),
(78, 3, 1, 'A', 2, 2, '2025-09-24 16:19:02'),
(79, 3, 1, 'A', 3, 3, '2025-09-24 16:19:02'),
(80, 3, 1, 'A', 4, 4, '2025-09-24 16:19:02'),
(81, 3, 1, 'A', 5, 5, '2025-09-24 16:19:02'),
(82, 3, 1, 'A', 6, 6, '2025-09-24 16:19:02'),
(83, 3, 1, 'B', 1, 1, '2025-09-24 16:19:02'),
(84, 3, 1, 'B', 2, 2, '2025-09-24 16:19:02'),
(85, 3, 1, 'B', 3, 3, '2025-09-24 16:19:02'),
(86, 3, 1, 'B', 4, 4, '2025-09-24 16:19:02'),
(87, 3, 1, 'B', 5, 5, '2025-09-24 16:19:02'),
(88, 3, 1, 'B', 6, 6, '2025-09-24 16:19:02'),
(89, 3, 2, 'C', 1, 1, '2025-09-24 16:19:02'),
(90, 3, 2, 'C', 2, 2, '2025-09-24 16:19:02'),
(91, 3, 2, 'C', 3, 3, '2025-09-24 16:19:02'),
(92, 3, 2, 'C', 4, 4, '2025-09-24 16:19:02'),
(93, 3, 2, 'C', 5, 5, '2025-09-24 16:19:02'),
(94, 3, 2, 'C', 6, 6, '2025-09-24 16:19:02'),
(95, 3, 3, 'D', 1, 1, '2025-09-24 16:19:02'),
(96, 3, 3, 'D', 2, 2, '2025-09-24 16:19:02'),
(97, 3, 3, 'D', 3, 3, '2025-09-24 16:19:02'),
(98, 3, 3, 'D', 4, 4, '2025-09-24 16:19:02'),
(99, 3, 3, 'D', 5, 5, '2025-09-24 16:19:02'),
(100, 3, 3, 'D', 6, 6, '2025-09-24 16:19:02'),
(101, 3, 3, 'E', 1, 1, '2025-09-24 16:19:02'),
(102, 3, 3, 'E', 2, 2, '2025-09-24 16:19:02'),
(103, 3, 3, 'E', 3, 3, '2025-09-24 16:19:02'),
(104, 3, 3, 'E', 4, 4, '2025-09-24 16:19:02'),
(105, 3, 3, 'E', 5, 5, '2025-09-24 16:19:02'),
(106, 3, 3, 'E', 6, 6, '2025-09-24 16:19:02');

-- --------------------------------------------------------

--
-- Table structure for table `seat_categories`
--

CREATE TABLE `seat_categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `base_price` decimal(10,0) NOT NULL,
  `color_class` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `seat_categories`
--

INSERT INTO `seat_categories` (`category_id`, `category_name`, `base_price`, `color_class`) VALUES
(1, 'A', 150000, 'c0d6efd'),
(2, 'B', 75000, 'c198754'),
(3, 'C', 0, 'c6f42c1');

-- --------------------------------------------------------

--
-- Table structure for table `seat_performance`
--

CREATE TABLE `seat_performance` (
  `seat_id` int(11) NOT NULL,
  `performance_id` int(11) NOT NULL,
  `status` enum('trống','đã đặt') NOT NULL DEFAULT 'trống'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `seat_performance`
--

INSERT INTO `seat_performance` (`seat_id`, `performance_id`, `status`) VALUES
(1, 15, 'đã đặt'),
(1, 17, 'trống'),
(1, 21, 'trống'),
(1, 24, 'trống'),
(1, 26, 'trống'),
(1, 28, 'trống'),
(1, 30, 'đã đặt'),
(1, 33, 'trống'),
(1, 41, 'trống'),
(1, 44, 'trống'),
(1, 46, 'trống'),
(1, 48, 'trống'),
(1, 49, 'trống'),
(2, 15, 'trống'),
(2, 17, 'trống'),
(2, 21, 'đã đặt'),
(2, 24, 'trống'),
(2, 26, 'trống'),
(2, 28, 'trống'),
(2, 30, 'trống'),
(2, 33, 'trống'),
(2, 41, 'trống'),
(2, 44, 'trống'),
(2, 46, 'trống'),
(2, 48, 'trống'),
(2, 49, 'trống'),
(3, 15, 'trống'),
(3, 17, 'đã đặt'),
(3, 21, 'trống'),
(3, 24, 'trống'),
(3, 26, 'trống'),
(3, 28, 'trống'),
(3, 30, 'đã đặt'),
(3, 33, 'trống'),
(3, 41, 'trống'),
(3, 44, 'trống'),
(3, 46, 'trống'),
(3, 48, 'trống'),
(3, 49, 'trống'),
(4, 15, 'đã đặt'),
(4, 17, 'đã đặt'),
(4, 21, 'trống'),
(4, 24, 'trống'),
(4, 26, 'trống'),
(4, 28, 'trống'),
(4, 30, 'đã đặt'),
(4, 33, 'trống'),
(4, 41, 'trống'),
(4, 44, 'trống'),
(4, 46, 'trống'),
(4, 48, 'trống'),
(4, 49, 'trống'),
(5, 15, 'trống'),
(5, 17, 'trống'),
(5, 21, 'trống'),
(5, 24, 'trống'),
(5, 26, 'trống'),
(5, 28, 'trống'),
(5, 30, 'trống'),
(5, 33, 'trống'),
(5, 41, 'trống'),
(5, 44, 'trống'),
(5, 46, 'trống'),
(5, 48, 'trống'),
(5, 49, 'trống'),
(6, 15, 'trống'),
(6, 17, 'đã đặt'),
(6, 21, 'đã đặt'),
(6, 24, 'trống'),
(6, 26, 'trống'),
(6, 28, 'đã đặt'),
(6, 30, 'trống'),
(6, 33, 'đã đặt'),
(6, 41, 'trống'),
(6, 44, 'trống'),
(6, 46, 'trống'),
(6, 48, 'trống'),
(6, 49, 'trống'),
(7, 15, 'trống'),
(7, 17, 'trống'),
(7, 21, 'đã đặt'),
(7, 24, 'trống'),
(7, 26, 'trống'),
(7, 28, 'trống'),
(7, 30, 'trống'),
(7, 33, 'trống'),
(7, 41, 'trống'),
(7, 44, 'trống'),
(7, 46, 'trống'),
(7, 48, 'trống'),
(7, 49, 'trống'),
(8, 15, 'trống'),
(8, 17, 'trống'),
(8, 21, 'trống'),
(8, 24, 'trống'),
(8, 26, 'trống'),
(8, 28, 'trống'),
(8, 30, 'trống'),
(8, 33, 'trống'),
(8, 41, 'trống'),
(8, 44, 'trống'),
(8, 46, 'trống'),
(8, 48, 'trống'),
(8, 49, 'trống'),
(9, 15, 'trống'),
(9, 17, 'trống'),
(9, 21, 'trống'),
(9, 24, 'trống'),
(9, 26, 'trống'),
(9, 28, 'trống'),
(9, 30, 'trống'),
(9, 33, 'đã đặt'),
(9, 41, 'trống'),
(9, 44, 'trống'),
(9, 46, 'trống'),
(9, 48, 'trống'),
(9, 49, 'trống'),
(10, 15, 'trống'),
(10, 17, 'đã đặt'),
(10, 21, 'trống'),
(10, 24, 'đã đặt'),
(10, 26, 'đã đặt'),
(10, 28, 'trống'),
(10, 30, 'đã đặt'),
(10, 33, 'trống'),
(10, 41, 'trống'),
(10, 44, 'trống'),
(10, 46, 'trống'),
(10, 48, 'trống'),
(10, 49, 'trống'),
(11, 15, 'đã đặt'),
(11, 17, 'trống'),
(11, 21, 'trống'),
(11, 24, 'trống'),
(11, 26, 'trống'),
(11, 28, 'trống'),
(11, 30, 'đã đặt'),
(11, 33, 'trống'),
(11, 41, 'trống'),
(11, 44, 'trống'),
(11, 46, 'trống'),
(11, 48, 'trống'),
(11, 49, 'trống'),
(12, 15, 'trống'),
(12, 17, 'trống'),
(12, 21, 'trống'),
(12, 24, 'trống'),
(12, 26, 'trống'),
(12, 28, 'trống'),
(12, 30, 'trống'),
(12, 33, 'trống'),
(12, 41, 'trống'),
(12, 44, 'trống'),
(12, 46, 'trống'),
(12, 48, 'trống'),
(12, 49, 'trống'),
(13, 15, 'trống'),
(13, 17, 'trống'),
(13, 21, 'trống'),
(13, 24, 'đã đặt'),
(13, 26, 'trống'),
(13, 28, 'trống'),
(13, 30, 'đã đặt'),
(13, 33, 'trống'),
(13, 41, 'trống'),
(13, 44, 'trống'),
(13, 46, 'trống'),
(13, 48, 'trống'),
(13, 49, 'trống'),
(14, 15, 'trống'),
(14, 17, 'trống'),
(14, 21, 'trống'),
(14, 24, 'trống'),
(14, 26, 'trống'),
(14, 28, 'trống'),
(14, 30, 'đã đặt'),
(14, 33, 'trống'),
(14, 41, 'trống'),
(14, 44, 'trống'),
(14, 46, 'trống'),
(14, 48, 'trống'),
(14, 49, 'trống'),
(15, 15, 'trống'),
(15, 17, 'trống'),
(15, 21, 'đã đặt'),
(15, 24, 'trống'),
(15, 26, 'đã đặt'),
(15, 28, 'trống'),
(15, 30, 'trống'),
(15, 33, 'trống'),
(15, 41, 'trống'),
(15, 44, 'trống'),
(15, 46, 'trống'),
(15, 48, 'trống'),
(15, 49, 'trống'),
(16, 15, 'trống'),
(16, 17, 'trống'),
(16, 21, 'trống'),
(16, 24, 'trống'),
(16, 26, 'trống'),
(16, 28, 'trống'),
(16, 30, 'đã đặt'),
(16, 33, 'trống'),
(16, 41, 'trống'),
(16, 44, 'trống'),
(16, 46, 'trống'),
(16, 48, 'trống'),
(16, 49, 'trống'),
(17, 15, 'trống'),
(17, 17, 'trống'),
(17, 21, 'trống'),
(17, 24, 'trống'),
(17, 26, 'trống'),
(17, 28, 'trống'),
(17, 30, 'trống'),
(17, 33, 'trống'),
(17, 41, 'trống'),
(17, 44, 'trống'),
(17, 46, 'trống'),
(17, 48, 'trống'),
(17, 49, 'trống'),
(18, 15, 'đã đặt'),
(18, 17, 'trống'),
(18, 21, 'đã đặt'),
(18, 24, 'trống'),
(18, 26, 'trống'),
(18, 28, 'trống'),
(18, 30, 'đã đặt'),
(18, 33, 'đã đặt'),
(18, 41, 'trống'),
(18, 44, 'trống'),
(18, 46, 'trống'),
(18, 48, 'trống'),
(18, 49, 'trống'),
(19, 15, 'đã đặt'),
(19, 17, 'trống'),
(19, 21, 'trống'),
(19, 24, 'trống'),
(19, 26, 'trống'),
(19, 28, 'trống'),
(19, 30, 'trống'),
(19, 33, 'trống'),
(19, 41, 'trống'),
(19, 44, 'trống'),
(19, 46, 'trống'),
(19, 48, 'trống'),
(19, 49, 'trống'),
(20, 15, 'trống'),
(20, 17, 'trống'),
(20, 21, 'trống'),
(20, 24, 'trống'),
(20, 26, 'trống'),
(20, 28, 'trống'),
(20, 30, 'trống'),
(20, 33, 'trống'),
(20, 41, 'trống'),
(20, 44, 'trống'),
(20, 46, 'trống'),
(20, 48, 'trống'),
(20, 49, 'trống'),
(21, 15, 'đã đặt'),
(21, 17, 'đã đặt'),
(21, 21, 'trống'),
(21, 24, 'đã đặt'),
(21, 26, 'trống'),
(21, 28, 'trống'),
(21, 30, 'trống'),
(21, 33, 'đã đặt'),
(21, 41, 'trống'),
(21, 44, 'trống'),
(21, 46, 'trống'),
(21, 48, 'trống'),
(21, 49, 'trống'),
(22, 15, 'trống'),
(22, 17, 'trống'),
(22, 21, 'trống'),
(22, 24, 'trống'),
(22, 26, 'trống'),
(22, 28, 'trống'),
(22, 30, 'trống'),
(22, 33, 'đã đặt'),
(22, 41, 'trống'),
(22, 44, 'trống'),
(22, 46, 'trống'),
(22, 48, 'trống'),
(22, 49, 'trống'),
(23, 15, 'trống'),
(23, 17, 'trống'),
(23, 21, 'trống'),
(23, 24, 'trống'),
(23, 26, 'trống'),
(23, 28, 'trống'),
(23, 30, 'trống'),
(23, 33, 'đã đặt'),
(23, 41, 'trống'),
(23, 44, 'trống'),
(23, 46, 'trống'),
(23, 48, 'trống'),
(23, 49, 'trống'),
(24, 15, 'trống'),
(24, 17, 'trống'),
(24, 21, 'trống'),
(24, 24, 'trống'),
(24, 26, 'trống'),
(24, 28, 'trống'),
(24, 30, 'trống'),
(24, 33, 'trống'),
(24, 41, 'trống'),
(24, 44, 'trống'),
(24, 46, 'trống'),
(24, 48, 'trống'),
(24, 49, 'trống'),
(25, 15, 'trống'),
(25, 17, 'trống'),
(25, 21, 'trống'),
(25, 24, 'trống'),
(25, 26, 'trống'),
(25, 28, 'đã đặt'),
(25, 30, 'đã đặt'),
(25, 33, 'đã đặt'),
(25, 41, 'trống'),
(25, 44, 'trống'),
(25, 46, 'trống'),
(25, 48, 'trống'),
(25, 49, 'trống'),
(26, 15, 'trống'),
(26, 17, 'đã đặt'),
(26, 21, 'đã đặt'),
(26, 24, 'trống'),
(26, 26, 'đã đặt'),
(26, 28, 'trống'),
(26, 30, 'trống'),
(26, 33, 'trống'),
(26, 41, 'trống'),
(26, 44, 'trống'),
(26, 46, 'trống'),
(26, 48, 'trống'),
(26, 49, 'trống'),
(27, 15, 'trống'),
(27, 17, 'trống'),
(27, 21, 'trống'),
(27, 24, 'trống'),
(27, 26, 'trống'),
(27, 28, 'đã đặt'),
(27, 30, 'trống'),
(27, 33, 'trống'),
(27, 41, 'trống'),
(27, 44, 'trống'),
(27, 46, 'trống'),
(27, 48, 'trống'),
(27, 49, 'trống'),
(28, 15, 'trống'),
(28, 17, 'đã đặt'),
(28, 21, 'trống'),
(28, 24, 'trống'),
(28, 26, 'trống'),
(28, 28, 'trống'),
(28, 30, 'trống'),
(28, 33, 'đã đặt'),
(28, 41, 'trống'),
(28, 44, 'trống'),
(28, 46, 'trống'),
(28, 48, 'trống'),
(28, 49, 'trống'),
(29, 15, 'đã đặt'),
(29, 17, 'trống'),
(29, 21, 'trống'),
(29, 24, 'trống'),
(29, 26, 'trống'),
(29, 28, 'trống'),
(29, 30, 'trống'),
(29, 33, 'đã đặt'),
(29, 41, 'trống'),
(29, 44, 'trống'),
(29, 46, 'trống'),
(29, 48, 'trống'),
(29, 49, 'trống'),
(30, 15, 'trống'),
(30, 17, 'trống'),
(30, 21, 'trống'),
(30, 24, 'trống'),
(30, 26, 'trống'),
(30, 28, 'trống'),
(30, 30, 'trống'),
(30, 33, 'đã đặt'),
(30, 41, 'trống'),
(30, 44, 'trống'),
(30, 46, 'trống'),
(30, 48, 'trống'),
(30, 49, 'trống'),
(31, 15, 'đã đặt'),
(31, 17, 'trống'),
(31, 21, 'trống'),
(31, 24, 'trống'),
(31, 26, 'trống'),
(31, 28, 'trống'),
(31, 30, 'trống'),
(31, 33, 'trống'),
(31, 41, 'trống'),
(31, 44, 'trống'),
(31, 46, 'trống'),
(31, 48, 'trống'),
(31, 49, 'trống'),
(32, 15, 'trống'),
(32, 17, 'trống'),
(32, 21, 'trống'),
(32, 24, 'đã đặt'),
(32, 26, 'đã đặt'),
(32, 28, 'trống'),
(32, 30, 'trống'),
(32, 33, 'trống'),
(32, 41, 'trống'),
(32, 44, 'trống'),
(32, 46, 'trống'),
(32, 48, 'trống'),
(32, 49, 'trống'),
(33, 15, 'trống'),
(33, 17, 'trống'),
(33, 21, 'đã đặt'),
(33, 24, 'trống'),
(33, 26, 'trống'),
(33, 28, 'trống'),
(33, 30, 'trống'),
(33, 33, 'trống'),
(33, 41, 'trống'),
(33, 44, 'trống'),
(33, 46, 'trống'),
(33, 48, 'trống'),
(33, 49, 'trống'),
(34, 15, 'trống'),
(34, 17, 'trống'),
(34, 21, 'trống'),
(34, 24, 'trống'),
(34, 26, 'trống'),
(34, 28, 'đã đặt'),
(34, 30, 'trống'),
(34, 33, 'trống'),
(34, 41, 'trống'),
(34, 44, 'trống'),
(34, 46, 'trống'),
(34, 48, 'trống'),
(34, 49, 'trống'),
(35, 15, 'trống'),
(35, 17, 'trống'),
(35, 21, 'trống'),
(35, 24, 'trống'),
(35, 26, 'trống'),
(35, 28, 'trống'),
(35, 30, 'trống'),
(35, 33, 'trống'),
(35, 41, 'trống'),
(35, 44, 'trống'),
(35, 46, 'trống'),
(35, 48, 'trống'),
(35, 49, 'trống'),
(36, 15, 'trống'),
(36, 17, 'trống'),
(36, 21, 'đã đặt'),
(36, 24, 'trống'),
(36, 26, 'trống'),
(36, 28, 'đã đặt'),
(36, 30, 'trống'),
(36, 33, 'trống'),
(36, 41, 'trống'),
(36, 44, 'trống'),
(36, 46, 'trống'),
(36, 48, 'trống'),
(36, 49, 'trống'),
(37, 15, 'đã đặt'),
(37, 17, 'trống'),
(37, 21, 'trống'),
(37, 24, 'trống'),
(37, 26, 'trống'),
(37, 28, 'trống'),
(37, 30, 'trống'),
(37, 33, 'trống'),
(37, 41, 'trống'),
(37, 44, 'trống'),
(37, 46, 'trống'),
(37, 48, 'trống'),
(37, 49, 'trống'),
(38, 15, 'trống'),
(38, 17, 'trống'),
(38, 21, 'đã đặt'),
(38, 24, 'trống'),
(38, 26, 'trống'),
(38, 28, 'trống'),
(38, 30, 'trống'),
(38, 33, 'trống'),
(38, 41, 'trống'),
(38, 44, 'trống'),
(38, 46, 'trống'),
(38, 48, 'trống'),
(38, 49, 'trống'),
(39, 15, 'trống'),
(39, 17, 'trống'),
(39, 21, 'trống'),
(39, 24, 'trống'),
(39, 26, 'trống'),
(39, 28, 'trống'),
(39, 30, 'trống'),
(39, 33, 'trống'),
(39, 41, 'trống'),
(39, 44, 'trống'),
(39, 46, 'trống'),
(39, 48, 'trống'),
(39, 49, 'trống'),
(40, 15, 'trống'),
(40, 17, 'trống'),
(40, 21, 'trống'),
(40, 24, 'trống'),
(40, 26, 'đã đặt'),
(40, 28, 'trống'),
(40, 30, 'trống'),
(40, 33, 'trống'),
(40, 41, 'trống'),
(40, 44, 'trống'),
(40, 46, 'trống'),
(40, 48, 'trống'),
(40, 49, 'trống'),
(41, 15, 'trống'),
(41, 17, 'trống'),
(41, 21, 'trống'),
(41, 24, 'trống'),
(41, 26, 'trống'),
(41, 28, 'trống'),
(41, 30, 'trống'),
(41, 33, 'trống'),
(41, 41, 'trống'),
(41, 44, 'trống'),
(41, 46, 'trống'),
(41, 48, 'trống'),
(41, 49, 'trống'),
(42, 15, 'trống'),
(42, 17, 'trống'),
(42, 21, 'đã đặt'),
(42, 24, 'trống'),
(42, 26, 'trống'),
(42, 28, 'trống'),
(42, 30, 'trống'),
(42, 33, 'trống'),
(42, 41, 'trống'),
(42, 44, 'trống'),
(42, 46, 'trống'),
(42, 48, 'trống'),
(42, 49, 'trống'),
(43, 15, 'trống'),
(43, 17, 'đã đặt'),
(43, 21, 'trống'),
(43, 24, 'đã đặt'),
(43, 26, 'trống'),
(43, 28, 'trống'),
(43, 30, 'trống'),
(43, 33, 'trống'),
(43, 41, 'trống'),
(43, 44, 'trống'),
(43, 46, 'trống'),
(43, 48, 'trống'),
(43, 49, 'trống'),
(44, 15, 'trống'),
(44, 17, 'trống'),
(44, 21, 'trống'),
(44, 24, 'trống'),
(44, 26, 'đã đặt'),
(44, 28, 'trống'),
(44, 30, 'đã đặt'),
(44, 33, 'trống'),
(44, 41, 'trống'),
(44, 44, 'trống'),
(44, 46, 'trống'),
(44, 48, 'trống'),
(44, 49, 'trống'),
(45, 15, 'trống'),
(45, 17, 'trống'),
(45, 21, 'trống'),
(45, 24, 'trống'),
(45, 26, 'trống'),
(45, 28, 'trống'),
(45, 30, 'trống'),
(45, 33, 'trống'),
(45, 41, 'trống'),
(45, 44, 'trống'),
(45, 46, 'trống'),
(45, 48, 'trống'),
(45, 49, 'trống'),
(46, 15, 'trống'),
(46, 17, 'trống'),
(46, 21, 'trống'),
(46, 24, 'trống'),
(46, 26, 'trống'),
(46, 28, 'trống'),
(46, 30, 'trống'),
(46, 33, 'trống'),
(46, 41, 'trống'),
(46, 44, 'trống'),
(46, 46, 'trống'),
(46, 48, 'trống'),
(46, 49, 'trống'),
(47, 15, 'đã đặt'),
(47, 17, 'trống'),
(47, 21, 'trống'),
(47, 24, 'trống'),
(47, 26, 'trống'),
(47, 28, 'trống'),
(47, 30, 'trống'),
(47, 33, 'đã đặt'),
(47, 41, 'trống'),
(47, 44, 'trống'),
(47, 46, 'trống'),
(47, 48, 'trống'),
(47, 49, 'trống'),
(48, 15, 'trống'),
(48, 17, 'trống'),
(48, 21, 'trống'),
(48, 24, 'trống'),
(48, 26, 'trống'),
(48, 28, 'trống'),
(48, 30, 'trống'),
(48, 33, 'trống'),
(48, 41, 'trống'),
(48, 44, 'trống'),
(48, 46, 'trống'),
(48, 48, 'trống'),
(48, 49, 'trống'),
(49, 15, 'trống'),
(49, 17, 'trống'),
(49, 21, 'trống'),
(49, 24, 'đã đặt'),
(49, 26, 'trống'),
(49, 28, 'trống'),
(49, 30, 'trống'),
(49, 33, 'trống'),
(49, 41, 'trống'),
(49, 44, 'trống'),
(49, 46, 'trống'),
(49, 48, 'trống'),
(49, 49, 'trống'),
(50, 15, 'trống'),
(50, 17, 'trống'),
(50, 21, 'trống'),
(50, 24, 'trống'),
(50, 26, 'trống'),
(50, 28, 'đã đặt'),
(50, 30, 'đã đặt'),
(50, 33, 'trống'),
(50, 41, 'trống'),
(50, 44, 'trống'),
(50, 46, 'trống'),
(50, 48, 'trống'),
(50, 49, 'trống'),
(51, 15, 'trống'),
(51, 17, 'trống'),
(51, 21, 'trống'),
(51, 24, 'trống'),
(51, 26, 'trống'),
(51, 28, 'trống'),
(51, 30, 'trống'),
(51, 33, 'trống'),
(51, 41, 'trống'),
(51, 44, 'trống'),
(51, 46, 'trống'),
(51, 48, 'trống'),
(51, 49, 'trống'),
(52, 15, 'trống'),
(52, 17, 'trống'),
(52, 21, 'trống'),
(52, 24, 'trống'),
(52, 26, 'trống'),
(52, 28, 'trống'),
(52, 30, 'trống'),
(52, 33, 'đã đặt'),
(52, 41, 'trống'),
(52, 44, 'trống'),
(52, 46, 'trống'),
(52, 48, 'trống'),
(52, 49, 'trống'),
(53, 16, 'trống'),
(53, 19, 'trống'),
(53, 22, 'trống'),
(53, 25, 'trống'),
(53, 29, 'đã đặt'),
(53, 32, 'trống'),
(53, 35, 'đã đặt'),
(53, 42, 'trống'),
(53, 45, 'trống'),
(53, 50, 'trống'),
(53, 52, 'trống'),
(54, 16, 'trống'),
(54, 19, 'trống'),
(54, 22, 'đã đặt'),
(54, 25, 'trống'),
(54, 29, 'đã đặt'),
(54, 32, 'trống'),
(54, 35, 'trống'),
(54, 42, 'trống'),
(54, 45, 'trống'),
(54, 50, 'trống'),
(54, 52, 'trống'),
(55, 16, 'đã đặt'),
(55, 19, 'trống'),
(55, 22, 'trống'),
(55, 25, 'đã đặt'),
(55, 29, 'đã đặt'),
(55, 32, 'đã đặt'),
(55, 35, 'trống'),
(55, 42, 'trống'),
(55, 45, 'trống'),
(55, 50, 'trống'),
(55, 52, 'trống'),
(56, 16, 'đã đặt'),
(56, 19, 'trống'),
(56, 22, 'trống'),
(56, 25, 'đã đặt'),
(56, 29, 'đã đặt'),
(56, 32, 'đã đặt'),
(56, 35, 'trống'),
(56, 42, 'trống'),
(56, 45, 'trống'),
(56, 50, 'trống'),
(56, 52, 'trống'),
(57, 16, 'trống'),
(57, 19, 'trống'),
(57, 22, 'trống'),
(57, 25, 'trống'),
(57, 29, 'trống'),
(57, 32, 'trống'),
(57, 35, 'đã đặt'),
(57, 42, 'trống'),
(57, 45, 'trống'),
(57, 50, 'trống'),
(57, 52, 'trống'),
(58, 16, 'đã đặt'),
(58, 19, 'trống'),
(58, 22, 'trống'),
(58, 25, 'trống'),
(58, 29, 'đã đặt'),
(58, 32, 'đã đặt'),
(58, 35, 'đã đặt'),
(58, 42, 'trống'),
(58, 45, 'trống'),
(58, 50, 'trống'),
(58, 52, 'trống'),
(59, 16, 'đã đặt'),
(59, 19, 'trống'),
(59, 22, 'trống'),
(59, 25, 'trống'),
(59, 29, 'đã đặt'),
(59, 32, 'đã đặt'),
(59, 35, 'đã đặt'),
(59, 42, 'trống'),
(59, 45, 'trống'),
(59, 50, 'trống'),
(59, 52, 'trống'),
(60, 16, 'đã đặt'),
(60, 19, 'trống'),
(60, 22, 'trống'),
(60, 25, 'đã đặt'),
(60, 29, 'trống'),
(60, 32, 'đã đặt'),
(60, 35, 'trống'),
(60, 42, 'trống'),
(60, 45, 'trống'),
(60, 50, 'trống'),
(60, 52, 'trống'),
(61, 16, 'đã đặt'),
(61, 19, 'trống'),
(61, 22, 'trống'),
(61, 25, 'trống'),
(61, 29, 'trống'),
(61, 32, 'trống'),
(61, 35, 'trống'),
(61, 42, 'trống'),
(61, 45, 'trống'),
(61, 50, 'trống'),
(61, 52, 'trống'),
(62, 16, 'đã đặt'),
(62, 19, 'trống'),
(62, 22, 'trống'),
(62, 25, 'đã đặt'),
(62, 29, 'trống'),
(62, 32, 'trống'),
(62, 35, 'trống'),
(62, 42, 'trống'),
(62, 45, 'trống'),
(62, 50, 'trống'),
(62, 52, 'trống'),
(63, 16, 'đã đặt'),
(63, 19, 'trống'),
(63, 22, 'trống'),
(63, 25, 'trống'),
(63, 29, 'đã đặt'),
(63, 32, 'đã đặt'),
(63, 35, 'trống'),
(63, 42, 'trống'),
(63, 45, 'trống'),
(63, 50, 'trống'),
(63, 52, 'trống'),
(64, 16, 'trống'),
(64, 19, 'đã đặt'),
(64, 22, 'trống'),
(64, 25, 'trống'),
(64, 29, 'đã đặt'),
(64, 32, 'đã đặt'),
(64, 35, 'trống'),
(64, 42, 'trống'),
(64, 45, 'trống'),
(64, 50, 'trống'),
(64, 52, 'trống'),
(65, 16, 'trống'),
(65, 19, 'trống'),
(65, 22, 'trống'),
(65, 25, 'đã đặt'),
(65, 29, 'đã đặt'),
(65, 32, 'đã đặt'),
(65, 35, 'trống'),
(65, 42, 'trống'),
(65, 45, 'trống'),
(65, 50, 'trống'),
(65, 52, 'trống'),
(66, 16, 'trống'),
(66, 19, 'trống'),
(66, 22, 'trống'),
(66, 25, 'đã đặt'),
(66, 29, 'trống'),
(66, 32, 'đã đặt'),
(66, 35, 'đã đặt'),
(66, 42, 'trống'),
(66, 45, 'trống'),
(66, 50, 'trống'),
(66, 52, 'trống'),
(67, 16, 'trống'),
(67, 19, 'trống'),
(67, 22, 'đã đặt'),
(67, 25, 'trống'),
(67, 29, 'trống'),
(67, 32, 'trống'),
(67, 35, 'trống'),
(67, 42, 'trống'),
(67, 45, 'trống'),
(67, 50, 'trống'),
(67, 52, 'trống'),
(68, 16, 'đã đặt'),
(68, 19, 'trống'),
(68, 22, 'đã đặt'),
(68, 25, 'trống'),
(68, 29, 'đã đặt'),
(68, 32, 'trống'),
(68, 35, 'trống'),
(68, 42, 'trống'),
(68, 45, 'trống'),
(68, 50, 'trống'),
(68, 52, 'trống'),
(69, 16, 'trống'),
(69, 19, 'đã đặt'),
(69, 22, 'trống'),
(69, 25, 'trống'),
(69, 29, 'đã đặt'),
(69, 32, 'đã đặt'),
(69, 35, 'đã đặt'),
(69, 42, 'trống'),
(69, 45, 'trống'),
(69, 50, 'trống'),
(69, 52, 'trống'),
(70, 16, 'trống'),
(70, 19, 'trống'),
(70, 22, 'đã đặt'),
(70, 25, 'đã đặt'),
(70, 29, 'đã đặt'),
(70, 32, 'đã đặt'),
(70, 35, 'đã đặt'),
(70, 42, 'trống'),
(70, 45, 'trống'),
(70, 50, 'trống'),
(70, 52, 'trống'),
(71, 16, 'trống'),
(71, 19, 'đã đặt'),
(71, 22, 'trống'),
(71, 25, 'đã đặt'),
(71, 29, 'đã đặt'),
(71, 32, 'đã đặt'),
(71, 35, 'trống'),
(71, 42, 'trống'),
(71, 45, 'trống'),
(71, 50, 'trống'),
(71, 52, 'trống'),
(72, 16, 'trống'),
(72, 19, 'trống'),
(72, 22, 'đã đặt'),
(72, 25, 'đã đặt'),
(72, 29, 'đã đặt'),
(72, 32, 'trống'),
(72, 35, 'trống'),
(72, 42, 'trống'),
(72, 45, 'trống'),
(72, 50, 'trống'),
(72, 52, 'trống'),
(73, 16, 'đã đặt'),
(73, 19, 'trống'),
(73, 22, 'trống'),
(73, 25, 'trống'),
(73, 29, 'đã đặt'),
(73, 32, 'trống'),
(73, 35, 'đã đặt'),
(73, 42, 'trống'),
(73, 45, 'trống'),
(73, 50, 'trống'),
(73, 52, 'trống'),
(74, 16, 'trống'),
(74, 19, 'đã đặt'),
(74, 22, 'trống'),
(74, 25, 'trống'),
(74, 29, 'đã đặt'),
(74, 32, 'trống'),
(74, 35, 'đã đặt'),
(74, 42, 'trống'),
(74, 45, 'trống'),
(74, 50, 'trống'),
(74, 52, 'trống'),
(75, 16, 'đã đặt'),
(75, 19, 'trống'),
(75, 22, 'trống'),
(75, 25, 'đã đặt'),
(75, 29, 'trống'),
(75, 32, 'đã đặt'),
(75, 35, 'trống'),
(75, 42, 'trống'),
(75, 45, 'trống'),
(75, 50, 'trống'),
(75, 52, 'trống'),
(76, 16, 'đã đặt'),
(76, 19, 'trống'),
(76, 22, 'đã đặt'),
(76, 25, 'trống'),
(76, 29, 'trống'),
(76, 32, 'đã đặt'),
(76, 35, 'đã đặt'),
(76, 42, 'trống'),
(76, 45, 'trống'),
(76, 50, 'trống'),
(76, 52, 'trống'),
(77, 18, 'đã đặt'),
(77, 20, 'trống'),
(77, 23, 'trống'),
(77, 27, 'đã đặt'),
(77, 31, 'trống'),
(77, 34, 'đã đặt'),
(77, 43, 'trống'),
(77, 47, 'trống'),
(77, 51, 'trống'),
(78, 18, 'trống'),
(78, 20, 'trống'),
(78, 23, 'trống'),
(78, 27, 'đã đặt'),
(78, 31, 'trống'),
(78, 34, 'đã đặt'),
(78, 43, 'trống'),
(78, 47, 'trống'),
(78, 51, 'trống'),
(79, 18, 'đã đặt'),
(79, 20, 'trống'),
(79, 23, 'trống'),
(79, 27, 'đã đặt'),
(79, 31, 'trống'),
(79, 34, 'trống'),
(79, 43, 'trống'),
(79, 47, 'trống'),
(79, 51, 'trống'),
(80, 18, 'trống'),
(80, 20, 'trống'),
(80, 23, 'trống'),
(80, 27, 'đã đặt'),
(80, 31, 'đã đặt'),
(80, 34, 'trống'),
(80, 43, 'trống'),
(80, 47, 'trống'),
(80, 51, 'trống'),
(81, 18, 'trống'),
(81, 20, 'trống'),
(81, 23, 'trống'),
(81, 27, 'đã đặt'),
(81, 31, 'trống'),
(81, 34, 'trống'),
(81, 43, 'trống'),
(81, 47, 'trống'),
(81, 51, 'trống'),
(82, 18, 'đã đặt'),
(82, 20, 'trống'),
(82, 23, 'trống'),
(82, 27, 'đã đặt'),
(82, 31, 'trống'),
(82, 34, 'trống'),
(82, 43, 'trống'),
(82, 47, 'trống'),
(82, 51, 'trống'),
(83, 18, 'đã đặt'),
(83, 20, 'trống'),
(83, 23, 'trống'),
(83, 27, 'trống'),
(83, 31, 'trống'),
(83, 34, 'trống'),
(83, 43, 'trống'),
(83, 47, 'trống'),
(83, 51, 'trống'),
(84, 18, 'đã đặt'),
(84, 20, 'trống'),
(84, 23, 'đã đặt'),
(84, 27, 'đã đặt'),
(84, 31, 'đã đặt'),
(84, 34, 'trống'),
(84, 43, 'trống'),
(84, 47, 'trống'),
(84, 51, 'trống'),
(85, 18, 'trống'),
(85, 20, 'trống'),
(85, 23, 'trống'),
(85, 27, 'trống'),
(85, 31, 'đã đặt'),
(85, 34, 'trống'),
(85, 43, 'trống'),
(85, 47, 'trống'),
(85, 51, 'trống'),
(86, 18, 'trống'),
(86, 20, 'trống'),
(86, 23, 'trống'),
(86, 27, 'trống'),
(86, 31, 'đã đặt'),
(86, 34, 'trống'),
(86, 43, 'trống'),
(86, 47, 'trống'),
(86, 51, 'trống'),
(87, 18, 'trống'),
(87, 20, 'trống'),
(87, 23, 'trống'),
(87, 27, 'đã đặt'),
(87, 31, 'trống'),
(87, 34, 'đã đặt'),
(87, 43, 'trống'),
(87, 47, 'trống'),
(87, 51, 'trống'),
(88, 18, 'trống'),
(88, 20, 'đã đặt'),
(88, 23, 'trống'),
(88, 27, 'đã đặt'),
(88, 31, 'trống'),
(88, 34, 'đã đặt'),
(88, 43, 'trống'),
(88, 47, 'trống'),
(88, 51, 'trống'),
(89, 18, 'trống'),
(89, 20, 'đã đặt'),
(89, 23, 'trống'),
(89, 27, 'đã đặt'),
(89, 31, 'đã đặt'),
(89, 34, 'trống'),
(89, 43, 'trống'),
(89, 47, 'trống'),
(89, 51, 'trống'),
(90, 18, 'trống'),
(90, 20, 'trống'),
(90, 23, 'đã đặt'),
(90, 27, 'đã đặt'),
(90, 31, 'trống'),
(90, 34, 'trống'),
(90, 43, 'trống'),
(90, 47, 'trống'),
(90, 51, 'trống'),
(91, 18, 'trống'),
(91, 20, 'đã đặt'),
(91, 23, 'trống'),
(91, 27, 'trống'),
(91, 31, 'đã đặt'),
(91, 34, 'trống'),
(91, 43, 'trống'),
(91, 47, 'trống'),
(91, 51, 'trống'),
(92, 18, 'đã đặt'),
(92, 20, 'trống'),
(92, 23, 'trống'),
(92, 27, 'đã đặt'),
(92, 31, 'đã đặt'),
(92, 34, 'trống'),
(92, 43, 'trống'),
(92, 47, 'trống'),
(92, 51, 'trống'),
(93, 18, 'trống'),
(93, 20, 'trống'),
(93, 23, 'trống'),
(93, 27, 'đã đặt'),
(93, 31, 'trống'),
(93, 34, 'đã đặt'),
(93, 43, 'trống'),
(93, 47, 'trống'),
(93, 51, 'trống'),
(94, 18, 'trống'),
(94, 20, 'trống'),
(94, 23, 'trống'),
(94, 27, 'trống'),
(94, 31, 'đã đặt'),
(94, 34, 'trống'),
(94, 43, 'trống'),
(94, 47, 'trống'),
(94, 51, 'trống'),
(95, 18, 'trống'),
(95, 20, 'đã đặt'),
(95, 23, 'trống'),
(95, 27, 'đã đặt'),
(95, 31, 'đã đặt'),
(95, 34, 'trống'),
(95, 43, 'trống'),
(95, 47, 'trống'),
(95, 51, 'trống'),
(96, 18, 'đã đặt'),
(96, 20, 'trống'),
(96, 23, 'đã đặt'),
(96, 27, 'trống'),
(96, 31, 'đã đặt'),
(96, 34, 'đã đặt'),
(96, 43, 'trống'),
(96, 47, 'trống'),
(96, 51, 'trống'),
(97, 18, 'trống'),
(97, 20, 'trống'),
(97, 23, 'đã đặt'),
(97, 27, 'trống'),
(97, 31, 'đã đặt'),
(97, 34, 'đã đặt'),
(97, 43, 'trống'),
(97, 47, 'trống'),
(97, 51, 'trống'),
(98, 18, 'trống'),
(98, 20, 'trống'),
(98, 23, 'trống'),
(98, 27, 'trống'),
(98, 31, 'trống'),
(98, 34, 'trống'),
(98, 43, 'trống'),
(98, 47, 'trống'),
(98, 51, 'trống'),
(99, 18, 'đã đặt'),
(99, 20, 'trống'),
(99, 23, 'trống'),
(99, 27, 'đã đặt'),
(99, 31, 'trống'),
(99, 34, 'trống'),
(99, 43, 'trống'),
(99, 47, 'trống'),
(99, 51, 'trống'),
(100, 18, 'trống'),
(100, 20, 'trống'),
(100, 23, 'trống'),
(100, 27, 'trống'),
(100, 31, 'trống'),
(100, 34, 'trống'),
(100, 43, 'trống'),
(100, 47, 'trống'),
(100, 51, 'trống'),
(101, 18, 'trống'),
(101, 20, 'trống'),
(101, 23, 'trống'),
(101, 27, 'đã đặt'),
(101, 31, 'trống'),
(101, 34, 'trống'),
(101, 43, 'trống'),
(101, 47, 'trống'),
(101, 51, 'trống'),
(102, 18, 'trống'),
(102, 20, 'đã đặt'),
(102, 23, 'trống'),
(102, 27, 'trống'),
(102, 31, 'đã đặt'),
(102, 34, 'trống'),
(102, 43, 'trống'),
(102, 47, 'trống'),
(102, 51, 'trống'),
(103, 18, 'đã đặt'),
(103, 20, 'trống'),
(103, 23, 'trống'),
(103, 27, 'trống'),
(103, 31, 'trống'),
(103, 34, 'trống'),
(103, 43, 'trống'),
(103, 47, 'trống'),
(103, 51, 'trống'),
(104, 18, 'trống'),
(104, 20, 'trống'),
(104, 23, 'trống'),
(104, 27, 'trống'),
(104, 31, 'trống'),
(104, 34, 'trống'),
(104, 43, 'trống'),
(104, 47, 'trống'),
(104, 51, 'trống'),
(105, 18, 'đã đặt'),
(105, 20, 'trống'),
(105, 23, 'trống'),
(105, 27, 'đã đặt'),
(105, 31, 'trống'),
(105, 34, 'đã đặt'),
(105, 43, 'trống'),
(105, 47, 'trống'),
(105, 51, 'trống'),
(106, 18, 'trống'),
(106, 20, 'đã đặt'),
(106, 23, 'trống'),
(106, 27, 'đã đặt'),
(106, 31, 'trống'),
(106, 34, 'trống'),
(106, 43, 'trống'),
(106, 47, 'trống'),
(106, 51, 'trống');

-- --------------------------------------------------------

--
-- Table structure for table `shows`
--

CREATE TABLE `shows` (
  `show_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT NULL,
  `director` varchar(255) DEFAULT NULL,
  `poster_image_url` varchar(255) DEFAULT NULL,
  `status` enum('Sắp chiếu','Đang chiếu','Đã kết thúc') NOT NULL DEFAULT 'Sắp chiếu',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `shows`
--

INSERT INTO `shows` (`show_id`, `title`, `description`, `duration_minutes`, `director`, `poster_image_url`, `status`, `created_at`, `updated_at`) VALUES
(8, 'Đứt dây tơ chùng', 'Câu chuyện xoay quanh những giằng xé trong tình yêu, danh vọng và số phận. Sợi dây tình cảm tưởng chừng bền chặt nhưng lại mong manh trước thử thách của lòng người.', 120, 'Nguyễn Văn Khánh', 'assets/images/dut-day-to-chung-poster.jpg', 'Đã kết thúc', '2025-08-01 00:00:00', '2025-08-01 00:00:00'),
(9, 'Gánh Cỏ Sông Hàn', 'Lấy bối cảnh miền Trung những năm sau chiến tranh, vở kịch khắc họa số phận những con người mưu sinh bên bến sông Hàn, với tình người chan chứa giữa cuộc đời đầy nhọc nhằn.', 110, 'Trần Thị Mai', 'assets/images/ganh-co-poster.jpg', 'Đang chiếu', '2025-08-01 00:00:00', '2025-08-01 00:00:00'),
(10, 'Làng Song Sinh', 'Một ngôi làng kỳ bí nơi những cặp song sinh liên tục chào đời. Bí mật phía sau sự trùng hợp ấy dần hé lộ, để rồi đẩy người xem vào những tình huống ly kỳ và ám ảnh.', 100, 'Lê Hoàng Nam', 'assets/images/lang-song-sinh-poster.jpg', 'Đang chiếu', '2025-08-01 00:00:00', '2025-08-01 00:00:00'),
(11, 'Lôi Vũ', 'Một trong những vở kịch nổi tiếng nhất thế kỷ XX, “Lôi Vũ” phơi bày những mâu thuẫn giai cấp, đạo đức và gia đình trong xã hội cũ. Vở diễn mang đến sự lay động mạnh mẽ và dư âm lâu dài.', 140, 'Phạm Quang Dũng', 'assets/images/loi-vu.jpg', 'Đang chiếu', '2025-08-01 00:00:00', '2025-08-01 00:00:00'),
(12, 'Ngôi Nhà Trong Mây', 'Một câu chuyện thơ mộng về tình yêu và khát vọng sống, nơi con người tìm đến “ngôi nhà trong mây” để trốn chạy thực tại. Nhưng rồi họ nhận ra: hạnh phúc thật sự chỉ đến khi dám đối diện với chính mình.', 104, 'Vũ Thảo My', 'assets/images/ngoi-nha-trong-may-poster.jpg', 'Đang chiếu', '2025-08-01 00:00:00', '2025-08-01 00:00:00'),
(13, 'Tấm Cám Đại Chiến', 'Phiên bản hiện đại, vui nhộn và đầy sáng tạo của truyện cổ tích “Tấm Cám”. Với yếu tố gây cười, châm biếm và bất ngờ, vở diễn mang đến những phút giây giải trí thú vị cho cả gia đình.', 95, 'Hoàng Anh Tú', 'assets/images/tam-cam-poster.jpg', 'Đang chiếu', '2025-08-01 00:00:00', '2025-11-04 13:41:51'),
(14, 'Má ơi út dìa', 'Câu chuyện cảm động về tình mẫu tử và nỗi day dứt của người con xa quê. Những ký ức, những tiếng gọi “Má ơi” trở thành sợi dây kết nối quá khứ và hiện tại.', 110, 'Nguyễn Thị Thanh Hương', 'assets/images/ma-oi-ut-dia-poster.png', 'Sắp chiếu', '2025-11-04 12:37:19', '2025-11-04 12:40:49'),
(15, 'Tía ơi má dìa', 'Một vở kịch hài – tình cảm về những hiểu lầm, giận hờn và yêu thương trong một gia đình miền Tây. Tiếng cười và nước mắt đan xen tạo nên cảm xúc sâu lắng.', 100, 'Trần Hoài Phong', 'assets/images/tia-oi-ma-dia-poster.jpg', 'Sắp chiếu', '2025-11-04 12:40:24', '2025-11-04 12:40:24'),
(16, 'Đức Thượng Công Tả Quân Lê Văn Duyệt', 'Tái hiện hình tượng vị danh tướng Lê Văn Duyệt – người để lại dấu ấn sâu đậm trong lịch sử và lòng dân Nam Bộ. Một vở diễn lịch sử trang trọng, đầy khí phách.', 130, 'Phạm Hữu Tấn', 'assets/images/duc-thuong-cong-ta-quan-le-van-duyet-poster.jpg', 'Sắp chiếu', '2025-11-04 12:42:26', '2025-11-04 12:42:26'),
(17, 'Chuyến Đò Định Mệnh', 'Một câu chuyện đầy kịch tính xoay quanh chuyến đò cuối cùng của đời người lái đò, nơi tình yêu, tội lỗi và sự tha thứ gặp nhau trong một đêm giông bão.', 115, 'Vũ Ngọc Dũng', 'assets/images/chuyen-do-dinh-menh-poster.jpg', 'Đang chiếu', '2025-11-04 12:43:35', '2025-11-04 13:43:57'),
(18, 'Một Ngày Làm Vua', 'Vở hài kịch xã hội châm biếm về một người bình thường bỗng được trao quyền lực. Từ đó, những tình huống oái oăm, dở khóc dở cười liên tục xảy ra.', 100, 'Nguyễn Hoàng Anh', 'assets/images/mot-ngay-lam-vua-poster.jpg', 'Đang chiếu', '2025-11-04 12:44:58', '2025-11-04 13:08:55'),
(19, 'Xóm Vịt Trời', 'Một góc nhìn nhân văn và hài hước về cuộc sống mưu sinh của những người lao động nghèo trong một xóm nhỏ ven sông. Dù khốn khó, họ vẫn giữ niềm tin và tình người.', 105, 'Lê Thị Phương Loan', 'assets/images/xom-vit-troi-poster.jpg', 'Đang chiếu', '2025-11-04 12:46:05', '2025-11-04 13:11:48'),
(20, 'Những con ma nhà hát', '“Những Con Ma Nhà Hát” là một câu chuyện rùng rợn nhưng cũng đầy tính châm biếm, xoay quanh những hiện tượng kỳ bí xảy ra tại một nhà hát cũ sắp bị phá bỏ. Khi đoàn kịch mới đến tập luyện, những bóng ma của các diễn viên quá cố bắt đầu xuất hiện, đưa người xem vào hành trình giằng co giữa nghệ thuật, danh vọng và quá khứ bị lãng quên.', 115, 'Nguyễn Khánh Trung', 'assets/images/nhung-con-ma-poster.jpg', 'Sắp chiếu', '2025-11-04 13:19:55', '2025-11-04 13:19:55');

-- --------------------------------------------------------

--
-- Table structure for table `show_genres`
--

CREATE TABLE `show_genres` (
  `show_id` int(11) NOT NULL,
  `genre_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `show_genres`
--

INSERT INTO `show_genres` (`show_id`, `genre_id`) VALUES
(8, 6),
(8, 8),
(9, 8),
(9, 9),
(9, 10),
(10, 8),
(10, 13),
(11, 6),
(11, 8),
(11, 15),
(12, 11),
(12, 12),
(13, 7),
(13, 14),
(14, 6),
(14, 10),
(14, 16),
(15, 7),
(15, 10),
(15, 16),
(16, 15),
(16, 17),
(16, 18),
(17, 6),
(17, 8),
(17, 13),
(18, 7),
(18, 18),
(18, 19),
(19, 8),
(19, 9),
(19, 10),
(20, 8),
(20, 12),
(20, 13);

-- --------------------------------------------------------

--
-- Table structure for table `theaters`
--

CREATE TABLE `theaters` (
  `theater_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_seats` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Chờ xử lý','Đã hoạt động') NOT NULL DEFAULT 'Chờ xử lý'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `theaters`
--

INSERT INTO `theaters` (`theater_id`, `name`, `total_seats`, `created_at`, `status`) VALUES
(1, 'Main Hall', 60, '2025-10-03 16:14:11', 'Đã hoạt động'),
(2, 'Black Box', 24, '2025-10-03 16:14:22', 'Đã hoạt động'),
(3, 'Studio', 30, '2025-10-03 16:14:32', 'Đã hoạt động');

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `ticket_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `seat_id` int(11) NOT NULL,
  `ticket_code` varchar(50) NOT NULL,
  `status` enum('Đang chờ','Hợp lệ','Đã sử dụng','Đã hủy') NOT NULL DEFAULT 'Đang chờ',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `tickets`
--

INSERT INTO `tickets` (`ticket_id`, `booking_id`, `seat_id`, `ticket_code`, `status`, `created_at`) VALUES
(45, 29, 61, 'TICK00045', 'Hợp lệ', '2025-08-26 21:06:55'),
(46, 29, 55, 'TICK00046', 'Hợp lệ', '2025-08-26 21:06:55'),
(47, 30, 103, 'TICK00047', 'Hợp lệ', '2025-09-06 19:25:25'),
(48, 30, 105, 'TICK00048', 'Hợp lệ', '2025-09-06 19:25:25'),
(49, 31, 70, 'TICK00049', 'Hợp lệ', '2025-10-22 17:30:20'),
(50, 31, 63, 'TICK00050', 'Hợp lệ', '2025-10-22 17:30:20'),
(51, 32, 59, 'TICK00051', 'Hợp lệ', '2025-09-24 11:08:10'),
(52, 32, 58, 'TICK00052', 'Hợp lệ', '2025-09-24 11:08:10'),
(53, 33, 75, 'TICK00053', 'Hợp lệ', '2025-08-01 03:53:33'),
(54, 33, 60, 'TICK00054', 'Hợp lệ', '2025-08-01 03:53:33'),
(55, 34, 50, 'TICK00055', 'Hợp lệ', '2025-08-07 18:49:30'),
(56, 34, 6, 'TICK00056', 'Hợp lệ', '2025-08-07 18:49:30'),
(57, 35, 21, 'TICK00057', 'Hợp lệ', '2025-08-10 21:45:46'),
(58, 35, 47, 'TICK00058', 'Hợp lệ', '2025-08-10 21:45:46'),
(59, 36, 56, 'TICK00059', 'Hợp lệ', '2025-10-16 17:00:02'),
(60, 36, 60, 'TICK00060', 'Hợp lệ', '2025-10-16 17:00:02'),
(61, 37, 19, 'TICK00061', 'Hợp lệ', '2025-08-26 18:14:39'),
(62, 37, 29, 'TICK00062', 'Hợp lệ', '2025-08-26 18:14:39'),
(63, 38, 3, 'TICK00063', 'Hợp lệ', '2025-09-03 08:19:29'),
(64, 38, 50, 'TICK00064', 'Hợp lệ', '2025-09-03 08:19:29'),
(65, 39, 102, 'TICK00065', 'Hợp lệ', '2025-10-19 17:42:09'),
(66, 39, 92, 'TICK00066', 'Hợp lệ', '2025-10-19 17:42:09'),
(67, 40, 97, 'TICK00067', 'Hợp lệ', '2025-08-02 05:14:21'),
(68, 40, 93, 'TICK00068', 'Hợp lệ', '2025-08-02 05:14:21'),
(69, 41, 105, 'TICK00069', 'Hợp lệ', '2025-10-06 11:47:10'),
(70, 41, 101, 'TICK00070', 'Hợp lệ', '2025-10-06 11:47:10'),
(71, 42, 75, 'TICK00071', 'Hợp lệ', '2025-09-20 13:06:24'),
(72, 42, 76, 'TICK00072', 'Hợp lệ', '2025-09-20 13:06:24'),
(73, 43, 25, 'TICK00073', 'Hợp lệ', '2025-10-22 06:26:58'),
(74, 43, 34, 'TICK00074', 'Hợp lệ', '2025-10-22 06:26:58'),
(75, 44, 58, 'TICK00075', 'Hợp lệ', '2025-10-28 16:56:57'),
(76, 44, 76, 'TICK00076', 'Hợp lệ', '2025-10-28 16:56:57'),
(77, 45, 44, 'TICK00077', 'Hợp lệ', '2025-09-02 12:22:29'),
(78, 45, 18, 'TICK00078', 'Hợp lệ', '2025-09-02 12:22:29'),
(79, 46, 71, 'TICK00079', 'Hợp lệ', '2025-10-10 04:58:34'),
(80, 46, 66, 'TICK00080', 'Hợp lệ', '2025-10-10 04:58:34'),
(81, 47, 65, 'TICK00081', 'Hợp lệ', '2025-08-02 14:00:03'),
(82, 47, 71, 'TICK00082', 'Hợp lệ', '2025-08-02 14:00:03'),
(83, 48, 38, 'TICK00083', 'Hợp lệ', '2025-08-02 05:53:38'),
(84, 48, 6, 'TICK00084', 'Hợp lệ', '2025-08-02 05:53:38'),
(85, 49, 15, 'TICK00085', 'Hợp lệ', '2025-10-02 00:30:49'),
(86, 49, 26, 'TICK00086', 'Hợp lệ', '2025-10-02 00:30:49'),
(87, 50, 32, 'TICK00087', 'Hợp lệ', '2025-09-22 20:00:29'),
(88, 50, 49, 'TICK00088', 'Hợp lệ', '2025-09-22 20:00:29'),
(89, 51, 13, 'TICK00089', 'Hợp lệ', '2025-09-06 18:10:44'),
(90, 51, 11, 'TICK00090', 'Hợp lệ', '2025-09-06 18:10:44'),
(91, 52, 54, 'TICK00091', 'Hợp lệ', '2025-09-14 07:21:00'),
(92, 52, 53, 'TICK00092', 'Hợp lệ', '2025-09-14 07:21:00'),
(93, 53, 2, 'TICK00093', 'Hợp lệ', '2025-10-06 12:05:14'),
(94, 53, 18, 'TICK00094', 'Hợp lệ', '2025-10-06 12:05:14'),
(95, 54, 90, 'TICK00095', 'Hợp lệ', '2025-08-01 00:49:40'),
(96, 54, 97, 'TICK00096', 'Hợp lệ', '2025-08-01 00:49:40'),
(97, 55, 96, 'TICK00097', 'Hợp lệ', '2025-10-08 15:33:06'),
(98, 55, 79, 'TICK00098', 'Hợp lệ', '2025-10-08 15:33:06'),
(99, 56, 6, 'TICK00099', 'Hợp lệ', '2025-10-17 06:11:45'),
(100, 56, 28, 'TICK00100', 'Hợp lệ', '2025-10-17 06:11:45'),
(101, 57, 66, 'TICK00101', 'Hợp lệ', '2025-08-03 19:53:37'),
(102, 57, 55, 'TICK00102', 'Hợp lệ', '2025-08-03 19:53:37'),
(103, 58, 62, 'TICK00103', 'Hợp lệ', '2025-10-12 21:31:32'),
(104, 58, 70, 'TICK00104', 'Hợp lệ', '2025-10-12 21:31:32'),
(105, 59, 65, 'TICK00105', 'Hợp lệ', '2025-08-03 07:26:23'),
(106, 59, 69, 'TICK00106', 'Hợp lệ', '2025-08-03 07:26:23'),
(107, 60, 74, 'TICK00107', 'Hợp lệ', '2025-08-07 05:42:18'),
(108, 60, 59, 'TICK00108', 'Hợp lệ', '2025-08-07 05:42:18'),
(109, 61, 64, 'TICK00109', 'Hợp lệ', '2025-10-12 05:54:40'),
(110, 61, 74, 'TICK00110', 'Hợp lệ', '2025-10-12 05:54:40'),
(111, 62, 43, 'TICK00111', 'Hợp lệ', '2025-09-22 04:33:01'),
(112, 62, 4, 'TICK00112', 'Hợp lệ', '2025-09-22 04:33:01'),
(113, 63, 10, 'TICK00113', 'Hợp lệ', '2025-10-15 08:31:15'),
(114, 63, 43, 'TICK00114', 'Hợp lệ', '2025-10-15 08:31:15'),
(115, 64, 88, 'TICK00115', 'Hợp lệ', '2025-09-20 04:56:18'),
(116, 64, 81, 'TICK00116', 'Hợp lệ', '2025-09-20 04:56:18'),
(117, 65, 99, 'TICK00117', 'Hợp lệ', '2025-09-11 23:03:27'),
(118, 65, 83, 'TICK00118', 'Hợp lệ', '2025-09-11 23:03:27'),
(119, 66, 63, 'TICK00119', 'Hợp lệ', '2025-09-03 22:29:11'),
(120, 66, 73, 'TICK00120', 'Hợp lệ', '2025-09-03 22:29:11'),
(121, 67, 70, 'TICK00121', 'Hợp lệ', '2025-10-16 19:25:04'),
(122, 67, 64, 'TICK00122', 'Hợp lệ', '2025-10-16 19:25:04'),
(123, 68, 11, 'TICK00123', 'Hợp lệ', '2025-10-24 14:29:48'),
(124, 68, 37, 'TICK00124', 'Hợp lệ', '2025-10-24 14:29:48'),
(125, 69, 94, 'TICK00125', 'Hợp lệ', '2025-09-03 08:34:41'),
(126, 69, 95, 'TICK00126', 'Hợp lệ', '2025-09-03 08:34:41'),
(127, 70, 80, 'TICK00127', 'Hợp lệ', '2025-08-22 20:03:53'),
(128, 70, 97, 'TICK00128', 'Hợp lệ', '2025-08-22 20:03:53'),
(129, 71, 22, 'TICK00129', 'Hợp lệ', '2025-10-30 06:37:41'),
(130, 71, 6, 'TICK00130', 'Hợp lệ', '2025-10-30 06:37:41'),
(131, 72, 85, 'TICK00131', 'Hợp lệ', '2025-08-26 16:26:34'),
(132, 72, 84, 'TICK00132', 'Hợp lệ', '2025-08-26 16:26:34'),
(133, 73, 78, 'TICK00133', 'Hợp lệ', '2025-11-01 19:56:10'),
(134, 73, 95, 'TICK00134', 'Hợp lệ', '2025-11-01 19:56:10'),
(135, 74, 36, 'TICK00135', 'Hợp lệ', '2025-08-19 20:25:59'),
(136, 74, 15, 'TICK00136', 'Hợp lệ', '2025-08-19 20:25:59'),
(137, 75, 96, 'TICK00137', 'Hợp lệ', '2025-09-28 06:20:19'),
(138, 75, 84, 'TICK00138', 'Hợp lệ', '2025-09-28 06:20:19'),
(139, 76, 55, 'TICK00139', 'Hợp lệ', '2025-10-30 05:35:56'),
(140, 76, 65, 'TICK00140', 'Hợp lệ', '2025-10-30 05:35:56'),
(141, 77, 58, 'TICK00141', 'Hợp lệ', '2025-09-17 19:06:42'),
(142, 77, 71, 'TICK00142', 'Hợp lệ', '2025-09-17 19:06:42'),
(143, 78, 77, 'TICK00143', 'Hợp lệ', '2025-09-03 01:28:23'),
(144, 78, 87, 'TICK00144', 'Hợp lệ', '2025-09-03 01:28:23'),
(145, 79, 89, 'TICK00145', 'Hợp lệ', '2025-09-29 04:49:21'),
(146, 79, 99, 'TICK00146', 'Hợp lệ', '2025-09-29 04:49:21'),
(147, 80, 53, 'TICK00147', 'Hợp lệ', '2025-10-07 13:17:23'),
(148, 80, 70, 'TICK00148', 'Hợp lệ', '2025-10-07 13:17:23'),
(149, 81, 9, 'TICK00149', 'Hợp lệ', '2025-09-05 00:21:55'),
(150, 81, 47, 'TICK00150', 'Hợp lệ', '2025-09-05 00:21:55'),
(151, 82, 68, 'TICK00151', 'Hợp lệ', '2025-09-04 19:45:46'),
(152, 82, 72, 'TICK00152', 'Hợp lệ', '2025-09-04 19:45:46'),
(153, 83, 33, 'TICK00153', 'Hợp lệ', '2025-10-19 15:51:09'),
(154, 83, 26, 'TICK00154', 'Hợp lệ', '2025-10-19 15:51:09'),
(155, 84, 89, 'TICK00155', 'Hợp lệ', '2025-10-21 17:07:45'),
(156, 84, 102, 'TICK00156', 'Hợp lệ', '2025-10-21 17:07:45'),
(157, 85, 27, 'TICK00157', 'Hợp lệ', '2025-10-20 12:32:36'),
(158, 85, 36, 'TICK00158', 'Hợp lệ', '2025-10-20 12:32:36'),
(159, 86, 106, 'TICK00159', 'Hợp lệ', '2025-09-05 18:42:26'),
(160, 86, 95, 'TICK00160', 'Hợp lệ', '2025-09-05 18:42:26'),
(161, 87, 21, 'TICK00161', 'Hợp lệ', '2025-09-18 19:40:40'),
(162, 87, 10, 'TICK00162', 'Hợp lệ', '2025-09-18 19:40:40'),
(163, 88, 87, 'TICK00163', 'Hợp lệ', '2025-08-06 00:14:45'),
(164, 88, 78, 'TICK00164', 'Hợp lệ', '2025-08-06 00:14:45'),
(165, 89, 28, 'TICK00165', 'Hợp lệ', '2025-09-15 04:48:20'),
(166, 89, 23, 'TICK00166', 'Hợp lệ', '2025-09-15 04:48:20'),
(167, 90, 52, 'TICK00167', 'Hợp lệ', '2025-10-02 19:14:12'),
(168, 90, 25, 'TICK00168', 'Hợp lệ', '2025-10-02 19:14:12'),
(169, 91, 63, 'TICK00169', 'Hợp lệ', '2025-09-24 15:20:27'),
(170, 91, 74, 'TICK00170', 'Hợp lệ', '2025-09-24 15:20:27'),
(171, 92, 29, 'TICK00171', 'Hợp lệ', '2025-09-18 15:24:17'),
(172, 92, 18, 'TICK00172', 'Hợp lệ', '2025-09-18 15:24:17'),
(173, 93, 21, 'TICK00173', 'Hợp lệ', '2025-08-18 16:34:13'),
(174, 93, 30, 'TICK00174', 'Hợp lệ', '2025-08-18 16:34:13'),
(175, 94, 77, 'TICK00175', 'Hợp lệ', '2025-10-11 05:47:26'),
(176, 94, 92, 'TICK00176', 'Hợp lệ', '2025-10-11 05:47:26'),
(177, 95, 69, 'TICK00177', 'Hợp lệ', '2025-09-15 15:53:49'),
(178, 95, 71, 'TICK00178', 'Hợp lệ', '2025-09-15 15:53:49'),
(179, 96, 56, 'TICK00179', 'Hợp lệ', '2025-10-26 13:25:17'),
(180, 96, 60, 'TICK00180', 'Hợp lệ', '2025-10-26 13:25:17'),
(181, 97, 72, 'TICK00181', 'Hợp lệ', '2025-09-18 22:40:33'),
(182, 97, 54, 'TICK00182', 'Hợp lệ', '2025-09-18 22:40:33'),
(183, 98, 105, 'TICK00183', 'Hợp lệ', '2025-09-01 03:06:23'),
(184, 98, 88, 'TICK00184', 'Hợp lệ', '2025-09-01 03:06:23'),
(185, 99, 90, 'TICK00185', 'Hợp lệ', '2025-08-28 16:18:48'),
(186, 99, 82, 'TICK00186', 'Hợp lệ', '2025-08-28 16:18:48'),
(187, 100, 91, 'TICK00187', 'Hợp lệ', '2025-08-05 05:08:38'),
(188, 100, 88, 'TICK00188', 'Hợp lệ', '2025-08-05 05:08:38'),
(189, 101, 77, 'TICK00189', 'Hợp lệ', '2025-08-20 05:56:04'),
(190, 101, 96, 'TICK00190', 'Hợp lệ', '2025-08-20 05:56:04'),
(191, 102, 68, 'TICK00191', 'Hợp lệ', '2025-10-14 03:54:54'),
(192, 102, 76, 'TICK00192', 'Hợp lệ', '2025-10-14 03:54:54'),
(193, 103, 86, 'TICK00193', 'Hợp lệ', '2025-10-24 01:09:53'),
(194, 103, 89, 'TICK00194', 'Hợp lệ', '2025-10-24 01:09:53'),
(195, 104, 58, 'TICK00195', 'Hợp lệ', '2025-08-11 00:32:19'),
(196, 104, 59, 'TICK00196', 'Hợp lệ', '2025-08-11 00:32:19'),
(197, 105, 73, 'TICK00197', 'Hợp lệ', '2025-10-18 09:19:08'),
(198, 105, 69, 'TICK00198', 'Hợp lệ', '2025-10-18 09:19:08'),
(199, 106, 42, 'TICK00199', 'Hợp lệ', '2025-09-09 11:00:38'),
(200, 106, 7, 'TICK00200', 'Hợp lệ', '2025-09-09 11:00:38'),
(201, 107, 56, 'TICK00201', 'Hợp lệ', '2025-10-13 22:52:51'),
(202, 107, 55, 'TICK00202', 'Hợp lệ', '2025-10-13 22:52:51'),
(203, 108, 69, 'TICK00203', 'Hợp lệ', '2025-08-15 22:29:28'),
(204, 108, 73, 'TICK00204', 'Hợp lệ', '2025-08-15 22:29:28'),
(205, 109, 10, 'TICK00205', 'Hợp lệ', '2025-08-24 10:17:47'),
(206, 109, 16, 'TICK00206', 'Hợp lệ', '2025-08-24 10:17:47'),
(207, 110, 62, 'TICK00207', 'Hợp lệ', '2025-09-09 04:34:27'),
(208, 110, 75, 'TICK00208', 'Hợp lệ', '2025-09-09 04:34:27'),
(209, 111, 1, 'TICK00209', 'Hợp lệ', '2025-09-23 02:26:01'),
(210, 111, 31, 'TICK00210', 'Hợp lệ', '2025-09-23 02:26:01'),
(211, 112, 4, 'TICK00211', 'Hợp lệ', '2025-10-07 20:22:51'),
(212, 112, 18, 'TICK00212', 'Hợp lệ', '2025-10-07 20:22:51'),
(213, 113, 13, 'TICK00213', 'Hợp lệ', '2025-08-28 10:23:04'),
(214, 113, 21, 'TICK00214', 'Hợp lệ', '2025-08-28 10:23:04'),
(215, 114, 66, 'TICK00215', 'Hợp lệ', '2025-10-12 16:30:44'),
(216, 114, 57, 'TICK00216', 'Hợp lệ', '2025-10-12 16:30:44'),
(217, 115, 10, 'TICK00217', 'Hợp lệ', '2025-09-10 05:21:42'),
(218, 115, 32, 'TICK00218', 'Hợp lệ', '2025-09-10 05:21:42'),
(219, 116, 70, 'TICK00219', 'Hợp lệ', '2025-09-07 22:20:06'),
(220, 116, 76, 'TICK00220', 'Hợp lệ', '2025-09-07 22:20:06'),
(221, 117, 59, 'TICK00221', 'Hợp lệ', '2025-10-27 18:47:04'),
(222, 117, 64, 'TICK00222', 'Hợp lệ', '2025-10-27 18:47:04'),
(223, 118, 4, 'TICK00223', 'Hợp lệ', '2025-09-16 04:20:04'),
(224, 118, 25, 'TICK00224', 'Hợp lệ', '2025-09-16 04:20:04'),
(225, 119, 84, 'TICK00225', 'Hợp lệ', '2025-10-29 22:50:08'),
(226, 119, 82, 'TICK00226', 'Hợp lệ', '2025-10-29 22:50:08'),
(227, 120, 40, 'TICK00227', 'Hợp lệ', '2025-09-13 04:59:52'),
(228, 120, 44, 'TICK00228', 'Hợp lệ', '2025-09-13 04:59:52'),
(229, 121, 96, 'TICK00229', 'Hợp lệ', '2025-08-21 17:11:46'),
(230, 121, 91, 'TICK00230', 'Hợp lệ', '2025-08-21 17:11:46'),
(231, 122, 80, 'TICK00231', 'Hợp lệ', '2025-09-27 13:22:41'),
(232, 122, 84, 'TICK00232', 'Hợp lệ', '2025-09-27 13:22:41'),
(233, 123, 56, 'TICK00233', 'Hợp lệ', '2025-11-02 03:09:28'),
(234, 123, 72, 'TICK00234', 'Hợp lệ', '2025-11-02 03:09:28'),
(235, 124, 92, 'TICK00235', 'Hợp lệ', '2025-10-25 02:00:11'),
(236, 124, 93, 'TICK00236', 'Hợp lệ', '2025-10-25 02:00:11'),
(237, 125, 14, 'TICK00237', 'Hợp lệ', '2025-10-16 14:06:56'),
(238, 125, 1, 'TICK00238', 'Hợp lệ', '2025-10-16 14:06:56'),
(239, 126, 26, 'TICK00239', 'Hợp lệ', '2025-08-15 05:09:29'),
(240, 126, 3, 'TICK00240', 'Hợp lệ', '2025-08-15 05:09:29'),
(241, 127, 68, 'TICK00241', 'Hợp lệ', '2025-10-04 04:57:13'),
(242, 127, 67, 'TICK00242', 'Hợp lệ', '2025-10-04 04:57:13'),
(243, 128, 79, 'TICK00243', 'Hợp lệ', '2025-08-12 02:32:23'),
(244, 128, 106, 'TICK00244', 'Hợp lệ', '2025-08-12 02:32:23');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `account_name` varchar(100) DEFAULT NULL,
  `user_type` enum('Khách hàng','Nhân viên') NOT NULL DEFAULT 'Khách hàng',
  `status` enum('hoạt động','khóa') NOT NULL DEFAULT 'hoạt động',
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `otp_code` varchar(10) DEFAULT NULL,
  `otp_expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `email`, `password`, `account_name`, `user_type`, `status`, `is_verified`, `otp_code`, `otp_expires_at`) VALUES
(1, 'staff@example.com', '$2y$10$/dfBZuihwFxTAjS6YFHUE.y/KIXoHhFjishdfbxVykWD87V4RCQKi', 'thanhngoc', 'Nhân viên', 'hoạt động', 1, NULL, NULL),
(2, 'mytrangle1509@gmail.com', '$2y$10$0doy81SVgcSvSwMD/VBK2OGfKf6yIVFEnCmzZYR15PjSq/yGz8p.C', 'trangle', 'Khách hàng', 'hoạt động', 1, NULL, NULL),
(3, 'hoaithunguyen066@gmail.com', '$2y$10$6pjx5wsk.tW3icop/RZjWu0nMUqs61OhljS8NttNHqOxG2yP/sZdK', 'hoaithu', 'Khách hàng', 'hoạt động', 1, NULL, NULL),
(4, 'nguyenthithuytrang2020bd@gmail.com', '$2y$10$qEOSBdHhLThH6gneJ2tki.YIdoFCGM7wsBScXYAZ7sgZpDUIuLKSW', 'thuytrang', 'Khách hàng', 'hoạt động', 1, NULL, NULL),
(6, 'trangltmt1509@gmail.com', '$2y$10$MTCttS.vzYX2xjZlEV7H9uEwBtOHw4LkrCtgxEmGLTQzABBKTt2sK', 'thuylinh', 'Nhân viên', 'khóa', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_detail`
--

CREATE TABLE `user_detail` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `date_of_birth` date NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `user_detail`
--

INSERT INTO `user_detail` (`user_id`, `full_name`, `date_of_birth`, `address`, `phone`) VALUES
(1, 'Dương Thanh Ngọc', '2005-08-12', NULL, NULL),
(2, 'Lê Thị Mỹ Trang', '2005-09-15', NULL, NULL),
(3, 'Nguyễn Hoài Thu', '2005-08-21', NULL, NULL),
(4, 'Nguyễn Thị Thùy Trang', '2005-03-12', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `user_idx` (`user_id`),
  ADD KEY `performance_idx` (`performance_id`);

--
-- Indexes for table `genres`
--
ALTER TABLE `genres`
  ADD PRIMARY KEY (`genre_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD UNIQUE KEY `unique_txn_ref` (`vnp_txn_ref`),
  ADD KEY `payment_booking_idx` (`booking_id`);

--
-- Indexes for table `performances`
--
ALTER TABLE `performances`
  ADD PRIMARY KEY (`performance_id`),
  ADD KEY `show_idx` (`show_id`),
  ADD KEY `theater_idx` (`theater_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `review_show_idx` (`show_id`),
  ADD KEY `review_user_idx` (`user_id`);

--
-- Indexes for table `seats`
--
ALTER TABLE `seats`
  ADD PRIMARY KEY (`seat_id`),
  ADD KEY `theater_idx2` (`theater_id`),
  ADD KEY `category_idx2` (`category_id`);

--
-- Indexes for table `seat_categories`
--
ALTER TABLE `seat_categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `seat_performance`
--
ALTER TABLE `seat_performance`
  ADD PRIMARY KEY (`seat_id`,`performance_id`),
  ADD KEY `sp_performance_idx` (`performance_id`),
  ADD KEY `idx_seat_id` (`seat_id`);

--
-- Indexes for table `shows`
--
ALTER TABLE `shows`
  ADD PRIMARY KEY (`show_id`);

--
-- Indexes for table `show_genres`
--
ALTER TABLE `show_genres`
  ADD PRIMARY KEY (`show_id`,`genre_id`),
  ADD KEY `show_genres_show_idx` (`show_id`),
  ADD KEY `show_genres_genre_idx` (`genre_id`);

--
-- Indexes for table `theaters`
--
ALTER TABLE `theaters`
  ADD PRIMARY KEY (`theater_id`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`ticket_id`),
  ADD UNIQUE KEY `unique_ticket_code` (`ticket_code`),
  ADD KEY `ticket_booking_idx` (`booking_id`),
  ADD KEY `ticket_seat_idx` (`seat_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `unique_email` (`email`);

--
-- Indexes for table `user_detail`
--
ALTER TABLE `user_detail`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `user_id_idx` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=129;

--
-- AUTO_INCREMENT for table `genres`
--
ALTER TABLE `genres`
  MODIFY `genre_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=124;

--
-- AUTO_INCREMENT for table `performances`
--
ALTER TABLE `performances`
  MODIFY `performance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `seats`
--
ALTER TABLE `seats`
  MODIFY `seat_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=207;

--
-- AUTO_INCREMENT for table `seat_categories`
--
ALTER TABLE `seat_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `shows`
--
ALTER TABLE `shows`
  MODIFY `show_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `theaters`
--
ALTER TABLE `theaters`
  MODIFY `theater_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `ticket_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=245;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `performance_idx` FOREIGN KEY (`performance_id`) REFERENCES `performances` (`performance_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `user_idx` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payment_booking_idx` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `payments_ibfk_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `performances`
--
ALTER TABLE `performances`
  ADD CONSTRAINT `show_idx` FOREIGN KEY (`show_id`) REFERENCES `shows` (`show_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `theater_idx` FOREIGN KEY (`theater_id`) REFERENCES `theaters` (`theater_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `review_show_idx` FOREIGN KEY (`show_id`) REFERENCES `shows` (`show_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `review_user_idx` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `seats`
--
ALTER TABLE `seats`
  ADD CONSTRAINT `category_idx2` FOREIGN KEY (`category_id`) REFERENCES `seat_categories` (`category_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `theater_idx2` FOREIGN KEY (`theater_id`) REFERENCES `theaters` (`theater_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `seat_performance`
--
ALTER TABLE `seat_performance`
  ADD CONSTRAINT `fk_sp_performance` FOREIGN KEY (`performance_id`) REFERENCES `performances` (`performance_id`),
  ADD CONSTRAINT `fk_sp_seat` FOREIGN KEY (`seat_id`) REFERENCES `seats` (`seat_id`),
  ADD CONSTRAINT `idx_seat_id` FOREIGN KEY (`seat_id`) REFERENCES `seats` (`seat_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `sp_performance_idx` FOREIGN KEY (`performance_id`) REFERENCES `performances` (`performance_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `show_genres`
--
ALTER TABLE `show_genres`
  ADD CONSTRAINT `show_genres_genre_idx` FOREIGN KEY (`genre_id`) REFERENCES `genres` (`genre_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `show_genres_show_idx` FOREIGN KEY (`show_id`) REFERENCES `shows` (`show_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `ticket_booking_idx` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `ticket_seat_idx` FOREIGN KEY (`seat_id`) REFERENCES `seats` (`seat_id`) ON UPDATE CASCADE;

--
-- Constraints for table `user_detail`
--
ALTER TABLE `user_detail`
  ADD CONSTRAINT `user_detail_ibfk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
