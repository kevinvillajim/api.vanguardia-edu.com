-- Script para crear la base de datos VanguardIA
-- Ejecutar en MySQL

CREATE DATABASE IF NOT EXISTS vanguardia_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE vanguardia_db;

-- Verificar que la base de datos fue creada
SELECT DATABASE() as current_database;

-- Mostrar las tablas (estará vacío hasta correr las migraciones)
SHOW TABLES;