// src/api/kraken.js
// API utility for Kraken (crypto spot trading)
// Uses backend endpoints for all requests

import api from './index';

export const getKrakenBalance = () => api.get('/kraken/balance');
export const placeKrakenOrder = (pair, type, volume) =>
  api.post('/kraken/order', { pair, type, volume });
