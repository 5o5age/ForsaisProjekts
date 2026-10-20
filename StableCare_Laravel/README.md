# StableCare — pilna zirgu staļļa pārvaldības lietotne

StableCare ir Laravel 13 + MySQL tīmekļa lietotne latviešu valodā ar HTML/Blade, CSS un JavaScript saskarni. Tā nav demo datu makets: formas saglabā datus datubāzē, lietotājiem ir autentifikācija un servera pusē pārbaudītas lomas. Jaunā datubāzē nav iepriekš ievietotu zirgu vai fiktīvu kontu.

## Iekļautās sadaļas

- **Publiskā mājaslapa** — funkciju un lomu apraksts, reģistrācija un pieslēgšanās.
- **Konti** — reģistrācija, pieslēgšanās/iziešana, parole ar hash, pieteikšanās mēģinājumu ierobežošana, paroles atiestatīšana un profila/paroles maiņa.
- **Lomas** — Administrators, Uzraugs, Treneris, Veterinārārsts; piekļuve tiek pārbaudīta servera middleware un kontrolieros.
- **Zirgi** — CRUD, dzimšanas datums, šķirne, dzimums, krāsa, čipa numurs, svars, statuss un piezīmes.
- **Uzdevumi** — CRUD, atbildīgais, zirgs, prioritāte, laiks, datumi un atkārtošanās: vienreiz, katru dienu, P–Pk, S–Sv, atlasītas nedēļas dienas, mēneša intervāli (piem., `1-5, 6-7`).
- **Kalendārs** — mēneša režģis, atkārtoti uzdevumi un katras dienas izpildes atzīme.
- **Barošana** — barošanas plāns rada kalendāra uzdevumu, ko var pabeigt atsevišķi katrā datumā.
- **Noliktava** — krājumu vienības, mērvienības, minimālais atlikums, izvēles derīguma termiņš un kustību žurnāls; atgādinājumi par termiņu tuvākajās 30 dienās. Krājums samazinās tikai tad, kad ar patēriņu saistītu uzdevumu atzīmē kā paveiktu; operācija tiek veikta datubāzes transakcijā.
- **Veselība** — apskates, vakcinācijas, ārstēšana, diagnozes/piezīmes un nākamās vizītes.
- **Treniņi** — grafiks, ilgums, statuss, piezīmes un rezultāts.
- **Lietotāju pārvaldība** — administratoram jaunu lietotāju izveide, lomu maiņa un konta aktivizēšana/deaktivizēšana; pēdējo administratoru nevar noņemt.
- **Staļļa iestatījumi** — staļļa nosaukums/adrese un atjaunojams komandas ielūguma kods.
- **Dokumenti, piezīmes un QR** — veterinārārsts/administrators var augšupielādēt veselības dokumentus privātā glabātuvē; katram zirgam var saglabāt piezīmes un ģenerēt profila QR kodu.
- **Atskaites** — administratoram datumu intervāla pārskats un uzdevumu CSV eksports.
- **Responsīvs dizains** — desktop sānu izvēlne, mobilā izvēlne, formas, kļūdu ziņojumi un tukšie stāvokļi.

## Lomas un atļaujas

| Loma | Atļaujas |
|---|---|
| **Administrators** | Pārvalda visu sistēmu, zirgus, uzdevumus, barošanu, noliktavu, veselību, treniņus un lietotāju lomas. |
| **Uzraugs** | Ikdienas aprūpes uzdevumu izveide/rediģēšana/izpilde, kalendārs, barošanas grafiki un noliktavas uzskaite. |
| **Treneris** | Var pārvaldīt zirgu profilus, skatīt aprūpes kalendāru un izveidot/labot/dzēst treniņu ierakstus. |
| **Veterinārārsts** | Var pārvaldīt zirgu profilus, pievienot/labot/dzēst veselības ierakstus un droši augšupielādēt attiecīgos dokumentus. |

Piezīme par administratora izvēli: drošības dēļ jebkurš anonīms apmeklētājs nevar vienkārši reģistrēties ar administratora tiesībām. Reģistrācijas formā administratora lomai vajadzīgs atsevišķs kods `STABLECARE_ADMIN_INVITE_CODE`. Pēc pirmā administratora izveides izdzēs vai iztukšo šo mainīgo `.env` failā un izpildi `php artisan config:clear`; turpmāk lomas piešķir administrators sadaļā **Lietotāji**.

## Prasības datoram

- PHP **8.3 vai jaunāks** un nepieciešamie Laravel PHP paplašinājumi, tostarp `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `tokenizer`, `xml` un `ctype`.
- Composer 2.
- MySQL 8+ vai saderīga MariaDB.
- VS Code (vai cita PHP koda redaktora programma).

## Uzstādīšana VS Code / Windows

1. Atarhivē `StableCare_Laravel.zip` mapē, piemēram, `C:\Projects\StableCare_Laravel`, un atver šo mapi VS Code.
2. Izveido tukšu MySQL datubāzi. Piemērs MySQL konsolē:

   ```sql
   CREATE DATABASE stablecare CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

   Alternatīvi vari izmantot `database/sql/stablecare_mysql.sql` pilnai shēmas izveidei. **Izvēlies vienu variantu:** parastai Laravel attīstībai izmanto migrācijas; ja importē pilno SQL failu, nelieto migrācijas uz tās pašas shēmas otru reizi.

3. Nokopē `.env.example` uz `.env`. Windows PowerShell:

   ```powershell
   Copy-Item .env.example .env
   ```

4. `.env` failā iestati MySQL savienojumu:

   ```dotenv
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=stablecare
   DB_USERNAME=root
   DB_PASSWORD=tava_mysql_parole
   ```

   Iestati arī sākotnējo administratora ielūguma kodu. Izveido jaunu nejaušu kodu, piemēram, ar `php -r "echo bin2hex(random_bytes(24)), PHP_EOL;"`, un ieraksti rezultātu `STABLECARE_ADMIN_INVITE_CODE` vērtībā. Neievieto šo kodu Git repozitorijā.

5. VS Code terminālī izpildi:

   ```bash
   composer install
   php artisan key:generate
   php artisan config:clear
   php artisan migrate --seed
   ```

   `--seed` apzināti neievieto demo datus. Pēc veiksmīgām migrācijām izveidosies visas tabulas.

6. Sāc lietotni:

   ```bash
   php artisan serve
   ```

   Atver `http://127.0.0.1:8000` pārlūkā.

7. Reģistrācijas lapā izveido pirmo kontu, izvēlies **Administrators** un ievadi ielūguma kodu. Pēc tam iztukšo `STABLECARE_ADMIN_INVITE_CODE` `.env` failā un izpildi `php artisan config:clear`. Pieslēdzies vēlreiz, ja nepieciešams. No sadaļas **Lietotāji** administrators var mainīt pārējo darbinieku lomas.

## E-pasta atiestatīšana

Pēc noklusējuma `.env.example` izmanto `MAIL_MAILER=log`. Tas ir drošs lokāls režīms: paroles atiestatīšanas paziņojums tiek ierakstīts `storage/logs/laravel.log`, nevis nosūtīts īstam lietotājam. Lai paroles atjaunošana darbotos pa e-pastu, iestati sava pasta piegādātāja SMTP vērtības `MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION` un `MAIL_FROM_ADDRESS`. Pēc izmaiņām izpildi `php artisan config:clear`.

## Pārbaudes

Pēc Composer atkarību instalēšanas vari palaist:

```bash
php artisan route:list
php artisan test
```

Komplektā ir sākotnējie automātiskie testi reģistrācijai, administratora koda aizsardzībai un lomu atļaujām. Testu vide izmanto SQLite atmiņā, tādēļ PHP instalācijai vajadzīgs `pdo_sqlite` paplašinājums. Reālās vides DB darbībai izmanto MySQL un pārbaudi arī katras lomas darbības pārlūkā.

## Git / commit

```bash
git init
git add .
git commit -m "feat: izveidota StableCare staļļa pārvaldības sistēma"
```

`.env`, `vendor/` un lokālie build/cache faili ir izslēgti no Git ar `.gitignore`. Commitā iekļauj `.env.example`, nevis īstās paroles vai ielūguma kodu.

## Pirms publicēšanas internetā

1. Iestati `APP_ENV=production`, `APP_DEBUG=false`, drošu `APP_KEY`, HTTPS un pareizu `APP_URL`.
2. Uzstādi lietotni serverī ar Laravel atbilstošu PHP versiju un rakstīšanas tiesībām `storage/` un `bootstrap/cache/` mapēs.
3. Iestati SMTP un veic pārbaudi paroles atiestatīšanai.
4. Izveido datubāzes dublēšanas plānu, veic lomu/piekļuves pārbaudi un pārbaudi `php artisan test` publicēšanas vidē.

## Svarīga piezīme par gatavību

Koda struktūra, migrācijas, kontrolieri, maršruti, skati, stili, skripti un sākotnējie testi ir sagatavoti. Šajā izpildvidē nebija pieejams Composer un tīkla piekļuve atkarību lejupielādei, tādēļ es nevarēju izpildīt `composer install`, Laravel migrācijas vai pilnu HTTP testu komplektu šeit. Pirms to sauc par produkcijā izvietotu sistēmu, izpildi uzstādīšanu savā datorā un novērs visas tur uzrādītās atkarību/vides kļūdas. Nav pievienotu reālu kontu vai demo zirgu.

## Tehnoloģijas

- PHP + Laravel 13 — maršruti, autentifikācija, validācija, datubāzes migrācijas un biznesa loģika.
- MySQL — pastāvīgā datu glabāšana; Eloquent ORM un SQL shēmas skripts.
- HTML / Blade — lapu struktūra un servera puses skati.
- CSS — responsīvs, gaišs StableCare dizains.
- JavaScript — mobilā izvēlne, atkārtošanās formas un lietotāja saskarnes darbības.
