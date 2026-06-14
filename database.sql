-- ============================================
-- Si-PAKAR - Database Schema
-- Sistem Terpadu Pengelolaan Agenda dan
-- Aktivitas Perkuliahan
-- ============================================

CREATE DATABASE IF NOT EXISTS sipakar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sipakar;

-- Tabel Users (Admin & Mahasiswa)
CREATE TABLE users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nama        VARCHAR(100) NOT NULL,
    nim         VARCHAR(20)  UNIQUE,
    email       VARCHAR(100) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('admin','student') NOT NULL DEFAULT 'student',
    prodi       VARCHAR(100),
    fakultas    VARCHAR(100),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Jadwal Kuliah
CREATE TABLE schedules (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    matkul      VARCHAR(100) NOT NULL,
    hari        ENUM('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu') NOT NULL,
    jam_mulai   TIME NOT NULL,
    jam_selesai TIME NOT NULL,
    ruangan     VARCHAR(50),
    dosen       VARCHAR(100),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel Tugas / To-Do
CREATE TABLE tasks (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    schedule_id INT,
    nama_tugas  VARCHAR(200) NOT NULL,
    deskripsi   TEXT,
    deadline    DATETIME,
    prioritas   ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
    status      ENUM('pending','selesai') NOT NULL DEFAULT 'pending',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE SET NULL
);

-- Tabel Master Prodi
CREATE TABLE prodi (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nama_prodi  VARCHAR(100) NOT NULL,
    fakultas    VARCHAR(100) NOT NULL
);

-- ============================================
-- Seed Data
-- ============================================

-- Admin default (password: admin123)
INSERT INTO users (nama, email, password, role) VALUES
('Administrator', 'admin@sipakar.ac.id', '$2y$10$KXUEUbgUG8Rt2uRRHJPOGuIJ.Z2ScfO4kcqRAS7R2ur8Iw8keAnOG', 'admin');

-- Mahasiswa demo (password: mahasiswa123)
INSERT INTO users (nama, nim, email, password, role, prodi, fakultas) VALUES
('Aldza Salwatul Aisy', '240605110228', 'aldza@student.sipakar.ac.id', '$2y$10$Qea8IFFJOnWHJay4Jm114.AjbAEK30XEvMnoN9iDFyacKF7sW/23W', 'student', 'Teknik Informatika', 'Sains dan Teknologi'),
('Najwarizqa Aryandri', '240605110060', 'najwa@student.sipakar.ac.id', '$2y$10$Qea8IFFJOnWHJay4Jm114.AjbAEK30XEvMnoN9iDFyacKF7sW/23W', 'student', 'Teknik Informatika', 'Sains dan Teknologi');

-- Master prodi
INSERT INTO prodi (nama_prodi, fakultas) VALUES
('Teknik Informatika', 'Sains dan Teknologi'),
('Sistem Informasi', 'Sains dan Teknologi'),
('Matematika', 'Sains dan Teknologi'),
('Biologi', 'Sains dan Teknologi');
