-- =====================================================================
-- Stallo - zirgu staļļu pārvaldības sistēma
-- MySQL 8.0+ shēma (13 tabulas)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS Stallo
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE Stallo;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS piezimes;
DROP TABLE IF EXISTS dokumenti;
DROP TABLE IF EXISTS treninji;
DROP TABLE IF EXISTS atgadinajumi;
DROP TABLE IF EXISTS noliktava;
DROP TABLE IF EXISTS veselibas_ieraksti;
DROP TABLE IF EXISTS uzdevumi;
DROP TABLE IF EXISTS barosanas_uzdevumi;
DROP TABLE IF EXISTS barosanas_plani;
DROP TABLE IF EXISTS zirgi;
DROP TABLE IF EXISTS lietotaji;
DROP TABLE IF EXISTS lomas;
DROP TABLE IF EXISTS stalli;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- 1. STALLI - staļļi (katrs stallis ir atsevišķs "īrnieks" sistēmā)
-- ---------------------------------------------------------------------
CREATE TABLE stalli (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nosaukums       VARCHAR(150) NOT NULL,
    adrese          VARCHAR(255),
    talrunis        VARCHAR(30),
    epasts          VARCHAR(150),
    izveidots       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. LOMAS - lietotāju lomas (īpašnieks, treneris, darbinieks, veterinārs...)
-- ---------------------------------------------------------------------
CREATE TABLE lomas (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    stalla_id       INT UNSIGNED NOT NULL,
    nosaukums       VARCHAR(80) NOT NULL,
    apraksts        VARCHAR(255),
    tiesibas        JSON COMMENT 'Tiesību saraksts, piem. {"zirgi":"edit","noliktava":"view"}',
    UNIQUE KEY uq_loma_stallis (stalla_id, nosaukums),
    CONSTRAINT fk_lomas_stalli FOREIGN KEY (stalla_id)
        REFERENCES stalli(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3. LIETOTAJI - sistēmas lietotāji
-- ---------------------------------------------------------------------
CREATE TABLE lietotaji (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    stalla_id       INT UNSIGNED NOT NULL,
    loma_id         INT UNSIGNED NOT NULL,
    vards           VARCHAR(80) NOT NULL,
    uzvards         VARCHAR(80) NOT NULL,
    epasts          VARCHAR(150) NOT NULL,
    paroles_hash    VARCHAR(255) NOT NULL,
    talrunis        INT(30),
    aktivs          TINYINT(1) NOT NULL DEFAULT 1,
    pedeja_pieslegsanas DATETIME NULL,
    izveidots       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_lietotaji_epasts (epasts),
    CONSTRAINT fk_lietotaji_stalli FOREIGN KEY (stalla_id)
        REFERENCES stalli(id) ON DELETE CASCADE,
    CONSTRAINT fk_lietotaji_lomas FOREIGN KEY (loma_id)
        REFERENCES lomas(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4. ZIRGI - zirgu profili
-- ---------------------------------------------------------------------
CREATE TABLE zirgi (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    stalla_id       INT UNSIGNED NOT NULL,
    ipasnieka_id    INT UNSIGNED NULL COMMENT 'Lietotājs, kas ir zirga īpašnieks',
    vards           VARCHAR(100) NOT NULL,
    šķirne          VARCHAR(100),
    dzimums         ENUM('Ērzelis','Kumeļš','Ērzeļkumeļs', 'Ķēvīte', 'Ķēve', 'Valahs') NOT NULL,
    dzimsanas_datums DATE,
    krasa           VARCHAR(60),
    skausta_augstums_cm SMALLINT UNSIGNED,
    svars_kg        DECIMAL(6,1),
    chip_numurs     VARCHAR(30),
    pase_numurs     VARCHAR(50),
    bokss           VARCHAR(20) COMMENT 'Boksa / vietas numurs stallī',
    foto_url        VARCHAR(255),
    qr_kods         VARCHAR(100) NOT NULL COMMENT 'Unikāla QR koda vērtība zirga ātrai atpazīšanai',
    statuss         ENUM('aktīvs','ārstēšanā','pensijā','pārdots','miris') NOT NULL DEFAULT 'aktīvs',
    izveidots       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_zirgi_qr (qr_kods),
    UNIQUE KEY uq_zirgi_chip (chip_numurs),
    KEY idx_zirgi_stallis (stalla_id),
    CONSTRAINT fk_zirgi_stalli FOREIGN KEY (stalla_id)
        REFERENCES stalli(id) ON DELETE CASCADE,
    CONSTRAINT fk_zirgi_ipasnieks FOREIGN KEY (ipasnieka_id)
        REFERENCES lietotaji(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5. BAROSANAS_PLANI - zirga barošanas plāns
-- ---------------------------------------------------------------------
CREATE TABLE barosanas_plani (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    zirga_id        INT UNSIGNED NOT NULL,
    izveidoja_id    INT UNSIGNED NULL,
    nosaukums       VARCHAR(120) NOT NULL,
    apraksts        TEXT,
    spekss_no       DATE NOT NULL,
    spekss_lidz     DATE NULL,
    aktivs          TINYINT(1) NOT NULL DEFAULT 1,
    izveidots       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_bplani_zirgs (zirga_id),
    CONSTRAINT fk_bplani_zirgi FOREIGN KEY (zirga_id)
        REFERENCES zirgi(id) ON DELETE CASCADE,
    CONSTRAINT fk_bplani_lietotaji FOREIGN KEY (izveidoja_id)
        REFERENCES lietotaji(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 6. BAROSANAS_UZDEVUMI - konkrēti barošanas reizes ieraksti plānā
-- ---------------------------------------------------------------------
CREATE TABLE barosanas_uzdevumi (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plana_id        INT UNSIGNED NOT NULL,
    noliktavas_id   INT UNSIGNED NULL COMMENT 'Kāds barības produkts tiek izmantots',
    laiks           TIME NOT NULL,
    barība          VARCHAR(120) NOT NULL,
    daudzums        DECIMAL(8,2) NOT NULL,
    merv_vieniba    VARCHAR(20) NOT NULL DEFAULT 'kg',
    piezimes        VARCHAR(255),
    izpildits       TINYINT(1) NOT NULL DEFAULT 0,
    izpildits_laika DATETIME NULL,
    izpildija_id    INT UNSIGNED NULL,
    KEY idx_bu_plans (plana_id),
    CONSTRAINT fk_bu_plani FOREIGN KEY (plana_id)
        REFERENCES barosanas_plani(id) ON DELETE CASCADE,
    CONSTRAINT fk_bu_izpildija FOREIGN KEY (izpildija_id)
        REFERENCES lietotaji(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 7. UZDEVUMI - vispārīgie stallī veicamie uzdevumi (kalendāram)
-- ---------------------------------------------------------------------
CREATE TABLE uzdevumi (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    stalla_id       INT UNSIGNED NOT NULL,
    zirga_id        INT UNSIGNED NULL,
    pieskirts_id    INT UNSIGNED NULL COMMENT 'Kuram lietotājam uzdevums piešķirts',
    izveidoja_id    INT UNSIGNED NULL,
    nosaukums       VARCHAR(150) NOT NULL,
    apraksts        TEXT,
    termins         DATETIME NULL,
    prioritate      ENUM('zema','vidēja','augsta') NOT NULL DEFAULT 'vidēja',
    statuss         ENUM('jauns','procesā','pabeigts','atcelts') NOT NULL DEFAULT 'jauns',
    izveidots       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_uzd_termins (termins),
    CONSTRAINT fk_uzd_stalli FOREIGN KEY (stalla_id)
        REFERENCES stalli(id) ON DELETE CASCADE,
    CONSTRAINT fk_uzd_zirgi FOREIGN KEY (zirga_id)
        REFERENCES zirgi(id) ON DELETE SET NULL,
    CONSTRAINT fk_uzd_pieskirts FOREIGN KEY (pieskirts_id)
        REFERENCES lietotaji(id) ON DELETE SET NULL,
    CONSTRAINT fk_uzd_izveidoja FOREIGN KEY (izveidoja_id)
        REFERENCES lietotaji(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 8. VESELIBAS_IERAKSTI - vakcinācijas, ārsta vizītes, ārstēšana, pakaltīšana
-- ---------------------------------------------------------------------
CREATE TABLE veselibas_ieraksti (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    zirga_id        INT UNSIGNED NOT NULL,
    veica_id        INT UNSIGNED NULL,
    tips            ENUM('vakcinācija','dehelmintizācija','veterinārs','zobārsts',
                         'pakaltīšana','trauma','slimība','cits') NOT NULL,
    datums          DATE NOT NULL,
    nakama_datums   DATE NULL COMMENT 'Kad nākamā reize (piem., revakcinācija)',
    apraksts        TEXT,
    diagnoze        VARCHAR(255),
    arstesana       TEXT,
    veterinars      VARCHAR(120),
    izmaksas        DECIMAL(10,2),
    izveidots       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_vesel_zirgs (zirga_id, datums),
    CONSTRAINT fk_vesel_zirgi FOREIGN KEY (zirga_id)
        REFERENCES zirgi(id) ON DELETE CASCADE,
    CONSTRAINT fk_vesel_veica FOREIGN KEY (veica_id)
        REFERENCES lietotaji(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 9. NOLIKTAVA - barība, piederumi, medikamenti u.c. krājumi
-- ---------------------------------------------------------------------
CREATE TABLE noliktava (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    stalla_id       INT UNSIGNED NOT NULL,
    nosaukums       VARCHAR(150) NOT NULL,
    kategorija      ENUM('barība','piedevas','medikamenti','pakaiši','aprīkojums','cits')
                    NOT NULL DEFAULT 'cits',
    daudzums        DECIMAL(10,2) NOT NULL DEFAULT 0,
    merv_vieniba    VARCHAR(20) NOT NULL DEFAULT 'kg',
    min_limenis     DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'Zem šī līmeņa jāgenerē atgādinājums',
    cena_par_vienibu DECIMAL(10,2),
    piegadatajs     VARCHAR(150),
    derigs_lidz     DATE NULL,
    atjaunots       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_nol_stallis (stalla_id, kategorija),
    CONSTRAINT fk_nol_stalli FOREIGN KEY (stalla_id)
        REFERENCES stalli(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Atliktā saite: barošanas uzdevumi → noliktava
ALTER TABLE barosanas_uzdevumi
    ADD CONSTRAINT fk_bu_noliktava FOREIGN KEY (noliktavas_id)
        REFERENCES noliktava(id) ON DELETE SET NULL;

-- ---------------------------------------------------------------------
-- 10. ATGADINAJUMI - automātiski un manuāli atgādinājumi
-- ---------------------------------------------------------------------
CREATE TABLE atgadinajumi (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lietotaja_id    INT UNSIGNED NOT NULL,
    zirga_id        INT UNSIGNED NULL,
    noliktavas_id   INT UNSIGNED NULL,
    tips            ENUM('vakcinācija','pakaltīšana','barība','noliktava','uzdevums','cits')
                    NOT NULL DEFAULT 'cits',
    zinojums        VARCHAR(255) NOT NULL,
    atgadinat_laika DATETIME NOT NULL,
    nosutits        TINYINT(1) NOT NULL DEFAULT 0,
    izlasits        TINYINT(1) NOT NULL DEFAULT 0,
    izveidots       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_atg_laiks (atgadinat_laika, nosutits),
    CONSTRAINT fk_atg_lietotaji FOREIGN KEY (lietotaja_id)
        REFERENCES lietotaji(id) ON DELETE CASCADE,
    CONSTRAINT fk_atg_zirgi FOREIGN KEY (zirga_id)
        REFERENCES zirgi(id) ON DELETE CASCADE,
    CONSTRAINT fk_atg_noliktava FOREIGN KEY (noliktavas_id)
        REFERENCES noliktava(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 11. TRENINJI - zirgu treniņu uzskaite
-- ---------------------------------------------------------------------
CREATE TABLE treninji (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    zirga_id        INT UNSIGNED NOT NULL,
    trenera_id      INT UNSIGNED NULL,
    jatnieka_id     INT UNSIGNED NULL,
    datums_laiks    DATETIME NOT NULL,
    ilgums_min      SMALLINT UNSIGNED,
    tips            ENUM('jāšana','izjāde','lonžēšana','brīvā kustība','sacensības','cits')
                    NOT NULL DEFAULT 'jāšana',
    intensitate     ENUM('viegla','vidēja','augsta') NOT NULL DEFAULT 'vidēja',
    apraksts        TEXT,
    zirga_stavoklis VARCHAR(255) COMMENT 'Novērojumi pēc treniņa',
    izveidots       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_tren_zirgs (zirga_id, datums_laiks),
    CONSTRAINT fk_tren_zirgi FOREIGN KEY (zirga_id)
        REFERENCES zirgi(id) ON DELETE CASCADE,
    CONSTRAINT fk_tren_treneris FOREIGN KEY (trenera_id)
        REFERENCES lietotaji(id) ON DELETE SET NULL,
    CONSTRAINT fk_tren_jatnieks FOREIGN KEY (jatnieka_id)
        REFERENCES lietotaji(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 12. DOKUMENTI - pases, vakcinācijas apliecības, līgumi, foto u.c.
-- ---------------------------------------------------------------------
CREATE TABLE dokumenti (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    zirga_id        INT UNSIGNED NOT NULL,
    augsupieladeja_id INT UNSIGNED NULL,
    nosaukums       VARCHAR(150) NOT NULL,
    tips            ENUM('pase','vakcinācijas apliecība','veterinārā izziņa','līgums',
                         'apdrošināšana','foto','cits') NOT NULL DEFAULT 'cits',
    faila_cels      VARCHAR(255) NOT NULL,
    faila_tips      VARCHAR(50),
    faila_izmers_kb INT UNSIGNED,
    derigs_lidz     DATE NULL,
    augsupieladets  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_dok_zirgs (zirga_id),
    CONSTRAINT fk_dok_zirgi FOREIGN KEY (zirga_id)
        REFERENCES zirgi(id) ON DELETE CASCADE,
    CONSTRAINT fk_dok_lietotaji FOREIGN KEY (augsupieladeja_id)
        REFERENCES lietotaji(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 13. PIEZIMES - brīvas piezīmes par zirgiem
-- ---------------------------------------------------------------------
CREATE TABLE piezimes (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    zirga_id        INT UNSIGNED NOT NULL,
    autora_id       INT UNSIGNED NULL,
    virsraksts      VARCHAR(150),
    saturs          TEXT NOT NULL,
    svarigs         TINYINT(1) NOT NULL DEFAULT 0,
    izveidots       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_piez_zirgs (zirga_id, izveidots),
    CONSTRAINT fk_piez_zirgi FOREIGN KEY (zirga_id)
        REFERENCES zirgi(id) ON DELETE CASCADE,
    CONSTRAINT fk_piez_autors FOREIGN KEY (autora_id)
        REFERENCES lietotaji(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
-- SĀKUMA DATI: noklusētās lomas un demonstrācijas stallis
-- =====================================================================
INSERT INTO stalli (nosaukums, adrese, talrunis, epasts)
VALUES ('Demo staļļi', 'Rīga, Latvija', '+37100000000', 'info@demo-stalli.lv');

INSERT INTO lomas (stalla_id, nosaukums, apraksts, tiesibas) VALUES
(1, 'Administrators', 'Pilna piekļuve visai sistēmai',
    '{"zirgi":"edit","barosana":"edit","noliktava":"edit","veseliba":"edit","lietotaji":"edit","kalendars":"edit"}'),
(1, 'Īpašnieks',      'Zirga īpašnieks - redz un pārvalda savus zirgus',
    '{"zirgi":"edit","barosana":"view","noliktava":"view","veseliba":"edit","kalendars":"view"}'),
(1, 'Treneris',       'Pārvalda treniņus un skata zirgu datus',
    '{"zirgi":"view","barosana":"view","veseliba":"view","treninji":"edit","kalendars":"edit"}'),
(1, 'Darbinieks',     'Izpilda barošanas un kopšanas uzdevumus',
    '{"zirgi":"view","barosana":"edit","noliktava":"edit","kalendars":"view"}'),
(1, 'Veterinārs',     'Veselības ierakstu pārvaldība',
    '{"zirgi":"view","veseliba":"edit","dokumenti":"edit"}');