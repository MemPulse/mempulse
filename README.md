# MemPulse

MemPulse is a PHP and MySQL study app for organizing flashcard decks and practicing with spaced repetition. It also includes account registration and login, study-session tracking, reports, and a public landing page.

## Project layout

- `landing-page/` contains the static public website.
- `authentication/` contains registration, login, and password-reset pages.
- `app/` contains the learner dashboard, flashcards, practice, and reports.
- `admin/` contains administrative pages and services.
- `config/` contains application and database configuration.

## Run locally

1. Install PHP, MySQL or MariaDB, and Composer. A local PHP stack such as XAMPP can provide PHP and MySQL.
2. Clone or place the project in your local web server's document root.
3. From the project root, install the PHP dependencies:

   ```sh
   composer install
   ```

4. Create the MemPulse database and tables by importing `seed_mempulse.sql` into your MySQL or MariaDB server.
5. Configure the database connection with the `MEMPULSE_DB_HOST`, `MEMPULSE_DB_USER`, `MEMPULSE_DB_PASSWORD`, and `MEMPULSE_DB_NAME` environment variables. They default to the local XAMPP settings (`localhost`, `root`, an empty password, and `mempulse_db`).
6. Open the landing page at `/mempulse/landing-page/` or the login page at `/mempulse/authentication/login.php`, adjusting `mempulse` if you chose a different folder name.

The password-reset feature has a separate schema at `authentication/password_reset_schema.sql`; apply it if you are setting up that feature.

## Public hosting

GitHub Pages can host the static landing page, but it does not execute PHP or connect to MySQL. The root `index.html` redirects visitors to `landing-page/`. The authenticated application and password-reset endpoints need a separate PHP-capable web host with HTTPS and a configured database.

To enable password resets:

1. Deploy the PHP application to a PHP-capable host and provision a MySQL or MariaDB database. The current app URLs expect the project to be served under `/mempulse/`; use a backend base URL such as `https://your-app-host.example/mempulse`.
2. Import `seed_mempulse.sql` and `authentication/password_reset_schema.sql` into that database.
3. Configure the backend environment variables `MEMPULSE_DB_HOST`, `MEMPULSE_DB_USER`, `MEMPULSE_DB_PASSWORD`, and `MEMPULSE_DB_NAME` with the production database credentials.
4. Configure SMTP using `MEMPULSE_SMTP_HOST`, `MEMPULSE_SMTP_PORT`, `MEMPULSE_SMTP_USERNAME`, `MEMPULSE_SMTP_PASSWORD`, `MEMPULSE_SMTP_FROM_EMAIL`, and `MEMPULSE_SMTP_ENCRYPTION` (`tls`, `ssl`, or `none`). `MEMPULSE_SMTP_FROM_NAME` is optional and defaults to `MemPulse`.
5. Set `MEMPULSE_PUBLIC_URL` to the backend base URL, for example `https://your-app-host.example/mempulse`. Reset emails use this value to link to the PHP endpoint at `/authentication/password-reset.php`; do not set it to the GitHub Pages URL.
6. In `landing-page/app-links.js`, set `APP_BASE_URL` to the same backend base URL with a trailing slash, for example `https://your-app-host.example/mempulse/`. This points the landing-page Login buttons to the PHP application instead of a nonexistent `.php` page on GitHub Pages.

The reset form returns the same confirmation whether or not an active account matches the submitted email. A password changes only after the account owner opens the emailed, single-use token link, which expires after 30 minutes. Test the flow on the PHP host and confirm email delivery before directing users to it.

Never commit production database or SMTP credentials. Configure them as environment variables in the PHP host's deployment settings.
