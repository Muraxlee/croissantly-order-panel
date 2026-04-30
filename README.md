# Croissantly Order Panel

Laravel + MySQL dashboard for Croissantly Bakery operations.

## Local Setup

1. Start MySQL from XAMPP.
2. Create the databases:

```powershell
& 'C:\xampp\htdocs\keyauth\mariadb-portable\mariadb-10.6.16-winx64\bin\mysql.exe' -h 127.0.0.1 -P 3306 -u root < database\setup_xampp.sql
```

3. Install dependencies:

```powershell
php -d extension=fileinfo C:\Users\prath\.config\herd-lite\bin\composer.phar install
npm install
```

4. Run migrations and seed demo data:

```powershell
php -d extension=fileinfo -d extension=pdo_mysql artisan migrate:fresh --seed
```

5. Build assets and start:

```powershell
npm run build
.\start-dev.ps1
```

Open `http://127.0.0.1:8000`.

## Demo Logins

- Admin: `admin` / `admin123`
- Client: `client-cafe` / `client123`
- Kitchen: `kitchen` / `kitchen123`
- Employee: `vanessa` / `vanessa123`
- Employee: `eva` / `eva123`
- Employee: `josue` / `josue123`

## Test Command

```powershell
php -d extension=fileinfo -d extension=pdo_mysql vendor\phpunit\phpunit\phpunit
```

The CLI PHP on this machine has `fileinfo` and `pdo_mysql` DLLs available but not enabled globally, so the commands above enable them per run.

## Included Workflows

- Admin order board with approval, live status filters, account creation, staff calendar, and editable menu items.
- Client order form with edit/add-more support until kitchen starts cooking.
- Kitchen production board with item quantities, cook/pack instructions, and `Start Cooking` lock button.
- Employee dashboard with private live slots and timesheet history with break-hour calculation.
