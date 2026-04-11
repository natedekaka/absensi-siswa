-- Migration: Add konfigurasi_sekolah table for school profile feature
-- Created: 2026-03-11

CREATE TABLE IF NOT EXISTS konfigurasi_sekolah (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_sekolah VARCHAR(255) NOT NULL DEFAULT 'SMA Negeri',
    logo VARCHAR(255) DEFAULT NULL,
    warna_primer VARCHAR(20) DEFAULT '#4f46e5',
    warna_sekunder VARCHAR(20) DEFAULT '#64748b',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO konfigurasi_sekolah (nama_sekolah) VALUES ('SMA Negeri');
