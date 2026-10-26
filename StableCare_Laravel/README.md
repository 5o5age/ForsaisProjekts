# StableCare

StableCare ir Laravel 13 tīmekļa lietotne zirgu staļļa ikdienas pārvaldībai. Saskarne ir latviešu valodā un izmanto Blade, CSS un JavaScript. Dati tiek glabāti MySQL datubāzē; piekļuves tiesības tiek pārbaudītas servera pusē.

## Funkcijas

- Lietotāju reģistrācija, pieteikšanās, paroles atjaunošana un profila pārvaldība.
- Četras lomas: administrators, uzraugs, treneris un veterinārārsts.
- Zirgu profili, dokumenti, piezīmes un QR kodi.
- Ikdienas uzdevumi ar atkārtošanās grafikiem, izpildes atzīmēm un piezīmēm.
- Barošanas plāni, kalendārs un atkārtotu notikumu pārvietošana.
- Noliktavas atlikumi, patēriņa prognoze, minimālā krājuma brīdinājumi un kustību vēsture.
- Veselības ieraksti, treniņi, lietotāju pārvaldība un atskaites.

## Lomas

| Loma | Galvenās tiesības |
|---|---|
| Administrators | Pārvalda savu stalli, tā lietotājus un datus. |
| Uzraugs | Pārvalda ikdienas aprūpi, uzdevumus, barošanu un noliktavu. |
| Treneris | Pārvalda treniņus un skatās kalendāru un zirgu profilus. |
| Veterinārārsts | Pārvalda veselības ierakstus un dokumentus. |

Reģistrējoties var izveidot jaunu stalli; tā izveidotājs kļūst par administratoru. Pievienojoties esošam stallim, jāievada tā ielūguma kods un piešķiramā loma. Esošam stallim pievienojoties, reģistrētājs nevar pats sev piešķirt administratora lomu.

## Prasības

- PHP 8.3 vai jaunāks ar Laravel nepieciešamajiem paplašinājumiem (`pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `tokenizer`, `xml`, `ctype`).
- Composer 2.
- MySQL 8+ vai saderīga MariaDB.

## Uzstādīšana jaunai videi

1. Izveido MySQL datubāzi un nokopē `.env.example` uz `.env`:

   ```powershell
   Copy-Item .env.example .env
   ```

2. `.env` iestati MySQL savienojumu (`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`) un lietotnes adresi.
3. Uzstādi PHP atkarības un izveido lietotnes atslēgu:

   ```bash
   composer install
   php artisan key:generate
   ```

   `key:generate` izmanto tikai jaunai instalācijai. Esošā vidē nemaini `APP_KEY`.

4. Izveido tabulas ar migrācijām:

   ```bash
   php artisan migrate --seed
   ```

   Sēkla neievieto demo lietotājus vai zirgus. Esošā datubāzē pirms migrēšanas izveido dublējumu un pārbaudi gaidošās migrācijas. Nelieto `migrate:fresh`, `migrate:refresh` vai `db:wipe`, ja jāsaglabā dati. Kalendāra atsevišķu atkārtojumu pārvietošanai vajadzīga migrācija `2026_10_20_000006_create_task_occurrences_table`.

5. Palaid lokālo Laravel serveri:

   ```bash
   php artisan serve
   ```

   Atver `http://127.0.0.1:8000` pārlūkā.

Frontend faili atrodas `public/assets`. Šajā repozitorijā nav `package.json`, tāpēc `npm run dev` nav nepieciešams.

## Testi un maršruti

```bash
php artisan route:list
composer test
```

`composer test` palaiž PHPUnit. Testu datubāze ir SQLite atmiņā, un tai vajadzīgs `pdo_sqlite` PHP paplašinājums.

## E-pasts un paroles atjaunošana

`.env.example` izmanto `MAIL_MAILER=log`; lokāli atiestatīšanas ziņojumi tiek rakstīti `storage/logs/laravel.log`. E-pastu sūtīšanai konfigurē SMTP iestatījumus `.env` failā un izpildi `php artisan config:clear`.

## Publicēšana

Pirms izvietošanas iestati `APP_ENV=production`, `APP_DEBUG=false`, HTTPS un pareizu `APP_URL`. Nodrošini rakstīšanas tiesības `storage/` un `bootstrap/cache/`, konfigurē SMTP un regulāras datubāzes dublējumkopijas. Neiekļauj Git īsto `.env`, paroles vai datubāzes datus.
