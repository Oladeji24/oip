# OIP Trading Bot

A sophisticated automated trading platform that integrates with multiple cryptocurrency and forex exchanges to provide intelligent trading strategies, portfolio management, and comprehensive financial services.

## 🚀 Features

### Trading & Exchange Integration
- **Multi-Exchange Support**: Seamlessly connect with popular exchanges:
  - **KuCoin** - Cryptocurrency trading
  - **Deriv** - Forex and synthetic indices
  - **Kraken** - Cryptocurrency trading
  - **Alpaca** - Stock and forex trading
- **Automated Trading Bots**: Deploy and manage trading bots with customizable strategies
- **Real-time Market Data**: Live price feeds, order books, and trading history
- **Advanced Analytics**: Comprehensive trading performance metrics and reporting

### Financial Services
- **Multi-Currency Wallet System**: Manage balances across different currencies
- **Payment Integration**:
  - **Paystack** - Nigerian Naira (NGN) payments
  - **PayPal** - International payments
- **Secure Withdrawals**: OTP verification and admin approval system
- **Transaction Logging**: Comprehensive audit trail for all financial activities

### User Management & Security
- **Role-Based Access Control**: User, Admin, and Super Admin roles
- **Secure Authentication**: Laravel Sanctum API authentication
- **User Impersonation**: Secure admin impersonation for support
- **Audit Logging**: Complete activity tracking and compliance
- **Email Notifications**: SendGrid integration for alerts and OTP

### Modern Interface
- **React 19 Frontend**: Modern, responsive user interface
- **Real-time Updates**: Live trading data and notifications
- **Mobile-Friendly**: Optimized for all device types
- **Dashboard Analytics**: Comprehensive trading and portfolio insights

## 🛠️ Tech Stack

### Backend
- **Laravel 9**: PHP framework for robust API development
- **PostgreSQL/MySQL**: Database management (Supabase compatible)
- **Laravel Sanctum**: API authentication and authorization
- **Guzzle HTTP**: HTTP client for exchange API integrations
- **Queue System**: Background job processing for trading operations

### Frontend
- **React 19**: Modern JavaScript library with latest features
- **React Bootstrap**: Responsive UI components
- **Chart.js**: Advanced data visualization for trading charts
- **React Router**: Client-side routing
- **Axios**: HTTP client for API requests
- **Context API**: State management

### Infrastructure & Services
- **Render**: Cloud deployment platform
- **Supabase**: PostgreSQL database hosting
- **SendGrid**: Email delivery service
- **Docker**: Containerization support

### External APIs
- **KuCoin API**: Cryptocurrency trading
- **Deriv API**: Forex and synthetic indices
- **Kraken API**: Cryptocurrency trading
- **Alpaca API**: Stock and forex trading
- **Paystack API**: Nigerian payment processing
- **PayPal API**: International payment processing

## 🚀 Getting Started

### Prerequisites

- **PHP 8.0+** with extensions: `pdo`, `mbstring`, `openssl`, `json`, `curl`
- **Composer** (PHP dependency manager)
- **Node.js 16+** and **npm**
- **PostgreSQL** or **MySQL** database
- **Git** for version control

### Quick Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/Oladeji24/oip.git
   cd oip
   ```

2. **Install backend dependencies**
   ```bash
   composer install
   ```

3. **Install frontend dependencies**
   ```bash
   npm install
   cd resources/react && npm install && cd ../../
   ```

4. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configure your environment**
   
   Edit `.env` file with your database and API credentials:
   ```env
   # Database (use PostgreSQL for production)
   DB_CONNECTION=pgsql
   DB_HOST=your-database-host
   DB_PORT=5432
   DB_DATABASE=your-database-name
   DB_USERNAME=your-username
   DB_PASSWORD=your-password
   
   # Exchange API Keys (get from respective platforms)
   KUCOIN_API_KEY=your-kucoin-api-key
   KUCOIN_SECRET_KEY=your-kucoin-secret
   KUCOIN_PASSPHRASE=your-kucoin-passphrase
   
   DERIV_API_KEY=your-deriv-api-key
   KRAKEN_API_KEY=your-kraken-api-key
   KRAKEN_SECRET_KEY=your-kraken-secret
   ALPACA_API_KEY=your-alpaca-api-key
   ALPACA_SECRET_KEY=your-alpaca-secret
   
   # Payment Processors
   PAYSTACK_PUBLIC_KEY=your-paystack-public-key
   PAYSTACK_SECRET_KEY=your-paystack-secret-key
   PAYPAL_CLIENT_ID=your-paypal-client-id
   PAYPAL_CLIENT_SECRET=your-paypal-client-secret
   
   # Email Service
   SENDGRID_API_KEY=your-sendgrid-api-key
   ```

6. **Database setup**
   ```bash
   php artisan migrate
   ```

7. **Build the application**
   ```bash
   npm run build
   npm run react-build
   ```

8. **Start development servers**
   
   **Backend (Terminal 1):**
   ```bash
   php artisan serve
   ```
   
   **Frontend Development (Terminal 2):**
   ```bash
   npm run react-dev
   ```

9. **Access the application**
   - Backend API: `http://localhost:8000`
   - Frontend (development): `http://localhost:3000`
   - Production: `http://localhost:8000` (serves both API and frontend)

## 📚 API Documentation

### Core Endpoints

#### Authentication
- `POST /api/register` - User registration
- `POST /api/login` - User login
- `POST /api/logout` - User logout (authenticated)
- `GET /api/user` - Get current user (authenticated)

#### Trading Operations
- `GET /api/kucoin/balance` - Get KuCoin account balance
- `POST /api/kucoin/order` - Place KuCoin order
- `GET /api/kucoin/ticker` - Get market ticker data
- `GET /api/deriv/account` - Get Deriv account info
- `POST /api/deriv/order` - Place Deriv order

#### Bot Management
- `POST /api/bot/start` - Start trading bot
- `POST /api/bot/stop` - Stop trading bot
- `GET /api/bot/params` - Get bot parameters
- `POST /api/bot/update-params` - Update bot parameters

#### Wallet & Payments
- `GET /api/wallet/balance` - Get wallet balance
- `POST /api/wallet/deposit` - Make deposit
- `POST /api/paystack/initialize` - Initialize Paystack payment
- `POST /api/paypal/create-payment` - Create PayPal payment

#### Admin Functions
- `GET /api/users` - List all users (admin)
- `GET /api/audit-logs` - View audit logs (admin)
- `POST /api/users/{id}/promote` - Promote user (superadmin)

### Authentication

All protected endpoints require a Bearer token in the Authorization header:
```bash
Authorization: Bearer your-api-token
```

## 🔧 Configuration

### Required Environment Variables

```env
# Application
APP_NAME=OIP
APP_ENV=production
APP_KEY=base64:your-generated-key
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database (PostgreSQL recommended for production)
DB_CONNECTION=pgsql
DB_HOST=your-database-host
DB_PORT=5432
DB_DATABASE=your-database-name
DB_USERNAME=your-username
DB_PASSWORD=your-password

# Exchange API Keys
KUCOIN_API_KEY=your-kucoin-api-key
KUCOIN_SECRET_KEY=your-kucoin-secret-key
KUCOIN_PASSPHRASE=your-kucoin-passphrase

DERIV_API_KEY=your-deriv-api-key

KRAKEN_API_KEY=your-kraken-api-key
KRAKEN_SECRET_KEY=your-kraken-secret-key

ALPACA_API_KEY=your-alpaca-api-key
ALPACA_SECRET_KEY=your-alpaca-secret-key

# Payment Processors
PAYSTACK_PUBLIC_KEY=your-paystack-public-key
PAYSTACK_SECRET_KEY=your-paystack-secret-key

PAYPAL_CLIENT_ID=your-paypal-client-id
PAYPAL_CLIENT_SECRET=your-paypal-client-secret

# Email Service
SENDGRID_API_KEY=your-sendgrid-api-key
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="OIP Trading Bot"
```

## 🚀 Deployment

### Render (Recommended)

The application is pre-configured for deployment on Render using the provided `render.yaml` file.

1. **Connect Repository**
   - Fork this repository to your GitHub account
   - Connect your GitHub repository to Render
   - Select "Web Service" and choose this repository

2. **Configure Environment Variables**
   
   In the Render dashboard, add all required environment variables:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_KEY=base64:your-generated-key
   DB_CONNECTION=pgsql
   # ... add all other variables from .env.example
   ```

3. **Deploy**
   - Render will automatically build and deploy your application
   - The build process includes: `composer install`, `npm install`, `npm run build`, `npm run react-build`, and `php artisan migrate`

### Manual Deployment

1. **Prepare for production**
   ```bash
   npm run deploy
   ```

2. **Upload to your server**
   - Upload all files except `node_modules`, `vendor`, and other ignored files
   - Run `composer install --no-dev --optimize-autoloader` on the server
   - Run `npm install && npm run build && npm run react-build` on the server

3. **Configure server**
   - Set up your web server (Apache/Nginx) to point to the `public` directory
   - Configure environment variables
   - Run `php artisan migrate --force`
   - Set proper file permissions

### Docker Deployment

A `Dockerfile` is included for containerized deployment:

```bash
docker build -t oip-trading-bot .
docker run -p 8000:8000 --env-file .env oip-trading-bot
```

## 🤝 Contributing

We welcome contributions! Please follow these steps:

1. **Fork the repository**
2. **Create a feature branch**
   ```bash
   git checkout -b feature/amazing-feature
   ```
3. **Make your changes**
   - Follow PSR-12 coding standards for PHP
   - Use ESLint configuration for JavaScript/React
   - Add tests for new functionality
4. **Commit your changes**
   ```bash
   git commit -m 'Add some amazing feature'
   ```
5. **Push to your branch**
   ```bash
   git push origin feature/amazing-feature
   ```
6. **Open a Pull Request**

### Development Guidelines

- **Backend**: Follow Laravel best practices and PSR standards
- **Frontend**: Use React hooks and functional components
- **Testing**: Write tests for new features
- **Documentation**: Update README and inline documentation
- **Security**: Never commit API keys or sensitive data

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

### Getting Help

- **Issues**: Open an issue on GitHub for bugs or feature requests
- **Discussions**: Use GitHub Discussions for questions and community support
- **Documentation**: Check the [Integration Guide](INTEGRATION_GUIDE.md) for detailed setup instructions

### Common Issues

1. **Database Connection**: Ensure your database credentials are correct in `.env`
2. **API Keys**: Verify all exchange API keys are valid and have proper permissions
3. **Build Errors**: Clear cache with `php artisan cache:clear` and `npm run build`
4. **Permission Issues**: Ensure proper file permissions on `storage` and `bootstrap/cache` directories

### Security

If you discover a security vulnerability, please send an email to the maintainers instead of opening a public issue.

---

**Made with ❤️ for the trading community**

