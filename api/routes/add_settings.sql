-- Adiciona colunas de configuração à tabela users
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS work_hour_start   INT DEFAULT 9,
  ADD COLUMN IF NOT EXISTS work_hour_end     INT DEFAULT 18,
  ADD COLUMN IF NOT EXISTS daily_invite_limit INT DEFAULT 20,
  ADD COLUMN IF NOT EXISTS timezone         VARCHAR(50) DEFAULT 'Europe/Lisbon',
  ADD COLUMN IF NOT EXISTS linkedin_url     VARCHAR(300) NULL,
  ADD COLUMN IF NOT EXISTS notifications_email TINYINT(1) DEFAULT 1,
  ADD COLUMN IF NOT EXISTS notifications_summary TINYINT(1) DEFAULT 1;
