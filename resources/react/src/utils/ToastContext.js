// src/utils/ToastContext.js
import React, { createContext, useContext, useState, useCallback } from 'react';
import GlobalToast from '../components/GlobalToast';

const ToastContext = createContext();

export const useToast = () => useContext(ToastContext);

export const ToastProvider = ({ children }) => {
  const [toast, setToast] = useState({ show: false, message: '', variant: 'info' });

  const showToast = useCallback((message, variant = 'info') => {
    setToast({ show: true, message, variant });
  }, []);

  const hideToast = useCallback(() => {
    setToast((t) => ({ ...t, show: false }));
  }, []);

  return (
    <ToastContext.Provider value={{ showToast }}>
      {children}
      <GlobalToast show={toast.show} onClose={hideToast} message={toast.message} variant={toast.variant} />
    </ToastContext.Provider>
  );
};
