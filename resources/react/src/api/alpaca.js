// src/api/alpaca.js
// API utility for Alpaca (forex spot trading)
// Uses backend endpoints for all requests

import api from './index';

export const getAlpacaAccount = () => api.get('/alpaca/account');
export const placeAlpacaOrder = (symbol, qty, side, type, time_in_force) =>
  api.post('/alpaca/order', { symbol, qty, side, type, time_in_force });
