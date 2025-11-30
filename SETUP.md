# Expense Tracker API - Setup Instructions

## Project Status
✅ Laravel 12.x installed  
✅ Laravel Sanctum installed  
✅ Export packages installed (DomPDF, Excel, L5-Swagger)  
✅ Environment configured for MySQL  
✅ Sanctum configuration published  

---

## Next Steps to Complete Setup

### 1. Create Database
Run this command (you'll need to enter your MySQL password):
```bash
sudo mysql -u root -e "CREATE DATABASE IF NOT EXISTS expense_tracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Or login to MySQL manually:
```bash
sudo mysql -u root
```
Then run:
```sql
CREATE DATABASE IF NOT EXISTS expense_tracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

### 2. Run Migrations
```bash
cd /var/www/html/SmartBucket/expense-tracker-api
php artisan migrate
```

### 3. Create Migrations for Our Tables
```bash
# Income table
php artisan make:migration create_income_table

# Categories table
php artisan make:migration create_categories_table

# Expenses table
php artisan make:migration create_expenses_table

# Export history table
php artisan make:migration create_export_history_table
```

### 4. Create Models
```bash
php artisan make:model Income
php artisan make:model Category
php artisan make:model Expense
php artisan make:model ExportHistory
```

### 5. Create Controllers
```bash
php artisan make:controller Api/AuthController
php artisan make:controller Api/IncomeController --api
php artisan make:controller Api/ExpenseController --api
php artisan make:controller Api/CategoryController --api
php artisan make:controller Api/DashboardController
php artisan make:controller Api/ExportController
php artisan make:controller Api/SettingsController
```

### 6. Create Form Requests
```bash
php artisan make:request Auth/RegisterRequest
php artisan make:request Auth/LoginRequest
php artisan make:request IncomeRequest
php artisan make:request ExpenseRequest
php artisan make:request CategoryRequest
```

### 7. Create Resources
```bash
php artisan make:resource UserResource
php artisan make:resource IncomeResource
php artisan make:resource ExpenseResource
php artisan make:resource CategoryResource
php artisan make:resource DashboardResource
```

### 8. Create Seeder for Categories
```bash
php artisan make:seeder CategorySeeder
```

### 9. Create Policies
```bash
php artisan make:policy ExpensePolicy --model=Expense
php artisan make:policy IncomePolicy --model=Income
php artisan make:policy CategoryPolicy --model=Category
```

---

## Installed Packages

### Core
- **Laravel Framework**: 12.40.2
- **PHP**: 8.1+ required

### Authentication
- **Laravel Sanctum**: 4.2.1 - API token authentication

### Export Functionality
- **barryvdh/laravel-dompdf**: 3.1.1 - PDF generation
- **maatwebsite/excel**: 3.1.67 - Excel export/import
- **darkaonline/l5-swagger**: 9.0.1 - API documentation

---

## Environment Configuration

The `.env` file has been configured with:

```env
APP_NAME="Expense Tracker API"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=expense_tracker
DB_USERNAME=root
DB_PASSWORD=

SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,127.0.0.1,127.0.0.1:3000
SESSION_DOMAIN=localhost
```

---

## Start Development Server

```bash
cd /var/www/html/SmartBucket/expense-tracker-api
php artisan serve
```

The API will be available at: **http://localhost:8000**

---

## API Routes Structure (To Be Created)

```
/api/auth
  - POST /register
  - POST /login
  - POST /logout

/api/income
  - GET /current
  - POST /
  - PUT /{id}
  - GET /history

/api/expenses
  - GET /
  - POST /
  - GET /{id}
  - PUT /{id}
  - DELETE /{id}
  - GET /summary

/api/categories
  - GET /
  - POST /
  - PUT /{id}
  - DELETE /{id}

/api/dashboard
  - GET /
  - GET /trends
  - GET /category-breakdown

/api/export
  - GET /csv
  - GET /pdf
  - GET /excel
  - GET /history

/api/settings
  - GET /
  - PUT /profile
  - PUT /password
```

---

## Database Schema

Refer to `DATABASE_SCHEMA.md` in the project root for complete database structure.

---

## Project Structure

```
expense-tracker-api/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   ├── Requests/
│   │   ├── Resources/
│   │   └── Middleware/
│   ├── Models/
│   ├── Policies/
│   └── Services/
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
├── routes/
│   └── api.php
└── config/
```

---

## Testing

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/ExpenseTest.php

# Run with coverage
php artisan test --coverage
```

---

## Additional Commands

```bash
# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Generate API documentation
php artisan l5-swagger:generate

# Create symbolic link for storage
php artisan storage:link

# Optimize for production
php artisan optimize
php artisan config:cache
php artisan route:cache
```

---

## CORS Configuration

CORS is configured in `config/cors.php`. For development, you may need to adjust:

```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_origins' => ['http://localhost:3000'],
```

---

## Next Development Steps

1. ✅ Database creation
2. ⏳ Run migrations
3. ⏳ Create migrations for custom tables
4. ⏳ Define Eloquent models with relationships
5. ⏳ Create API controllers
6. ⏳ Define API routes
7. ⏳ Implement authentication
8. ⏳ Create form validation requests
9. ⏳ Implement business logic services
10. ⏳ Add API resources for responses
11. ⏳ Seed default categories
12. ⏳ Write tests
13. ⏳ Generate API documentation

---

## Troubleshooting

### Database Connection Issues
If you get database connection errors:
1. Make sure MySQL is running: `sudo systemctl status mysql`
2. Start MySQL if needed: `sudo systemctl start mysql`
3. Check database exists: `sudo mysql -u root -e "SHOW DATABASES;"`

### Permission Issues
```bash
# Fix storage and cache permissions
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R $USER:www-data storage bootstrap/cache
```

### Sanctum Issues
Make sure to add `EnsureFrontendRequestsAreStateful` to `app/Http/Kernel.php`:
```php
'api' => [
    \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    'throttle:api',
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
],
```

---

## Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Sanctum Documentation](https://laravel.com/docs/sanctum)
- [API Resource Documentation](https://laravel.com/docs/eloquent-resources)
- [L5 Swagger Documentation](https://github.com/DarkaOnLine/L5-Swagger)
