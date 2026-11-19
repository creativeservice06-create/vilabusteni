<?php
// STRUCTURA BAZEI DE DATE – se aplică AUTOMAT, nu trebuie importat nimic manual.
//
// Fiecare pas are un număr. Site-ul ține minte (în tabelul "versiune_db")
// până la ce pas a ajuns și le rulează doar pe cele noi.
// Pentru o modificare viitoare: adaugi un pas nou la final (ex. 2 => "ALTER TABLE ..."),
// NU modifici pașii vechi.

function migrari()
{
    return [
        1 => "CREATE TABLE IF NOT EXISTS rezervari (
                id         INT AUTO_INCREMENT PRIMARY KEY,
                sosire     DATE NOT NULL,
                plecare    DATE NOT NULL,
                nopti      INT NOT NULL,
                persoane   INT NOT NULL,
                nume       VARCHAR(120) NOT NULL,
                email      VARCHAR(160) NOT NULL,
                telefon    VARCHAR(40)  NOT NULL,
                mesaj      TEXT,
                total      INT NOT NULL,
                status     VARCHAR(20) NOT NULL DEFAULT 'in_asteptare',
                ip         VARCHAR(45),
                creat_la   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX (status, sosire, plecare)
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // limba în care clientul a făcut rezervarea (ro, en, de, it, ru)
        2 => "ALTER TABLE rezervari ADD COLUMN limba VARCHAR(5) NOT NULL DEFAULT 'ro'",

        // notă internă (vizibilă doar în panoul de administrare) și data ultimei schimbări de status
        3 => "ALTER TABLE rezervari ADD COLUMN nota VARCHAR(255) NULL, ADD COLUMN modificat_la DATETIME NULL",

        // proforma și plata: cod secret pentru linkul proformei, avansul, termenul de plată, data plății
        4 => "ALTER TABLE rezervari ADD COLUMN cod VARCHAR(32) NULL, ADD COLUMN avans INT NOT NULL DEFAULT 0,
                                    ADD COLUMN termen_plata DATETIME NULL, ADD COLUMN platit_la DATETIME NULL",

        // ce a ales clientul să plătească (avans / suma / integral) și dovada de plată încărcată
        5 => "ALTER TABLE rezervari ADD COLUMN optiune_plata VARCHAR(10) NOT NULL DEFAULT 'avans',
                                    ADD COLUMN dovada_fisier VARCHAR(255) NULL, ADD COLUMN dovada_la DATETIME NULL",

        // datele pentru factura fiscală (persoană fizică sau firmă) și cum s-a plătit (transfer / card)
        6 => "ALTER TABLE rezervari ADD COLUMN fact_tip VARCHAR(2) NOT NULL DEFAULT 'PF',
                                    ADD COLUMN fact_tara VARCHAR(2) NULL, ADD COLUMN fact_judet VARCHAR(3) NULL,
                                    ADD COLUMN fact_localitate VARCHAR(100) NULL, ADD COLUMN fact_adresa VARCHAR(255) NULL,
                                    ADD COLUMN fact_firma VARCHAR(160) NULL, ADD COLUMN fact_cui VARCHAR(20) NULL,
                                    ADD COLUMN fact_reg_com VARCHAR(40) NULL, ADD COLUMN plata_metoda VARCHAR(10) NULL",

        // plățile cu cardul prin Netopia (o rezervare poate avea mai multe încercări)
        7 => "CREATE TABLE IF NOT EXISTS plati_card (
                id            INT AUTO_INCREMENT PRIMARY KEY,
                rezervare_id  INT NOT NULL,
                comanda       VARCHAR(40) NOT NULL,
                suma          INT NOT NULL,
                ntp_id        VARCHAR(40) NULL,
                status        VARCHAR(12) NOT NULL DEFAULT 'pornita',
                cod_netopia   INT NULL,
                mesaj         VARCHAR(255) NULL,
                card          VARCHAR(40) NULL,
                creat_la      DATETIME NOT NULL,
                actualizat_la DATETIME NULL,
                UNIQUE (comanda),
                INDEX (rezervare_id)
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // facturile fiscale emise prin FGO (avans / integral / rest)
        8 => "CREATE TABLE IF NOT EXISTS facturi (
                id            INT AUTO_INCREMENT PRIMARY KEY,
                rezervare_id  INT NOT NULL,
                tip           VARCHAR(10) NOT NULL,
                suma          INT NOT NULL,
                serie         VARCHAR(50) NULL,
                numar         VARCHAR(50) NULL,
                link          VARCHAR(500) NULL,
                eroare        VARCHAR(500) NULL,
                creat_la      DATETIME NOT NULL,
                INDEX (rezervare_id)
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];
}

/** Aplică pașii noi. Se apelează automat la conectarea la baza de date. */
function aplica_migrari(PDO $pdo)
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS versiune_db (versiune INT NOT NULL)");
    $curenta = (int) $pdo->query("SELECT MAX(versiune) FROM versiune_db")->fetchColumn();

    foreach (migrari() as $nr => $sql) {
        if ($nr <= $curenta) {
            continue;
        }
        $pdo->exec($sql);
        $pdo->prepare("INSERT INTO versiune_db (versiune) VALUES (?)")->execute([$nr]);
    }
}
