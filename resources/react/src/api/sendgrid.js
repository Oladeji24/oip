// src/api/sendgrid.js
// API utility for SendGrid (email/OTP)
// Uses backend endpoints for all requests

import api from './index';

export const sendEmail = (to, subject, content) =>
  api.post('/sendgrid/send', { to, subject, content });
