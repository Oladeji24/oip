# Contributing to OIP Trading Bot

Thank you for your interest in contributing to the OIP Trading Bot! This document provides guidelines and information for contributors.

## 🤝 How to Contribute

### Reporting Issues

1. **Search existing issues** first to avoid duplicates
2. **Use the issue template** when creating new issues
3. **Provide detailed information**:
   - Steps to reproduce the issue
   - Expected vs actual behavior
   - Environment details (OS, PHP version, Node.js version)
   - Screenshots or error logs if applicable

### Suggesting Features

1. **Check the roadmap** to see if the feature is already planned
2. **Open a feature request** with detailed description
3. **Explain the use case** and why it would be valuable
4. **Consider implementation complexity** and maintenance burden

### Code Contributions

1. **Fork the repository**
2. **Create a feature branch** from `main`
3. **Make your changes** following our coding standards
4. **Write tests** for new functionality
5. **Update documentation** as needed
6. **Submit a pull request**

## 🛠️ Development Setup

### Prerequisites

- PHP 8.0+ with required extensions
- Composer
- Node.js 16+ and npm
- PostgreSQL or MySQL
- Git

### Local Development

1. **Clone your fork**
   ```bash
   git clone https://github.com/your-username/oip.git
   cd oip
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   cd resources/react && npm install && cd ../../
   ```

3. **Set up environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure database and API keys** in `.env`

5. **Run migrations**
   ```bash
   php artisan migrate
   ```

6. **Start development servers**
   ```bash
   # Terminal 1: Backend
   php artisan serve
   
   # Terminal 2: Frontend
   npm run react-dev
   ```

## 📝 Coding Standards

### PHP/Laravel Backend

- **Follow PSR-12** coding standards
- **Use Laravel conventions**:
  - Eloquent models in `app/Models/`
  - Controllers in `app/Http/Controllers/` or `controllers/`
  - Services in `services/` directory
  - Use dependency injection
  - Follow RESTful API principles

- **Code Style**:
  ```php
  <?php
  
  namespace App\Http\Controllers;
  
  use Illuminate\Http\Request;
  use Illuminate\Http\JsonResponse;
  
  class ExampleController extends Controller
  {
      public function index(Request $request): JsonResponse
      {
          // Implementation
          return response()->json(['data' => $data]);
      }
  }
  ```

- **Database**:
  - Use migrations for schema changes
  - Follow Laravel naming conventions
  - Add proper indexes and foreign keys
  - Use Eloquent relationships

### React Frontend

- **Use functional components** with hooks
- **Follow React best practices**:
  - Use Context API for global state
  - Implement proper error boundaries
  - Use proper key props in lists
  - Avoid inline functions in render

- **Code Style**:
  ```javascript
  import React, { useState, useEffect } from 'react';
  import { useAuth } from '../context/AuthContext';
  
  const ExampleComponent = () => {
      const [data, setData] = useState([]);
      const { user } = useAuth();
  
      useEffect(() => {
          // Effect logic
      }, []);
  
      return (
          <div className="example-component">
              {/* Component JSX */}
          </div>
      );
  };
  
  export default ExampleComponent;
  ```

- **Styling**:
  - Use Bootstrap classes for consistency
  - Create custom CSS files for complex components
  - Follow BEM methodology for custom classes

### API Design

- **RESTful endpoints**:
  - `GET /api/resource` - List resources
  - `GET /api/resource/{id}` - Get specific resource
  - `POST /api/resource` - Create resource
  - `PUT /api/resource/{id}` - Update resource
  - `DELETE /api/resource/{id}` - Delete resource

- **Response format**:
  ```json
  {
      "success": true,
      "data": {},
      "message": "Operation successful",
      "errors": []
  }
  ```

- **Error handling**:
  ```json
  {
      "success": false,
      "message": "Validation failed",
      "errors": {
          "field": ["Error message"]
      }
  }
  ```

## 🧪 Testing

### Backend Tests

- **Write feature tests** for API endpoints
- **Write unit tests** for services and utilities
- **Use Laravel testing tools**:
  ```php
  public function test_user_can_create_bot()
  {
      $user = User::factory()->create();
      
      $response = $this->actingAs($user)
          ->postJson('/api/bot/start', [
              'exchange' => 'kucoin',
              'strategy' => 'basic'
          ]);
      
      $response->assertStatus(200)
          ->assertJson(['success' => true]);
  }
  ```

### Frontend Tests

- **Write component tests** using React Testing Library
- **Test user interactions** and state changes
- **Mock API calls** in tests

### Running Tests

```bash
# Backend tests
php artisan test

# Frontend tests
cd resources/react && npm test
```

## 📚 Documentation

### Code Documentation

- **Add PHPDoc comments** to PHP methods
- **Add JSDoc comments** to JavaScript functions
- **Document complex algorithms** and business logic
- **Update README.md** for new features

### API Documentation

- **Document new endpoints** in README.md
- **Include request/response examples**
- **Document authentication requirements**
- **Update Postman collection** if available

## 🔒 Security Guidelines

### General Security

- **Never commit sensitive data** (API keys, passwords)
- **Use environment variables** for configuration
- **Validate all inputs** on both frontend and backend
- **Implement proper authentication** and authorization
- **Use HTTPS** in production

### API Security

- **Validate request data** using Laravel Form Requests
- **Implement rate limiting** for API endpoints
- **Use CORS** properly for frontend access
- **Log security events** for audit purposes

### Frontend Security

- **Sanitize user inputs** before display
- **Use secure HTTP headers**
- **Implement proper error handling**
- **Don't expose sensitive data** in client-side code

## 🚀 Deployment

### Before Deployment

- **Run all tests** and ensure they pass
- **Update version numbers** in relevant files
- **Update CHANGELOG.md** with changes
- **Test in staging environment**

### Deployment Process

1. **Create pull request** to main branch
2. **Code review** by maintainers
3. **Merge after approval**
4. **Automatic deployment** via Render or manual deployment
5. **Monitor application** after deployment

## 📋 Pull Request Guidelines

### Before Submitting

- **Ensure tests pass** locally
- **Update documentation** as needed
- **Follow commit message conventions**
- **Rebase on latest main** branch

### PR Description Template

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing
- [ ] Tests pass locally
- [ ] New tests added for new functionality
- [ ] Manual testing completed

## Checklist
- [ ] Code follows style guidelines
- [ ] Self-review completed
- [ ] Documentation updated
- [ ] No breaking changes (or documented)
```

### Review Process

1. **Automated checks** must pass
2. **Code review** by at least one maintainer
3. **Testing** in review environment
4. **Approval** required before merge

## 🎯 Roadmap and Priorities

### High Priority
- Performance optimizations
- Additional exchange integrations
- Enhanced security features
- Mobile app development

### Medium Priority
- Advanced trading strategies
- Social trading features
- Portfolio analytics
- API rate limiting improvements

### Low Priority
- UI/UX enhancements
- Additional payment methods
- Localization support
- Advanced reporting features

## 💬 Communication

### Channels
- **GitHub Issues**: Bug reports and feature requests
- **GitHub Discussions**: General questions and community support
- **Pull Requests**: Code review and technical discussions

### Response Times
- **Issues**: We aim to respond within 48 hours
- **Pull Requests**: Initial review within 72 hours
- **Security Issues**: Immediate attention (email maintainers)

## 🏆 Recognition

Contributors will be recognized in:
- **README.md** contributors section
- **CHANGELOG.md** for significant contributions
- **GitHub releases** for major features

Thank you for contributing to OIP Trading Bot! 🚀