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

The `inventaire` database exists and migrations are run with
`php artisan migrate`.
The `.env` file contains machine-specific settings and should not be committed.

## Companies, roles and the manager dashboard

- Registering creates a **company** (you type its name) and a **manager**
  account that belongs to it. Google sign-in does the same for new emails,
  using "{Name}'s Company" as the default name.
- Every user has a `role` (`manager` or `employee`). Employees will be added
  by managers later.
- Products and stock movements belong to a company, so one company never sees
  another company's data.
- `/dashboard` is protected by login plus the `role:manager` middleware
  (employees get 403). It shows:
  - **Revenue**: sum of `totalAmount` of outbound movements.
  - **Expenses**: sum of `totalAmount` of inbound movements.
  - **Inventory valuation**: sum of `stockQuantity x purchasePrice`.
  - A searchable product list and an **Add item** popup (validated on the
    server; the SKU must be unique inside the company).
- The header menu (top left) opens a sliding drawer with Dashboard, Edit
  account, Add employee and Log out. The page you are on is highlighted and
  not clickable. Edit account is a placeholder for now.
- **Employee home** (`/home` for employees): same three summary cards and
  searchable product list as the manager sees, but no Add item and no Add
  employee. Each product has **Update stock**, which records a movement:
  - *Shipped out / sold*: stock goes down and the amount (quantity x the
    product's selling price) is added to **revenue**.
  - *Restocked / purchased*: stock goes up and the amount (quantity x the
    purchase price the employee types) is added to **expenses**.
  - Shipping more than is in stock is rejected and nothing changes. The stock
    row is locked inside a database transaction, so two employees cannot
    oversell the same item.
  Employees only see their own company's products and cannot change anything
  else about them.
- Managers can **change an employee's password** (they must enter their own
  password; the employee is signed out everywhere).
- **Employees** (`/employees`): managers add an employee (name, email,
  password) through a popup. The account is created with the `employee` role
  in the manager's company. To delete one, the manager must re-enter their
  own password. Only employees of the same company can be deleted, and not
  ones that already have recorded stock movements (to keep the ledger intact).
- **Password policy** (registration and employees), following OWASP/ASVS:
  12 to 64 characters, no forced symbol or case rules, no leading or trailing
  space, and not the same as the email. Passwords are stored hashed.
- Amounts show as "Rp". Change `app/Support/Money.php` to use another currency.
- New migrations: `companies`, plus company/role on users and company/prices on
  products and stock movements.

## Application architecture

The path `app/Http/Controllers` is Laravel's conventional location for HTTP
controllers. `Http` and `Controllers` are nested directories, and the matching
PHP namespace is `App\Http\Controllers`. This is a framework path, not a
complete architecture on its own.

The project keeps Laravel's normal entry points while organizing inventory
code into Clean Architecture-style layers:

```text
app/
  Application/
    Inventory/                  Inventory use cases and application workflows
  Domain/
    Inventory/                  Inventory business rules and domain types
  Http/
    Controllers/
      Inventory/                Laravel controllers (HTTP adapters)
  Infrastructure/
    Persistence/
      Eloquent/                 Eloquent models and persistence implementations
  Models/                       Eloquent models (User, Category, Product, StockMovement)
database/
  migrations/                   Database schema changes
resources/
  views/
    layouts/                    Shared Blade layouts
    pages/                      Blade page templates
routes/
  web.php                       Web URLs mapped to controller actions
```

Keep the domain independent of Laravel, HTTP, and database details. Application
use cases coordinate domain behavior through interfaces; infrastructure
implements those interfaces using Eloquent/MySQL. Controllers translate web
requests into use-case calls and return views or other responses. This keeps
framework and persistence details at the edges. The folders are scaffolding;
inventory-specific rules and classes should be added when their requirements
are known. Laravel's default `app/Models/User.php` remains in place.

## Requirements

- XAMPP with PHP 8.2 or newer and MySQL/MariaDB
- Composer
- Node.js and npm (needed to build frontend assets)

## Run locally

### Recommended: Laravel development server

Open two terminals in the project directory. In the first, start Laravel:

```powershell
php artisan serve
```

In the second, start Vite so frontend assets are rebuilt as you edit:

```powershell
npm run dev
```

Keep both terminals running, then visit <http://127.0.0.1:8000>. To stop either
server, press `Ctrl+C` in its terminal. For a one-time production-style asset
build instead of Vite's development server, run `npm run build`.

The login page is at `/login` and the registration page is at `/register`.
Registration currently creates a basic account; company setup, invitations,
and manager/employee roles are not implemented yet.

### Google sign-in

Login and registration also offer **Continue with Google**. Google returns a
verified email: if it already belongs to an Inventaire account, the user is
signed into that account; otherwise, a new account is created automatically.
The Google button works after OAuth credentials are added to `.env`.

To configure local development:

1. Create a Google OAuth client ID in Google Cloud Console (OAuth client type:
   **Web application**).
2. Add `http://127.0.0.1:8000/auth/google/callback` as an authorized redirect
   URI for the client.
3. Add the client ID and secret to `.env`:

   ```env
   GOOGLE_CLIENT_ID=your-client-id
   GOOGLE_CLIENT_SECRET=your-client-secret
   GOOGLE_REDIRECT_URI=http://127.0.0.1:8000/auth/google/callback
   ```

4. Run `php artisan config:clear` if the server was already running, then try
   the Google button. Keep the OAuth secret private and never commit `.env`.

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

## Running tests

Tests also use MySQL, in a separate `inventaire_test` database so your real
data is never touched. Create it once, then run the suite:

```powershell
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS inventaire_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
php artisan test
```

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