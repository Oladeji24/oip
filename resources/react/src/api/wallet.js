// src/api/wallet.js
// API utility for wallet management
import api from './index';

export const getWalletBalance = () => api.get('/wallet/balance');
export const getWalletLogs = () => api.get('/wallet/logs');
export const deposit = (amount, currency) => api.post('/wallet/deposit', { amount, currency });
export const withdraw = (amount, currency) => api.post('/wallet/withdraw', { amount, currency });
