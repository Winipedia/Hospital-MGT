-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 26, 2023 at 02:38 PM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hospital`
--

CREATE DATABASE IF NOT EXISTS `hospital` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `hospital`;

-- --------------------------------------------------------

--
-- Table structure for table `gender`
--

CREATE TABLE `gender` (
  `gender_id` int(11) NOT NULL AUTO_INCREMENT,
  `gender_name` varchar(20) NOT NULL,
  PRIMARY KEY (`gender_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gender`
--

INSERT INTO `gender` (`gender_id`, `gender_name`) VALUES
(1, 'Male'),
(2, 'Female'),
(3, 'Other');

-- --------------------------------------------------------

--
-- Table structure for table `address`
--

CREATE TABLE `address` (
  `address_id` int(11) NOT NULL AUTO_INCREMENT,
  `street` varchar(200) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `postcode` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`address_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `address`
--

INSERT INTO `address` (`address_id`, `street`, `city`, `postcode`) VALUES
(1, '45 The Barnum', 'Nottingham', 'NG2 6TY'),
(2, '1 Chatsworth Avenue, Carlton', 'Nottingham', 'NG4'),
(3, '102 Leeming Lane South, Mansfield Woodhouse', 'Mansfield', NULL),
(4, '16 Lenton Boulevard, Lenton', 'Nottingham', 'NG7 2ES'),
(5, '3 Rolleston Drive', 'Nottingham', NULL),
(6, '44 Dunlop Avenue, Lenton', 'Nottingham', 'NG1 5AW'),
(7, '55 Wishford Avenue, Lenton', 'Nottingham', NULL),
(8, '47 Derby Road', 'Nottingham', 'NG1 5AW'),
(9, 'QMC Admin Office', 'Nottingham', 'NG7 2UH'),
(10, '668 Watnall Road, Hucknall', 'Nottingham', 'NG15'),
(11, '1 Pelham Crescent, Beeston', 'Nottingham', 'NG9'),
(12, '4 Lake Street', 'Nottingham', 'NG7 4BT'),
(13, '52 Chatsworth Avenue, Carlton', 'Nottingham', 'NG4'),
(14, 'Lake Street', 'Nottingham', 'NG7 4BT'),
(15, '100 Hawton Crescent, Wollaton', 'Nottingham', 'NG8 1BZ'),
(16, '22 Hawton Crescent, Wollaton', 'Nottingham', 'NG8 1BZ'),
(17, 'Floor A Room 234 Derby Rd, Lenton', 'Nottingham', 'NG7 2UH'),
(18, 'Queen\'s Medical Centre, Derby Rd, Lenton', 'Nottingham', 'NG7 2UG'),
(19, 'Floor C Room 234 Derby Rd, Lenton', 'Nottingham', 'NG7 2UH'),
(20, 'Floor A Room 32 Derby Rd, Lenton', 'Nottingham', 'NG7 2UH'),
(21, 'Floor A, Derby Rd, Lenton', 'Nottingham', 'NG7 2UH'),
(22, 'Floor B, Derby Rd, Lenton', 'Nottingham', 'NG7 2UH'),
(23, 'Floor C, Derby Rd, Lenton', 'Nottingham', 'NG7 2UH'),
(24, 'Ground Floor, Derby Rd, Lenton', 'Nottingham', 'NG7 2UH');

-- --------------------------------------------------------

--
-- Table structure for table `specialisation`
--

CREATE TABLE `specialisation` (
  `specialisation_id` int(11) NOT NULL AUTO_INCREMENT,
  `specialisation_name` varchar(100) NOT NULL,
  PRIMARY KEY (`specialisation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `specialisation`
--

INSERT INTO `specialisation` (`specialisation_id`, `specialisation_name`) VALUES
(1, 'Dermatology'),
(2, 'Urology'),
(3, 'Orthopaedics'),
(4, 'Cardiology'),
(5, 'Emergency Medicine'),
(6, 'Administration');

-- --------------------------------------------------------

--
-- Table structure for table `department`
--

CREATE TABLE `department` (
  `department_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `address_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `department`
--

INSERT INTO `department` (`department_id`, `name`, `address_id`) VALUES
(1, 'Dermatology', 21),
(2, 'Urology', 22),
(3, 'Orthopaedics', 23),
(4, 'Emergency Medicine', 24),
(5, 'Cardiology', 21);

-- --------------------------------------------------------

--
-- Table structure for table `doctor`
--

CREATE TABLE `doctor` (
  `staffno` varchar(100) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(50) DEFAULT NULL,
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) DEFAULT NULL,
  `specialisation_id` int(11) DEFAULT NULL,
  `qualification` varchar(100) DEFAULT NULL,
  `pay` int(11) NOT NULL,
  `gender_id` int(11) DEFAULT NULL,
  `consultantstatus` TINYINT(1) NOT NULL DEFAULT 0,
  `address_id` int(11) DEFAULT NULL,
  `ward_id` int(11) DEFAULT NULL,
  `is_admin` TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctor`
--

INSERT INTO `doctor` (`staffno`, `username`, `password`, `firstname`, `lastname`, `specialisation_id`, `qualification`, `pay`, `gender_id`, `consultantstatus`, `address_id`, `ward_id`, `is_admin`) VALUES
('CH007', 'mceards', 'lord456', 'Steve', 'Fan', 1, NULL, 67000, 1, 1, 1, 1, 0),
('GT067', 'moorland', 'buzz48', 'Julie', 'Ford', 2, 'CCT', 66000, 2, 1, NULL, 2, 0),
('QM003', NULL, NULL, 'Joel', 'Graham', 3, NULL, 44000, 1, 0, 2, 3, 0),
('QM004', NULL, NULL, 'Jason', 'Atkin', 4, 'CCT', 60000, 1, 1, 3, 5, 0),
('QM009', NULL, NULL, 'Grazziela', 'Luis', 5, 'CCT', 62000, 2, 1, 4, 4, 0),
('QM122', NULL, NULL, 'David', 'Ulrik', 2, NULL, 46000, 1, 0, 5, 2, 0),
('QM267', NULL, NULL, 'Andrew', 'Xin', 1, 'CCT', 58000, 1, 1, 6, 1, 0),
('QM300', NULL, NULL, 'Joy', 'Liz', 4, 'CCT', 52000, 2, 0, 7, 5, 0),
('QT001', NULL, NULL, 'Martin', 'Peter', 3, NULL, 48000, 1, 0, 8, 3, 0),
('ADMIN001', 'jelina', 'iron99', 'Jelina', 'Administrator', 6, NULL, 75000, 2, 1, 9, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `patient`
--

CREATE TABLE `patient` (
  `NHSno` varchar(100) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) DEFAULT NULL,
  `phone` varchar(100) NOT NULL,
  `address_id` int(11) NOT NULL,
  `age` int(11) NOT NULL,
  `gender_id` int(11) DEFAULT NULL,
  `emergencyphone` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patient`
--

INSERT INTO `patient` (`NHSno`, `firstname`, `lastname`, `phone`, `address_id`, `age`, `gender_id`, `emergencyphone`) VALUES
('W20616', 'Zoya', 'Kalim', '07656999653', 10, 18, 2, NULL),
('W20620', 'Nazia', 'Rafiq', '07798522777', 11, 37, 2, NULL),
('W21028', 'Max', 'Wilson', '07740312868', 12, 33, 1, NULL),
('W21758', 'Alex', 'Kai', '06654742456', 13, 46, 1, NULL),
('W21814', 'Chao', 'Chen', '077 25 765428', 14, 36, 1, NULL),
('W21895', 'Liz', 'Felton', '074 56 733 487', 15, 23, 2, NULL),
('W21961', 'Jeremie', 'Clos', '07754312868', 16, 45, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `patientexamination`
--

CREATE TABLE `patientexamination` (
  `patientid` varchar(100) NOT NULL,
  `doctorid` varchar(100) NOT NULL,
  `date` DATE NOT NULL,
  `time` TIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patientexamination`
--

INSERT INTO `patientexamination` (`patientid`, `doctorid`, `date`, `time`) VALUES
('W20616', 'CH007', '2023-12-21', '11:23:11'),
('W20616', 'QM004', '2022-10-18', '10:23:19'),
('W20616', 'QM267', '2022-02-02', '08:23:19'),
('W20620', 'GT067', '2023-06-18', '07:06:05'),
('W20620', 'QM300', '2023-11-08', '09:09:19'),
('W21028', 'QM003', '2021-11-08', '09:23:19'),
('W21758', 'GT067', '2020-11-11', '11:23:05'),
('W21814', 'QM122', '2023-12-12', '02:02:10'),
('W21814', 'QT001', '2016-03-03', '08:18:18'),
('W21895', 'QM003', '2019-11-19', '08:09:10'),
('W21895', 'QM009', '2021-11-19', '08:08:08');

-- --------------------------------------------------------

--
-- Table structure for table `patient_test`
--

CREATE TABLE `patient_test` (
  `pid` varchar(100) NOT NULL,
  `testid` int(11) NOT NULL,
  `date` DATE NOT NULL,
  `report` varchar(100) DEFAULT NULL,
  `doctorid` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patient_test`
--

INSERT INTO `patient_test` (`pid`, `testid`, `date`, `report`, `doctorid`) VALUES
('W20616', 6, '2023-10-01', NULL, 'QM003'),
('W21028', 3, '2021-11-07', NULL, 'QM004'),
('W21028', 8, '2021-11-11', NULL, 'QM004'),
('W21758', 6, '2023-11-15', NULL, 'CH007'),
('W21758', 12, '2023-11-16', NULL, 'QM122'),
('W21814', 3, '2023-02-17', NULL, 'QM267'),
('W21814', 3, '2023-02-18', NULL, 'QM300'),
('W21814', 5, '2023-02-19', NULL, 'QM009'),
('W21895', 5, '2023-06-07', NULL, 'QM300'),
('W21895', 5, '2023-06-08', NULL, 'QM267'),
('W21895', 7, '2023-06-09', NULL, 'CH007'),
('W21961', 4, '2019-10-18', NULL, 'QM004');

-- --------------------------------------------------------

--
-- Table structure for table `test`
--

CREATE TABLE `test` (
  `testid` int(11) NOT NULL AUTO_INCREMENT,
  `testname` varchar(100) NOT NULL,
  PRIMARY KEY (`testid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `test`
--

INSERT INTO `test` (`testid`, `testname`) VALUES
(1, 'Blood count'),
(2, 'Urinalysis'),
(3, 'CT scan'),
(4, 'Ultrasonography'),
(5, 'Colonoscopy'),
(6, 'Genetic testing'),
(7, 'Hematocrit'),
(8, 'Pap smear'),
(9, 'X-ray'),
(10, 'Biopsy'),
(11, 'Mammography'),
(12, 'Lumbar puncture'),
(13, 'thyroid function test'),
(14, 'prenatal testing'),
(15, 'electrocardiography'),
(16, 'skin test');

-- --------------------------------------------------------

--
-- Table structure for table `ward`
--

CREATE TABLE `ward` (
  `wardid` int(11) NOT NULL AUTO_INCREMENT,
  `wardname` varchar(100) NOT NULL,
  `address_id` int(11) NOT NULL,
  `phone` varchar(100) NOT NULL,
  `noofbeds` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`wardid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ward`
--

INSERT INTO `ward` (`wardid`, `wardname`, `address_id`, `phone`, `noofbeds`, `department_id`) VALUES
(1, 'Dermatology', 17, '0115 970 9215', 45, 1),
(2, 'Urology', 18, '0115 870 9215', 43, 2),
(3, 'Orthopaedics ', 19, '0115 678 9215', 33, 3),
(4, 'Accident and emergency', 18, '0115 986 9215', 66, 4),
(5, 'Cardiology', 20, '0115 986 6578', 67, 5);

-- --------------------------------------------------------

--
-- Table structure for table `wardpatientaddmission`
--

CREATE TABLE `wardpatientaddmission` (
  `pid` varchar(100) NOT NULL,
  `wardid` int(11) NOT NULL,
  `consultantid` varchar(100) NOT NULL,
  `date` DATE NOT NULL,
  `time` TIME DEFAULT NULL,
  `status` ENUM('admitted', 'discharged') NOT NULL DEFAULT 'admitted'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wardpatientaddmission`
--

INSERT INTO `wardpatientaddmission` (`pid`, `wardid`, `consultantid`, `date`, `time`, `status`) VALUES
('W20616', 1, 'QM004', '2022-10-07', '09:23:19', 'discharged'),
('W20616', 2, 'QM122', '2023-10-01', '07:23:19', 'discharged'),
('W20616', 3, 'QM009', '2018-12-07', '08:13:55', 'discharged'),
('W20616', 5, 'QM267', '2022-06-07', '21:23:19', 'admitted'),
('W20620', 4, 'QM267', '2021-10-07', '08:08:08', 'discharged'),
('W21028', 2, 'CH007', '2021-11-07', '08:23:19', 'admitted'),
('W21758', 2, 'QM122', '2018-11-27', '23:55:56', 'admitted'),
('W21758', 4, 'QT001', '2023-09-29', '08:23:19', 'discharged'),
('W21814', 3, 'QM003', '2023-02-17', '08:33:33', 'discharged'),
('W21895', 4, 'CH007', '2023-06-07', '21:23:19', 'admitted'),
('W21961', 5, 'QM009', '2019-10-18', '08:34:19', 'discharged');

-- --------------------------------------------------------

--
-- Table structure for table `parking_permit`
--

CREATE TABLE `parking_permit` (
  `permit_id` int(11) NOT NULL AUTO_INCREMENT,
  `doctor_id` varchar(100) NOT NULL,
  `car_registration` varchar(20) NOT NULL,
  `permit_choice` ENUM('monthly', 'yearly') NOT NULL,
  `activation_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  `approved_by` varchar(100) DEFAULT NULL,
  `permit_number` varchar(50) DEFAULT NULL,
  `rejection_reason` varchar(500) DEFAULT NULL,
  `request_date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`permit_id`),
  KEY `fk_parking_doctor` (`doctor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parking_permit`
--

INSERT INTO `parking_permit` (`permit_id`, `doctor_id`, `car_registration`, `permit_choice`, `activation_date`, `end_date`, `amount`, `status`, `approved_by`, `permit_number`, `rejection_reason`, `request_date`) VALUES
(1, 'CH007', 'AB12CDE', 'yearly', '2024-01-01', '2024-12-31', 500.00, 'approved', 'ADMIN001', 'PERMIT-2024-001', NULL, '2023-12-15 10:30:00'),
(2, 'QM004', 'XY98ZWQ', 'monthly', '2024-11-01', '2024-11-30', 50.00, 'pending', NULL, NULL, NULL, '2024-11-20 14:20:00');

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `audit_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(100) NOT NULL,
  `action` varchar(50) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` varchar(100) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `timestamp` datetime DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`audit_id`),
  KEY `fk_audit_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`audit_id`, `user_id`, `action`, `table_name`, `record_id`, `old_value`, `new_value`, `timestamp`, `ip_address`) VALUES
(1, 'ADMIN001', 'APPROVE_PERMIT', 'parking_permit', '1', 'status: pending', 'status: approved, permit_number: PERMIT-2024-001', '2023-12-16 09:15:00', '127.0.0.1');

-- --------------------------------------------------------

--
-- Indexes for dumped tables
--

--
-- Indexes for table `gender`
--
ALTER TABLE `gender`
  ADD UNIQUE KEY `unique_gender_name` (`gender_name`);

--
-- Indexes for table `address`
--

--
-- Indexes for table `specialisation`
--
ALTER TABLE `specialisation`
  ADD UNIQUE KEY `unique_specialisation_name` (`specialisation_name`);

--
-- Indexes for table `department`
--
ALTER TABLE `department`
  ADD UNIQUE KEY `unique_department_name` (`name`),
  ADD KEY `fk_department_address` (`address_id`);

--
-- Indexes for table `doctor`
--
ALTER TABLE `doctor`
  ADD PRIMARY KEY (`staffno`),
  ADD UNIQUE KEY `unique_username` (`username`),
  ADD KEY `fk_doctor_gender` (`gender_id`),
  ADD KEY `fk_doctor_address` (`address_id`),
  ADD KEY `fk_doctor_specialisation` (`specialisation_id`),
  ADD KEY `fk_doctor_ward` (`ward_id`);

--
-- Indexes for table `patient`
--
ALTER TABLE `patient`
  ADD PRIMARY KEY (`NHSno`),
  ADD KEY `fk_patient_gender` (`gender_id`),
  ADD KEY `fk_patient_address` (`address_id`);

--
-- Indexes for table `patientexamination`
--
ALTER TABLE `patientexamination`
  ADD PRIMARY KEY (`patientid`,`doctorid`,`date`,`time`),
  ADD KEY `fk_examination_patient` (`patientid`),
  ADD KEY `fk_examination_doctor` (`doctorid`);

--
-- Indexes for table `patient_test`
--
ALTER TABLE `patient_test`
  ADD PRIMARY KEY (`pid`,`testid`,`date`),
  ADD KEY `fk_patienttest_patient` (`pid`),
  ADD KEY `fk_patienttest_test` (`testid`),
  ADD KEY `fk_patienttest_doctor` (`doctorid`);

--
-- Indexes for table `test`
--
ALTER TABLE `test`
  ADD UNIQUE KEY `unique_testname` (`testname`);

--
-- Indexes for table `ward`
--
ALTER TABLE `ward`
  ADD KEY `fk_ward_address` (`address_id`),
  ADD KEY `fk_ward_department` (`department_id`);

--
-- Indexes for table `wardpatientaddmission`
--
ALTER TABLE `wardpatientaddmission`
  ADD PRIMARY KEY (`pid`,`wardid`,`consultantid`,`date`),
  ADD KEY `fk_wardadmission_patient` (`pid`),
  ADD KEY `fk_wardadmission_ward` (`wardid`),
  ADD KEY `fk_wardadmission_consultant` (`consultantid`);

--
-- Indexes for table `parking_permit`
--
ALTER TABLE `parking_permit`
  ADD PRIMARY KEY (`permit_id`),
  ADD KEY `fk_parking_doctor` (`doctor_id`);

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`audit_id`),
  ADD KEY `fk_audit_user` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

ALTER TABLE `gender`
  MODIFY `gender_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `address`
  MODIFY `address_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

ALTER TABLE `specialisation`
  MODIFY `specialisation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

ALTER TABLE `department`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

ALTER TABLE `test`
  MODIFY `testid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

ALTER TABLE `ward`
  MODIFY `wardid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

ALTER TABLE `parking_permit`
  MODIFY `permit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `audit_log`
  MODIFY `audit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

ALTER TABLE `department`
  ADD CONSTRAINT `fk_department_address` FOREIGN KEY (`address_id`) REFERENCES `address` (`address_id`) ON DELETE SET NULL;

ALTER TABLE `ward`
  ADD CONSTRAINT `fk_ward_address` FOREIGN KEY (`address_id`) REFERENCES `address` (`address_id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_ward_department` FOREIGN KEY (`department_id`) REFERENCES `department` (`department_id`) ON DELETE SET NULL;

ALTER TABLE `doctor`
  ADD CONSTRAINT `fk_doctor_gender` FOREIGN KEY (`gender_id`) REFERENCES `gender` (`gender_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_doctor_address` FOREIGN KEY (`address_id`) REFERENCES `address` (`address_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_doctor_specialisation` FOREIGN KEY (`specialisation_id`) REFERENCES `specialisation` (`specialisation_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_doctor_ward` FOREIGN KEY (`ward_id`) REFERENCES `ward` (`wardid`) ON DELETE SET NULL;

ALTER TABLE `patient`
  ADD CONSTRAINT `fk_patient_gender` FOREIGN KEY (`gender_id`) REFERENCES `gender` (`gender_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_patient_address` FOREIGN KEY (`address_id`) REFERENCES `address` (`address_id`) ON DELETE RESTRICT;

ALTER TABLE `patientexamination`
  ADD CONSTRAINT `fk_examination_patient` FOREIGN KEY (`patientid`) REFERENCES `patient` (`NHSno`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_examination_doctor` FOREIGN KEY (`doctorid`) REFERENCES `doctor` (`staffno`) ON DELETE CASCADE;

ALTER TABLE `patient_test`
  ADD CONSTRAINT `fk_patienttest_patient` FOREIGN KEY (`pid`) REFERENCES `patient` (`NHSno`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_patienttest_test` FOREIGN KEY (`testid`) REFERENCES `test` (`testid`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_patienttest_doctor` FOREIGN KEY (`doctorid`) REFERENCES `doctor` (`staffno`) ON DELETE SET NULL;

ALTER TABLE `wardpatientaddmission`
  ADD CONSTRAINT `fk_wardadmission_patient` FOREIGN KEY (`pid`) REFERENCES `patient` (`NHSno`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_wardadmission_ward` FOREIGN KEY (`wardid`) REFERENCES `ward` (`wardid`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_wardadmission_consultant` FOREIGN KEY (`consultantid`) REFERENCES `doctor` (`staffno`) ON DELETE CASCADE;

ALTER TABLE `parking_permit`
  ADD CONSTRAINT `fk_parking_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctor` (`staffno`) ON DELETE CASCADE;

ALTER TABLE `audit_log`
  ADD CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `doctor` (`staffno`) ON DELETE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
