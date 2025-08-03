// src/api/paystack.js
// API utility for Paystack (NGN payments)
// Uses backend endpoints for all requests

import api from './index';

export const initializePaystackTransaction = (email, amount) =>
  api.post('/paystack/initialize', { email, amount });
