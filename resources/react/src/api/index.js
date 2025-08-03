// src/api/index.js
// Centralized API utility for backend communication

import axios from 'axios';

const api = axios.create({
  baseURL: process.env.REACT_APP_API_URL || 'http://localhost:8000/api',
  withCredentials: true, // if you use cookies/sessions
});

export default api;

// Usage in other files:
// import api from 'path_to_this_file';
// api.get('/endpoint');
