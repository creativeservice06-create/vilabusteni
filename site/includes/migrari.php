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
