// src/api/paypal.js
// API utility for PayPal (international payments)
// Uses backend endpoints for all requests

import api from './index';

export const createPayPalPayment = (amount, currency) =>
  api.post('/paypal/create-payment', { amount, currency });
