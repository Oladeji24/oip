// src/api/auth.js
// Authentication API
import axios from 'axios';

const API_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000/api';

export const register = (name, email, password) =>
  axios.post(`${API_URL}/register`, { name, email, password });

export const login = (email, password) =>
  axios.post(`${API_URL}/login`, { email, password });

export const logout = (token) =>
  axios.post(`${API_URL}/logout`, {}, {
    headers: { Authorization: `Bearer ${token}` }
  });

export const getUser = (token) =>
  axios.get(`${API_URL}/user`, {
    headers: { Authorization: `Bearer ${token}` }
  });
