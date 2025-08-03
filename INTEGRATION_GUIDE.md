# Frontend-Backend Integration Guide

This document outlines the steps to integrate the React frontend with the Laravel backend into a single folder structure.

## Step 1: Create React App Directory in Laravel

Create a new directory in the Laravel resources folder to house the React application:

```bash
mkdir -p backend/resources/react
```

## Step 2: Move React Frontend Files

Copy all files from the frontend directory to the new location:

```bash
cp -r frontend/* backend/resources/react/
```

## Step 3: Update Laravel Package.json

Update the Laravel package.json file to include React build scripts and dependencies:

```json
{
  "private": true,
  "type": "module",
  "scripts": {
    "dev": "vite",
    "build": "vite build && npm run build-react",
    "build-react": "cd resources/react && npm run build && cp -r build/. ../../public/",
    "react-dev": "cd resources/react && npm start"
  },
  "devDependencies": {
    "axios": "^1.6.1",
    "laravel-vite-plugin": "^1.0.0",
    "vite": "^5.0.0"
  }
}
```

## Step 4: Update Vite Configuration

Update the vite.config.js file to handle React assets:

```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
});
```

## Step 5: Create API Routes for React

Create API routes in Laravel to serve the React application:

```php
// routes/api.php - Add your API routes here
Route::middleware('auth:sanctum')->group(function () {
    // Protected API routes
});

// routes/web.php - Add a catch-all route to serve React app
Route::get('{path?}', function () {
    return view('app');
})->where('path', '.*');
```

## Step 6: Create a View for React

Create a Blade template to serve the React app:

```php
// resources/views/app.blade.php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>OIP Trading Bot</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @viteReactRefresh
    @vite(['resources/js/app.js'])
</head>
<body>
    <div id="root"></div>
    <script src="{{ asset('js/app.js') }}" defer></script>
</body>
</html>
```

## Step 7: Configure React to Use Laravel API

Update the React app's API configuration to use the Laravel backend:

```javascript
// resources/react/src/api/config.js
const API_URL = process.env.NODE_ENV === 'production' 
  ? '/api' 
  : 'http://localhost:8000/api';

export default API_URL;
```

## Step 8: Update React Package.json

Update the React package.json to include a proxy for local development:

```json
{
  "name": "frontend",
  "version": "0.1.0",
  "private": true,
  "proxy": "http://localhost:8000",
  "dependencies": {
    // existing dependencies
  },
  "scripts": {
    // existing scripts
  }
}
```

## Step 9: Update .gitignore

Add React-specific entries to Laravel's .gitignore file:

```
# React
/public/static
/resources/react/node_modules
/resources/react/build
```

## Step 10: Build and Test

Build the integrated application:

```bash
# Install dependencies
composer install
npm install
cd resources/react && npm install && cd ../../

# Build for production
npm run build

# Or for development
php artisan serve
npm run react-dev
```

## Notes

- For local development, you'll need to run both the Laravel server (`php artisan serve`) and React dev server (`npm run react-dev`)
- For production, only the Laravel server is needed as React will be built and served from the Laravel public directory
- Make sure your API requests from React include the CSRF token for Laravel authentication
