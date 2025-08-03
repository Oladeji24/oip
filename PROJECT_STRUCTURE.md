# Project Structure

This document outlines the structure and organization of the OIP Trading Bot project.

## 📁 Root Directory Structure

```
OIP/
├── app/                    # Laravel application logic
├── bootstrap/              # Laravel bootstrap files
├── config/                 # Configuration files
├── controllers/            # Custom controllers (outside Laravel structure)
├── database/               # Database migrations, factories, seeders
├── frontend/               # Standalone React frontend (alternative structure)
├── lang/                   # Language files
├── public/                 # Public web directory
├── resources/              # Views, assets, and React app
├── routes/                 # Route definitions
├── services/               # Business logic services
├── storage/                # File storage and logs
├── tests/                  # Test files
├── vendor/                 # Composer dependencies
├── .env.example            # Environment variables template
├── composer.json           # PHP dependencies
├── package.json            # Node.js dependencies
├── render.yaml             # Render deployment configuration
└── README.md               # Project documentation
```

## 🏗️ Backend Architecture (Laravel)

### Core Application (`app/`)
```
app/
├── Console/
│   ├── Commands/
│   │   └── RunBotTradingLoop.php    # Trading bot command
│   └── Kernel.php
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── AuthController.php
│   │       └── TradingController.php
│   ├── Middleware/                   # Custom middleware
│   └── Kernel.php
├── Mail/                            # Email templates
│   ├── AccountSummary.php
│   └── TradingAlert.php
├── Models/                          # Eloquent models
│   ├── ActiveBot.php
│   ├── AuditLog.php
│   ├── BotTrade.php
│   ├── User.php
│   ├── UserWallet.php
│   └── WalletTransaction.php
└── Providers/                       # Service providers
```

### Custom Controllers (`controllers/`)
```
controllers/
├── AlpacaController.php             # Alpaca API integration
├── AuditLogController.php           # Audit logging
├── AuthController.php               # Authentication
├── BotController.php                # Trading bot management
├── DerivController.php              # Deriv API integration
├── KrakenController.php             # Kraken API integration
├── KuCoinController.php             # KuCoin API integration
├── PayPalController.php             # PayPal integration
├── PaystackController.php           # Paystack integration
├── SendGridController.php           # Email service
├── TransactionController.php        # Transaction management
├── UserController.php               # User management
├── WalletController.php             # Wallet operations
└── WithdrawalController.php         # Withdrawal processing
```

### Business Services (`services/`)
```
services/
├── AlpacaConnector.php              # Alpaca API service
├── BacktestService.php              # Trading backtesting
├── DerivConnector.php               # Deriv API service
├── KrakenConnector.php              # Kraken API service
├── KuCoinConnector.php              # KuCoin API service
├── PayPalConnector.php              # PayPal service
├── PaystackConnector.php            # Paystack service
├── SendGridConnector.php            # SendGrid email service
├── TransactionLogger.php            # Transaction logging
└── UserNotificationService.php      # User notifications
```

### Database Structure (`database/`)
```
database/
├── migrations/
│   ├── 2014_10_12_000000_create_users_table.php
│   ├── 2025_06_13_000001_create_bot_trades_table.php
│   ├── 2025_06_15_000001_create_audit_logs_table.php
│   ├── 2025_06_20_000001_create_user_wallets_table.php
│   ├── 2025_06_20_000002_create_active_bots_table.php
│   ├── 2025_06_20_000005_create_wallet_transactions_table.php
│   └── 2025_06_20_000006_create_price_alerts_table.php
├── factories/                       # Model factories for testing
└── seeders/                         # Database seeders
```

## ⚛️ Frontend Architecture (React)

### Main React App (`resources/react/`)
```
resources/react/
├── public/
│   └── index.html                   # HTML template
├── src/
│   ├── api/                         # API service modules
│   │   ├── auth.js                  # Authentication API
│   │   ├── bot.js                   # Bot management API
│   │   ├── deriv.js                 # Deriv API calls
│   │   ├── kucoin.js                # KuCoin API calls
│   │   ├── paypal.js                # PayPal API calls
│   │   ├── paystack.js              # Paystack API calls
│   │   ├── user.js                  # User management API
│   │   └── wallet.js                # Wallet API calls
│   ├── components/                  # React components
│   │   ├── AdminDashboard.js        # Admin interface
│   │   ├── AuthForm.js              # Authentication forms
│   │   ├── BotStatus.js             # Bot status display
│   │   ├── DemoTrading.js           # Demo trading interface
│   │   ├── MarketDashboard.js       # Market data display
│   │   ├── Navigation.js            # Navigation component
│   │   ├── TraderDashboard.js       # Main trading interface
│   │   └── UserDetailModal.js       # User details modal
│   ├── context/
│   │   └── AuthContext.js           # Authentication context
│   ├── pages/                       # Page components
│   │   ├── AdminPage.js             # Admin page
│   │   ├── AuthPage.js              # Login/Register page
│   │   ├── DemoPage.js              # Demo trading page
│   │   ├── ProfilePage.js           # User profile page
│   │   └── TraderPage.js            # Main trading page
│   ├── utils/                       # Utility functions
│   │   ├── AuthContext.js           # Auth utilities
│   │   ├── botLogic.js              # Bot logic utilities
│   │   └── ToastContext.js          # Toast notifications
│   ├── App.js                       # Main App component
│   ├── index.js                     # React entry point
│   └── index.css                    # Global styles
├── package.json                     # React dependencies
└── .env                             # React environment variables
```

### Alternative Frontend (`frontend/`)
```
frontend/
├── src/                             # Alternative React structure
│   ├── api/                         # API modules (similar to resources/react)
│   ├── components/                  # React components
│   ├── pages/                       # Page components
│   └── utils/                       # Utility functions
└── .github/
    └── workflows/
        └── frontend.yml             # GitHub Actions for frontend
```

## 🔧 Configuration Files

### Environment Configuration
- `.env.example` - Template for environment variables
- `.env` - Local environment variables (not committed)

### Build Configuration
- `package.json` - Node.js dependencies and scripts
- `composer.json` - PHP dependencies
- `vite.config.js` - Vite build configuration
- `render.yaml` - Render deployment configuration

### Development Tools
- `.editorconfig` - Editor configuration
- `.gitignore` - Git ignore rules
- `.gitattributes` - Git attributes
- `phpunit.xml` - PHPUnit testing configuration

## 🚀 Key Features by Directory

### Trading Engine
- **Controllers**: Handle API requests for different exchanges
- **Services**: Business logic for exchange integrations
- **Models**: Data models for trades, bots, and transactions

### User Management
- **Authentication**: Laravel Sanctum-based API authentication
- **Authorization**: Role-based access control (User, Admin, SuperAdmin)
- **Audit Logging**: Complete activity tracking

### Financial Services
- **Wallet System**: Multi-currency wallet management
- **Payment Processing**: Paystack (NGN) and PayPal (International)
- **Withdrawal System**: OTP verification and admin approval

### Frontend Interface
- **React 19**: Modern React with hooks and context
- **Responsive Design**: Bootstrap-based responsive UI
- **Real-time Updates**: Live trading data and notifications
- **Admin Interface**: Comprehensive admin dashboard

## 📊 Data Flow

1. **User Authentication**: React → Laravel API → Database
2. **Trading Operations**: React → Controller → Service → Exchange API
3. **Data Storage**: Exchange API → Service → Controller → Database
4. **Real-time Updates**: Database → API → React Context → Components
5. **Notifications**: Service → Email/SMS → User

## 🔒 Security Features

- **API Authentication**: Laravel Sanctum tokens
- **Input Validation**: Request validation on all endpoints
- **CORS Protection**: Configured for frontend domains
- **Rate Limiting**: API rate limiting for security
- **Audit Logging**: Complete activity tracking
- **Environment Variables**: Sensitive data in environment files

## 🧪 Testing Structure

```
tests/
├── Feature/                         # Feature tests
│   ├── AuthTest.php                 # Authentication tests
│   └── ExampleTest.php              # Example feature tests
├── Unit/                            # Unit tests
│   └── ExampleTest.php              # Example unit tests
├── CreatesApplication.php           # Test application setup
└── TestCase.php                     # Base test case
```

This structure provides a scalable, maintainable architecture for the OIP Trading Bot platform with clear separation of concerns and modern development practices.