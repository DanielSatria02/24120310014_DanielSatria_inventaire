# Inventaire

This project is set up as a Laravel application. Laravel provides the web
framework, and its Eloquent ORM can be used to work with the MySQL database.
Laravel's query builder (`DB::table(...)`) is also available when you prefer
plain table queries.

## What has been set up

- Installed Laravel 12 in this repository using Composer.
- Enabled PHP's `zip` extension in `C:\xampp\php\php.ini` so Composer could
  download Laravel dependencies.
- Set the local `.env` database connection to MySQL:
  - Database: `inventaire`
  - Host/port: `127.0.0.1:3306`
  - Username: `root`
  - Password: empty (the usual default XAMPP setup)
- Linked `C:\xampp\htdocs\inventaire` to this project's `public` folder using
  a Windows directory junction. The source code stays in this repository;
  Apache serves the public entry point.

The database has not yet been created and migrations have not yet been run.
The `.env` file contains machine-specific settings and should not be committed.

## Requirements

- XAMPP with PHP 8.2 or newer and MySQL/MariaDB
- Composer
- Node.js and npm (needed to build frontend assets)

## Run locally

### Recommended: Laravel development server

Open a terminal in the project directory and run:

```powershell
php artisan serve
```

Then visit <http://127.0.0.1:8000>.

### Through XAMPP Apache

Start Apache in the XAMPP Control Panel, then visit
<http://localhost/inventaire>. The `htdocs\inventaire` junction must point to
this repository's `public` directory.

## Configure MySQL

1. Start MySQL in the XAMPP Control Panel.
2. In phpMyAdmin (<http://localhost/phpmyadmin>), create a database named
   `inventaire` (for example, with the `utf8mb4_unicode_ci` collation).
3. Check the `DB_*` values in `.env` if your MySQL credentials differ from the
   default XAMPP values.
4. From the project directory, create the tables:

   ```powershell
   php artisan migrate
   ```

## Frontend assets

Install the JavaScript dependencies and compile the frontend assets:

```powershell
npm install
npm run build
```

During frontend development, use `npm run dev` in a separate terminal for
Vite's development server.

## Useful Laravel commands

Create a model and its migration:

```powershell
php artisan make:model Product -m
```

Run pending migrations:

```powershell
php artisan migrate
```

Eloquent example:

```php
$products = Product::where('active', true)->get();
```

Query Builder example:

```php
$products = DB::table('products')->where('active', true)->get();
```