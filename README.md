# Shwe Pyi Soe school management system

Laravel 12, PHP 8.3+, Blade, Tailwind CSS 3, Vite and Alpine.js. SQLite is configured for local development; MySQL 8 is supported. The public family portal is free and does not require an account. The separate staff portal uses Laravel Breeze authentication with usernames, hashed passwords, session regeneration, request throttling and an administrator check.

## Local setup

Install PHP 8.3+ with PDO SQLite (or PDO MySQL), mbstring, fileinfo, OpenSSL, XML, DOM and GD; Composer 2; Node.js 22.12+ and npm. On Windows, use `npm.cmd` if PowerShell blocks `npm.ps1`. Restart your terminal after installing PHP/Composer so PATH is refreshed.

```sh
composer install
cp .env.example .env
php artisan key:generate
```

PowerShell can use `Copy-Item .env.example .env` instead of `cp`. Edit these values **before the first seed**:

```dotenv
APP_NAME="Shwe Pyi Soe"
APP_URL=http://localhost:8000
APP_TIMEZONE=Asia/Yangon
ADMIN_PATH=staff
ADMIN_NAME="School Administrator"
ADMIN_USERNAME=schooladmin
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD="your-own-strong-password"
```

Use one unique URL-safe segment for `ADMIN_PATH` (for example `school-office`); avoid public route names. All staff URLs follow this prefix. The login URL defaults to **http://localhost:8000/staff/login**. There are no staff links on public pages and no public registration, password-reset, or default `/login` routes. Staff responses, including authentication redirects and errors, send `X-Robots-Tag: noindex, nofollow`. The robots route disallows the staff prefix; no sitemap is generated. The prefix is not a substitute for authentication.

```sh
php artisan migrate --seed
php artisan storage:link
npm install
npm run build
php artisan serve
```

Open http://localhost:8000. `migrate --seed` creates the initial admin, KG–Grade 12, A/B classes, subjects, exactly four examinations, and school settings. **It does not create demo students or teachers.** If `ADMIN_PASSWORD` is blank, the first seed generates a random password and prints it once; save it immediately. The existing local workspace's seeded account credentials are recorded in its ignored `.env` file. Never commit that file.

For frontend development use `npm run dev` in a second terminal. Core features work with `php artisan serve`; no queue worker, Redis, mail server or WebSocket server is needed. The generated production assets must exist, or Vite must be running.

## Structure versus fictional demo data

```sh
php artisan db:seed
php artisan db:seed --class=SampleDataSeeder
```

The first command seeds admin + structure only. The second also creates **52 clearly fictional students, 26 sample teachers, 26 head-teacher assignments, all four sets of marks, attendance, 52 fee records, 6 notices, and weekly class timetables**. Initials avatars work without internet; real portraits are uploaded through staff forms. Seeders can be rerun without duplicating structure or sample records. Structure seeding does not overwrite an existing admin password or school settings. Demo seeding updates sample marks, timetables and head assignments; use it only in development.

To start clean before entering real data, use a fresh database and `migrate --seed`. `migrate:fresh --seed` **deletes every existing database table** and is only appropriate for an expendable local demo database. Do not run it against live records.

## Staff workflows

Type `/staff/login` (or your configured prefix) directly, and sign in with `ADMIN_USERNAME` / the password saved during seeding. Settings lets the signed-in administrator change the username, email and password, as well as school information. Saving settings requires the current password. New passwords require 12 characters, mixed case and a number. There is deliberately no public account registration or password reset endpoint.

- **Students:** search and filter by grade/class; add, edit, deactivate or delete students; upload JPG/PNG/WebP portraits up to 3 MB. Parent contact, birth date, address and individual fee status appear only here, inside authentication. Deletion removes related marks, attendance and fees. Deactivation retains records but removes the student from the public portal and active attendance/marks lists.
- **Teachers:** add/edit/delete teachers and portraits; assign or clear a head teacher for each class. Deleting a teacher clears that teacher's class and timetable assignments.
- **Attendance:** choose class/date, explicitly select present/late/absent, then save. Saving a date edits the existing record. Present and late count as attended; the denominator is recorded days, not calendar days. Class summaries average the percentages of students with attendance records.
- **Marks:** choose one of the four exam chips and a class; enter whole-number scores from 0–100. Save to recalculate results. A blank cell removes that score. KG–Grade 9 use Myanmar, English, Mathematics, Science and Social Studies. Grades 10–12 use Myanmar, English, Mathematics, Physics, Chemistry and Biology. These defaults live in `SchoolReport::subjects` and can be adapted with the school's confirmed curriculum.
- **School Fees:** grades run from Grade 12 down to KG. Expand a student's editor, enter amount due/paid, payment method, date and note, and save. Mark Paid sets the paid amount equal to the amount due. Undo clears the payment to zero, its method and date; it does not restore a previous partial amount. Choose Cash, KBZPay, WavePay or Bank for a nonzero payment. Overpayments are rejected. The model stores one current fee balance per student, as requested; it is not a payment transaction ledger. All school and grade totals include active and inactive retained student records. “Unpaid” totals the full due amounts on records with zero payment; “left” includes outstanding partial balances as well.
- **Announcements:** enter English and Burmese titles/bodies, category, event date, bilingual When/For who/What to do text, optional grade targets, pin and NEW-until date. Leave target grades unselected for all grades. A future Publish at value schedules visibility without a queue. The public grade filter includes all-grade notices and notices aimed at the selected grade.
- **Settings:** update school name, address, phone and Facebook URL. The phone is intentionally blank until the school supplies a confirmed number. Staff timestamps use `APP_TIMEZONE`, default Asia/Yangon.

Grades/classes and the four exams are structural records. Timetables are seeded class/weekday/period records; changes to curriculum or timetable structure can be made through the models/seeders. The requested staff sidebar has no separate timetable editor.

## Public portal and calculations

Separate routes: `/`, `/announcements`, `/grades`, `/grades/{id}`, `/students/{id}`, `/teachers`, `/top-achievers`, `/overview`.

English / မြန်မာ is stored in session and a one-year encrypted cookie. Interface translations are in `lang/en` and `lang/my`; data fields use `_en`/`_my`. Free-text notice key-information fields accept bilingual content. User-entered teacher subjects/qualifications are displayed as entered. Burmese uses Noto Sans Myanmar, Latin headings use Manrope. Google Fonts is optional: system fonts are fallbacks.

Exams always appear in school-year order **July → October → December → February (Final)**, with December selected by default. Totals, averages and ranks are never stored. Only students with every required subject recorded receive a class rank or a Top badge. Equal complete totals share a competition rank (1, 1, 3). Every tied class leader appears in Top Achievers. Classes are ordered Grade 12 down to KG, section A then B. Scores shown for incomplete results are partial sums, marked as incomplete on the individual page. Public queries explicitly select safe student/teacher columns; guardian details, birth dates, addresses, teacher phone numbers and fee records are not loaded for public display.

Desktop uses a fixed sidebar; mobile uses an Alpine drawer. Wide tables scroll within their cards rather than widening the page. The school crest and illustration are local SVG; avatars do not depend on remote portrait services.

## Verification

```sh
php artisan test --compact
npm run build
```

Feature tests cover anonymous access and privacy-safe queries, hidden and authenticated staff routes, rejection of wrong passwords and non-admin accounts, login throttling, public registration being unavailable, exam switching, ties/incomplete results, fee totals/payments/undo, attendance/marks validation, notice targeting and language persistence. Tests use an isolated in-memory SQLite database and fictional seeded fixtures.

With a seeded local app running on port 8000:

```sh
npm run test:browser
```

Browser checks use installed Chrome on Windows and test public/staff pages at **1440px and 390px**, bilingual public navigation, drawer interaction, absence of staff links, JavaScript errors and horizontal overflow. Set `CHROME_PATH` to another Chromium executable and `APP_URL` if needed. Browser login reads local `.env` credentials or explicit `ADMIN_USERNAME` / `ADMIN_PASSWORD` process variables; supply the current password if it was changed through Settings. Screenshots are saved to ignored `test-results/`.

## Shared hosting deployment

1. Use hosting with PHP 8.3+ and the extensions above, MySQL 8, HTTPS, and rewrite support. Create a fresh database. Back up any existing live database and uploaded files before deploying updates.
2. Build locally with `npm ci && npm run build`. Upload the project **including `public/build`**, and install dependencies with `composer install --no-dev --prefer-dist`. Node is not required on the host after assets have been built.
3. Keep application code, `.env`, `vendor`, database files and storage **outside the web root**. Point the domain document root to this application's `public/` directory. If the host fixes `public_html`, put only the contents of `public/` there, adjust its `index.php` bootstrap paths to the private application directory, and preserve `.htaccess`. Never expose the whole project directory.
4. Configure `.env`: `APP_ENV=production`, `APP_DEBUG=false`, the HTTPS `APP_URL`, `SESSION_SECURE_COOKIE=true`, `DB_CONNECTION=mysql`, database host/name/user/password, and initial admin values. Use a unique staff prefix and a strong password. Run `php artisan key:generate` only on the initial installation; retain the same app key on updates.
5. Make `storage/` and `bootstrap/cache/` writable by PHP with the least required permissions. Run `php artisan migrate --seed --force`, then `php artisan storage:link`. If symlinks are forbidden, configure a public upload disk supported by the host rather than exposing private storage. Preserve uploaded portraits on subsequent deployments.
6. Run `php artisan config:cache`, `php artisan route:cache`, `php artisan view:cache`. When changing `ADMIN_PATH`, clear/rebuild these caches. Test public navigation, direct staff login and an upload over HTTPS.
7. Production uses `DatabaseSeeder` only. Do not run `SampleDataSeeder` on live data. Keep routine database and `storage/app/public` backups. Core operations need no scheduled jobs or background processes.

School: Shwe Pyi Soe Private High School (ရွှေပြည်စိုးကိုယ်ပိုင်အထက်တန်းကျောင်း), No.18 Si Thu St., corner of Pearl Kan St., Ward 65, South Dagon Township, Yangon, Myanmar. Facebook is configured in Settings. The school year runs June–February.
