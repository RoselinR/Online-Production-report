-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3377
-- Generation Time: Dec 30, 2024 at 01:07 AM
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
-- Database: `trg`
--

-- --------------------------------------------------------

--
-- Table structure for table `componentdetails`
--

CREATE TABLE `componentdetails` (
  `SNO` bigint(20) UNSIGNED NOT NULL,
  `LFNO` varchar(15) NOT NULL,
  `DrawingNo` varchar(15) NOT NULL,
  `CompName` varchar(15) NOT NULL,
  `Description` text NOT NULL,
  `UR` int(11) NOT NULL,
  `Variant` varchar(10) NOT NULL,
  `HigherAssembly` varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `componentdetails`
--

INSERT INTO `componentdetails` (`SNO`, `LFNO`, `DrawingNo`, `CompName`, `Description`, `UR`, `Variant`, `HigherAssembly`) VALUES
(1, '111112', '112233', 'Ger', 'ger', 34, 'T72', 'Gerboc'),
(2, '111113', '123333', 'Gerboc', 'Gerboc2', 5, '1313', 'None');

-- --------------------------------------------------------

--
-- Table structure for table `employeedetails`
--

CREATE TABLE `employeedetails` (
  `SNO` bigint(20) UNSIGNED NOT NULL,
  `PerNo` varchar(15) NOT NULL,
  `Name` varchar(30) NOT NULL,
  `Design` varchar(30) NOT NULL,
  `Remarks` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employeedetails`
--

INSERT INTO `employeedetails` (`SNO`, `PerNo`, `Name`, `Design`, `Remarks`) VALUES
(1, '9361', 'VIJAYKUMAR S', 'MACHINIST', '');

-- --------------------------------------------------------

--
-- Table structure for table `machinedetails`
--

CREATE TABLE `machinedetails` (
  `SNO` bigint(20) UNSIGNED NOT NULL,
  `HVFNo` varchar(15) NOT NULL,
  `Make` varchar(30) NOT NULL,
  `Model` varchar(15) NOT NULL,
  `Name` varchar(15) NOT NULL,
  `Description` text NOT NULL,
  `Comm_date` date NOT NULL,
  `Location` varchar(15) NOT NULL,
  `Bay` varchar(5) NOT NULL,
  `WorkingCondition` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `machinedetails`
--

INSERT INTO `machinedetails` (`SNO`, `HVFNo`, `Make`, `Model`, `Name`, `Description`, `Comm_date`, `Location`, `Bay`, `WorkingCondition`) VALUES
(1, '123123', 'M12', 'maod', 'Lath', 'dats', '2024-12-03', 'TRG1', '4', 'NoOperator');

-- --------------------------------------------------------

--
-- Table structure for table `operationdetails`
--

CREATE TABLE `operationdetails` (
  `SNO` bigint(20) UNSIGNED NOT NULL,
  `DrawingNo` varchar(15) NOT NULL,
  `OpnNo` varchar(10) NOT NULL,
  `OpnName` varchar(20) NOT NULL,
  `OpnDesc` text NOT NULL,
  `HVFNo` varchar(20) NOT NULL,
  `MachineType` varchar(30) NOT NULL,
  `TGT` int(11) NOT NULL,
  `CLS` int(11) NOT NULL,
  `Bal` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `operationdetails`
--

INSERT INTO `operationdetails` (`SNO`, `DrawingNo`, `OpnNo`, `OpnName`, `OpnDesc`, `HVFNo`, `MachineType`, `TGT`, `CLS`, `Bal`) VALUES
(1, '112233', '10', 'DADA', 'aala', '123123', 'lath', 11, 3, 8);

--
-- Triggers `operationdetails`
--
DELIMITER $$
CREATE TRIGGER `UpdateBalOnClsIncrement` BEFORE UPDATE ON `operationdetails` FOR EACH ROW BEGIN
    -- Check if CLS is being incremented
    IF NEW.CLS > OLD.CLS THEN
        -- Ensure CLS does not exceed TGT
        IF NEW.CLS > NEW.TGT THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'CLS cannot exceed TGT value';
        END IF;

        -- Decrement Bal based on the CLS increment
        SET NEW.Bal = NEW.Bal - (NEW.CLS - OLD.CLS);
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `productiondetails`
--

CREATE TABLE `productiondetails` (
  `SNO` bigint(20) UNSIGNED NOT NULL,
  `TodaysDate` date NOT NULL,
  `FillDate` date NOT NULL,
  `MachineStatus` varchar(30) NOT NULL,
  `Shift` varchar(30) NOT NULL,
  `BayNo` varchar(30) NOT NULL,
  `PerNo` varchar(30) NOT NULL,
  `MachineNo` varchar(30) NOT NULL,
  `DrawingNo` varchar(30) NOT NULL,
  `ComponentsProduced` int(11) NOT NULL,
  `OpnNo` varchar(30) NOT NULL,
  `CLS` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `productiondetails`
--

INSERT INTO `productiondetails` (`SNO`, `TodaysDate`, `FillDate`, `MachineStatus`, `Shift`, `BayNo`, `PerNo`, `MachineNo`, `DrawingNo`, `ComponentsProduced`, `OpnNo`, `CLS`) VALUES
(7, '0000-00-00', '2024-12-30', 'NoOperator', 'Shift 3', '3', '9361', '123123', '112233', 1, '10', 2),
(8, '2024-12-30', '2024-12-30', 'NoOperator', 'Shift 3', '3', '9361', '123123', '112233', 1, '10', 3);

-- --------------------------------------------------------

--
-- Table structure for table `target`
--

CREATE TABLE `target` (
  `SNO` bigint(20) UNSIGNED NOT NULL,
  `LFNO` varchar(15) NOT NULL,
  `DrawingNo` varchar(30) NOT NULL,
  `Description` text NOT NULL,
  `Variant` varchar(15) NOT NULL,
  `Target` int(11) NOT NULL,
  `Year` year(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(30) NOT NULL,
  `password` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`) VALUES
(1, 'rambabu', '11111');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `componentdetails`
--
ALTER TABLE `componentdetails`
  ADD PRIMARY KEY (`LFNO`),
  ADD UNIQUE KEY `SNO` (`SNO`);

--
-- Indexes for table `employeedetails`
--
ALTER TABLE `employeedetails`
  ADD PRIMARY KEY (`PerNo`),
  ADD UNIQUE KEY `SNO` (`SNO`);

--
-- Indexes for table `machinedetails`
--
ALTER TABLE `machinedetails`
  ADD PRIMARY KEY (`HVFNo`),
  ADD UNIQUE KEY `SNO` (`SNO`);

--
-- Indexes for table `operationdetails`
--
ALTER TABLE `operationdetails`
  ADD UNIQUE KEY `SNO` (`SNO`);

--
-- Indexes for table `productiondetails`
--
ALTER TABLE `productiondetails`
  ADD UNIQUE KEY `SNO` (`SNO`);

--
-- Indexes for table `target`
--
ALTER TABLE `target`
  ADD PRIMARY KEY (`LFNO`),
  ADD UNIQUE KEY `SNO` (`SNO`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `componentdetails`
--
ALTER TABLE `componentdetails`
  MODIFY `SNO` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `employeedetails`
--
ALTER TABLE `employeedetails`
  MODIFY `SNO` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `machinedetails`
--
ALTER TABLE `machinedetails`
  MODIFY `SNO` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `operationdetails`
--
ALTER TABLE `operationdetails`
  MODIFY `SNO` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `productiondetails`
--
ALTER TABLE `productiondetails`
  MODIFY `SNO` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `target`
--
ALTER TABLE `target`
  MODIFY `SNO` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
