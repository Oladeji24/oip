// src/api/deriv.js
// API utility for Deriv endpoints
import api from './index';

export const getDerivAccount = () => api.get('/deriv/account');
export const placeDerivOrder = (data) => api.post('/deriv/order', data);
export const getDerivSymbols = () => api.get('/deriv/symbols');
export const getDerivTrades = (symbol) => api.get(`/deriv/trades?symbol=${symbol}`);
export const getDerivSymbolInfo = (symbol) => api.get(`/deriv/symbol?symbol=${encodeURIComponent(symbol)}`);
export const clearDerivSymbolCache = (symbol) => api.post('/deriv/symbol/clear-cache', { symbol });
