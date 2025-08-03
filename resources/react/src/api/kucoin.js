// src/api/kucoin.js
// API utility for KuCoin endpoints
import api from './index';

export const getKucoinBalance = () => api.get('/kucoin/balance');
export const placeKucoinOrder = (data) => api.post('/kucoin/order', data);
export const getKucoinTicker = (symbol) => api.get(`/kucoin/ticker?symbol=${symbol}`);
export const getKucoinOrderBook = (symbol) => api.get(`/kucoin/orderbook?symbol=${symbol}`);
export const getKucoinSymbols = () => api.get('/kucoin/symbols');
export const getKucoinTrades = (symbol) => api.get(`/kucoin/trades?symbol=${symbol}`);
export const getKucoinSymbol = (symbol) => api.get(`/kucoin/symbol?symbol=${symbol}`);
export const clearKucoinSymbolCache = (symbol) => api.post('/kucoin/symbol/clear-cache', { symbol });
