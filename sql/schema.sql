-- Structura bazei de date. Se importă O SINGURĂ DATĂ din cPanel → phpMyAdmin → Import.

CREATE TABLE IF NOT EXISTS rezervari (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  sosire     DATE NOT NULL,
  plecare    DATE NOT NULL,
  nopti      INT NOT NULL,
  persoane   INT NOT NULL,
  nume       VARCHAR(120) NOT NULL,
  email      VARCHAR(160) NOT NULL,
  telefon    VARCHAR(40)  NOT NULL,
  mesaj      TEXT,
  total      INT NOT NULL,                              -- lei
  status     VARCHAR(20) NOT NULL DEFAULT 'in_asteptare', -- in_asteptare / confirmata / anulata
  ip         VARCHAR(45),
  creat_la   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (status, sosire, plecare)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
