-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3308
-- Generation Time: Jun 07, 2024 at 01:02 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


CREATE TABLE Surat (
    Tanggal_Diinput DATE NOT NULL,
    Tanggal_Surat DATE NOT NULL,
    No_Surat VARCHAR(50) NOT NULL,
    Jenis_Surat VARCHAR(50) NOT NULL,
    No_Agenda VARCHAR(50) NOT NULL,
    Perihal TEXT NOT NULL,
    Ringkasan TEXT,
    Pengirim_Eksternal VARCHAR(100),
    Pengirim_Internal VARCHAR(100),
    Penerima VARCHAR(100),
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;