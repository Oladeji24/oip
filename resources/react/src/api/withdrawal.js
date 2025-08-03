// src/api/withdrawal.js
// API utility for withdrawal flow
import api from './index';

export const requestWithdrawal = (email, amount, currency) => api.post('/withdrawal/request', { email, amount, currency });
export const confirmWithdrawal = (otp) => api.post('/withdrawal/confirm', { otp });
export const approveWithdrawal = (user_id, amount, currency) => api.post('/withdrawal/approve', { user_id, amount, currency });
