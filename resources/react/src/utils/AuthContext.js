// src/utils/AuthContext.js
import React, { createContext, useContext, useState, useEffect } from 'react';
import { getUser, logout } from '../api/auth';

const AuthContext = createContext();

export const useAuth = () => useContext(AuthContext);

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function fetchUser() {
      setLoading(true);
      try {
        const token = localStorage.getItem('token');
        if (token) {
          const res = await getUser(token);
          setUser(res.data);
        } else {
          setUser(null);
        }
      } catch {
        setUser(null);
      }
      setLoading(false);
    }
    fetchUser();
  }, []);

  const handleLogout = async () => {
    await logout();
    setUser(null);
  };

  return (
    <AuthContext.Provider value={{ user, setUser, loading, logout: handleLogout }}>
      {children}
    </AuthContext.Provider>
  );
};
