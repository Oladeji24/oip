// src/context/AuthContext.js
import React, { createContext, useState, useEffect, useContext } from 'react';
import { login as apiLogin, register as apiRegister, logout as apiLogout, getUser } from '../api/auth';

const AuthContext = createContext();

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [token, setToken] = useState(localStorage.getItem('token'));
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (token) {
      getUser(token)
        .then(res => setUser(res.data))
        .catch(() => setUser(null));
    }
    setLoading(false);
  }, [token]);

  const login = async (email, password) => {
    const res = await apiLogin(email, password);
    setToken(res.data.access_token);
    localStorage.setItem('token', res.data.access_token);
    setUser(res.data.user);
  };

  const register = async (name, email, password) => {
    const res = await apiRegister(name, email, password);
    setToken(res.data.access_token);
    localStorage.setItem('token', res.data.access_token);
    setUser(res.data.user);
  };

  const logout = async () => {
    await apiLogout(token);
    setToken(null);
    setUser(null);
    localStorage.removeItem('token');
  };

  return (
    <AuthContext.Provider value={{ user, token, login, register, logout, loading }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => useContext(AuthContext);
