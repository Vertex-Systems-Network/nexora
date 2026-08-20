# Developer Onboarding

## Local Laragon defaults

```text
Host: 127.0.0.1
Port: 3306
Database: nexora
User: root
Password: root
```

Create/configure the application with:

```bat
composer install
npm install
copy .env.example .env
php artisan key:generate
php scripts\create-mysql-database.php
php artisan migrate:fresh --seed
php artisan nexora:runtime:sync
npm run dev
```

`php scripts\create-mysql-database.php` is cross-platform and creates the configured MySQL database without needing the `mysql` executable in PATH.

For an existing checkout that already has `.env`, make sure its `DB_*` values match the intended MySQL configuration; `.env.example` never overwrites a developer's private `.env`.
