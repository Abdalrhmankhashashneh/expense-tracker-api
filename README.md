# SmartBucket - Expense Tracker API

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-11-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 11">
  <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/License-MIT-green?style=for-the-badge" alt="MIT License">
</p>

A robust RESTful API for personal expense tracking and budget management, built with Laravel 11.

## ✨ Features

- 🔐 **Authentication** - Secure token-based auth with Laravel Sanctum
- 💰 **Expense Management** - Full CRUD operations with filtering & pagination
- 💵 **Income Tracking** - Monthly income management with history
- 📂 **Categories** - Customizable expense categories with icons & colors
- 📊 **Dashboard Analytics** - Spending insights, trends, and breakdowns
- 📤 **Export** - Export data to CSV, PDF, or Excel
- 🌐 **Multi-language** - English and Arabic support
- 🔒 **Role-based Access** - Permissions with Spatie Laravel Permission
- 📖 **API Documentation** - Swagger/OpenAPI documentation

## 🚀 Quick Start

### Prerequisites

- PHP 8.2+
- Composer
- MySQL 8.0+
- Node.js 18+ (for frontend assets)

### Installation

1. **Clone the repository**
```bash
git clone https://github.com/Abdalrhmankhashashneh/expense-tracker-api.git
cd expense-tracker-api
```

2. **Install dependencies**
```bash
composer install
npm install
```

3. **Environment setup**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Configure database** in `.env`
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=expense_tracker
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

5. **Run migrations and seeders**
```bash
php artisan migrate
php artisan db:seed
```

6. **Start the server**
```bash
php artisan serve
```

The API will be available at `http://localhost:8000/api`

## 📚 API Endpoints

### Authentication
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/register` | Register new user |
| POST | `/api/auth/login` | Login user |
| POST | `/api/auth/logout` | Logout user |
| GET | `/api/auth/user` | Get authenticated user |

### Expenses
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/expenses` | List all expenses |
| POST | `/api/expenses` | Create expense |
| GET | `/api/expenses/{id}` | Get expense details |
| PUT | `/api/expenses/{id}` | Update expense |
| DELETE | `/api/expenses/{id}` | Delete expense |
| GET | `/api/expenses/summary` | Get expense summary |

### Income
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/income` | List all income records |
| POST | `/api/income` | Create income |
| GET | `/api/income/current` | Get current month income |
| GET | `/api/income/history` | Get income history |
| PUT | `/api/income/{id}` | Update income |
| DELETE | `/api/income/{id}` | Delete income |

### Categories
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/categories` | List all categories |
| POST | `/api/categories` | Create category |
| GET | `/api/categories/{id}` | Get category details |
| PUT | `/api/categories/{id}` | Update category |
| DELETE | `/api/categories/{id}` | Delete category |

### Dashboard
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/dashboard` | Get dashboard overview |
| GET | `/api/dashboard/trends` | Get spending trends |
| GET | `/api/dashboard/category-breakdown` | Get category breakdown |

### Export
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/export/csv` | Export as CSV |
| GET | `/api/export/pdf` | Export as PDF |
| GET | `/api/export/excel` | Export as Excel |
| GET | `/api/export/history` | Get export history |

### Balance
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/balance` | Get current balance |
| POST | `/api/balance/add` | Add money to balance |
| GET | `/api/balance/transactions` | Get transaction history |
| GET | `/api/balance/sources` | Get available transaction sources |

### Debts
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/debts` | List all debts |
| POST | `/api/debts` | Create debt |
| GET | `/api/debts/{id}` | Get debt details |
| PUT | `/api/debts/{id}` | Update debt |
| DELETE | `/api/debts/{id}` | Delete debt |
| GET | `/api/debts/statistics` | Get debt statistics |
| POST | `/api/debts/{id}/payments` | Record debt payment |
| GET | `/api/debts/{id}/payments` | Get debt payment history |

### Lending
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/lendings` | List all lendings |
| POST | `/api/lendings` | Create lending |
| GET | `/api/lendings/{id}` | Get lending details |
| PUT | `/api/lendings/{id}` | Update lending |
| DELETE | `/api/lendings/{id}` | Delete lending |
| POST | `/api/lendings/{id}/payments` | Record lending payment |
| GET | `/api/lendings/{id}/payments` | Get lending payment history |
| DELETE | `/api/lendings/{id}/payments/{payment}` | Delete lending payment |
| POST | `/api/lendings/{id}/forgive` | Forgive lending |

### Targets (Savings Goals)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/targets` | List all targets |
| POST | `/api/targets` | Create target |
| GET | `/api/targets/{id}` | Get target details |
| PUT | `/api/targets/{id}` | Update target |
| DELETE | `/api/targets/{id}` | Delete target |
| POST | `/api/targets/{id}/purchase` | Purchase target item |

### Currencies
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/currencies` | List all currencies |
| GET | `/api/currencies/default` | Get default currency |
| GET | `/api/currencies/active` | Get active currencies |
| PUT | `/api/currencies/set` | Set user currency preference |

### Settings
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/settings` | Get user settings |
| PUT | `/api/settings/profile` | Update profile |
| PUT | `/api/settings/password` | Change password |

## 🔧 Configuration

### CORS
Configure allowed origins in `config/cors.php`:
```php
'allowed_origins' => [
    env('FRONTEND_URL', 'http://localhost:5173'),
],
```

### Sanctum
Token expiration can be configured in `config/sanctum.php`.

## 📖 API Documentation

Swagger documentation is available at `/api/documentation` when running locally.

Generate/update docs:
```bash
php artisan l5-swagger:generate
```

## 🧪 Testing

```bash
php artisan test
```

## 📁 Project Structure

```
app/
├── Http/
│   ├── Controllers/Api/    # API Controllers
│   ├── Middleware/         # Custom middleware
│   ├── Requests/           # Form requests
│   └── Resources/          # API Resources
├── Models/                 # Eloquent models
├── Policies/               # Authorization policies
└── Providers/              # Service providers

database/
├── migrations/             # Database migrations
├── seeders/               # Database seeders
└── factories/             # Model factories

routes/
└── api.php                # API routes
```

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👤 Author

**Abdalrhman Khashashneh**

- GitHub: [@Abdalrhmankhashashneh](https://github.com/Abdalrhmankhashashneh)
