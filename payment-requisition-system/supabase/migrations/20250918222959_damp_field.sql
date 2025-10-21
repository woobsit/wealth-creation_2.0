-- Payment Requisition System Database Schema
-- Created: 2025-01-09

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- Create database
CREATE DATABASE IF NOT EXISTS `requisition_system` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `requisition_system`;

-- Users table
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_level` int(5) UNSIGNED NOT NULL DEFAULT 1,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `has_roles` text NOT NULL DEFAULT 'user',
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `department` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Staff table
CREATE TABLE `staffs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `user_level` int(11) NOT NULL DEFAULT 1,
  `full_name` varchar(100) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `other_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `home_address` varchar(200) DEFAULT NULL,
  `phone_no` varchar(15) DEFAULT NULL,
  `cug_phone` varchar(15) DEFAULT NULL,
  `other_email` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `sex` enum('Male','Female') DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `qualification` varchar(100) DEFAULT NULL,
  `date_of_employment` date DEFAULT NULL,
  `department` varchar(50) NOT NULL,
  `present_grade` varchar(50) DEFAULT NULL,
  `last_promotion_date` date DEFAULT NULL,
  `hmo` varchar(50) DEFAULT NULL,
  `pension_provider` varchar(50) DEFAULT NULL,
  `rsa_no` varchar(20) DEFAULT NULL,
  `bank_name` varchar(50) DEFAULT NULL,
  `bank_acct_name` varchar(100) DEFAULT NULL,
  `bank_acct_no` varchar(20) DEFAULT NULL,
  `gross_salary` decimal(15,2) DEFAULT 0.00,
  `payee_tax` decimal(15,2) DEFAULT 0.00,
  `salary_deduction` decimal(15,2) DEFAULT 0.00,
  `pension_deduction` decimal(15,2) DEFAULT 0.00,
  `net_salary` decimal(15,2) DEFAULT 0.00,
  `next_of_kin` varchar(100) DEFAULT NULL,
  `kin_phone_no` varchar(15) DEFAULT NULL,
  `kin_home_address` varchar(200) DEFAULT NULL,
  `level` varchar(20) DEFAULT NULL,
  `update_status` varchar(50) DEFAULT 'pending',
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `inputter_status` varchar(20) DEFAULT 'pending',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `staffs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Requisitions table
CREATE TABLE `requisitions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reference_number` varchar(50) NOT NULL UNIQUE,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'NGN',
  `department` varchar(100) NOT NULL,
  `category` varchar(100) NOT NULL,
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `status` enum('draft','pending','approved','rejected','completed') NOT NULL DEFAULT 'draft',
  `justification` text DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `current_approval_level` int(11) NOT NULL DEFAULT 1,
  `final_approval_level` int(11) NOT NULL DEFAULT 3,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference_number` (`reference_number`),
  KEY `created_by` (`created_by`),
  KEY `idx_status` (`status`),
  KEY `idx_department` (`department`),
  CONSTRAINT `requisitions_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Approval levels table
CREATE TABLE `approval_levels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `level` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `user_level_required` int(11) NOT NULL,
  `department_specific` tinyint(1) NOT NULL DEFAULT 0,
  `is_final` tinyint(1) NOT NULL DEFAULT 0,
  `order_sequence` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Approval steps table
CREATE TABLE `approval_steps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requisition_id` int(11) NOT NULL,
  `approval_level` int(11) NOT NULL,
  `approver_id` int(11) DEFAULT NULL,
  `approver_name` varchar(150) DEFAULT NULL,
  `approver_title` varchar(100) DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `comments` text DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `requisition_id` (`requisition_id`),
  KEY `approver_id` (`approver_id`),
  CONSTRAINT `approval_steps_ibfk_1` FOREIGN KEY (`requisition_id`) REFERENCES `requisitions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `approval_steps_ibfk_2` FOREIGN KEY (`approver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Attachments table
CREATE TABLE `requisition_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requisition_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `requisition_id` (`requisition_id`),
  KEY `uploaded_by` (`uploaded_by`),
  CONSTRAINT `requisition_attachments_ibfk_1` FOREIGN KEY (`requisition_id`) REFERENCES `requisitions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `requisition_attachments_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications table
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `data` json DEFAULT NULL,
  `read_status` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_read_status` (`read_status`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default approval levels
INSERT INTO `approval_levels` (`level`, `title`, `description`, `user_level_required`, `department_specific`, `is_final`, `order_sequence`) VALUES
(1, 'Department Head', 'Department head approval', 3, 1, 0, 1),
(2, 'Finance Manager', 'Finance department review', 4, 0, 0, 2),
(3, 'Chief Executive', 'Final executive approval', 5, 0, 1, 3);

-- Insert default admin user (password: admin123)
INSERT INTO `users` (`user_level`, `full_name`, `email`, `password`, `has_roles`, `status`, `department`) VALUES
(5, 'System Administrator', 'admin@requisition.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin,approver', 'active', 'Administration');

COMMIT;