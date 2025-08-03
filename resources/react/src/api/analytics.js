// src/api/analytics.js
// API utility for user trade analytics
import api from './index';

export const getUserAnalytics = () => api.get('/analytics');
export const getUserAnalyticsByMarket = (market) => api.get(`/analytics?market=${encodeURIComponent(market)}`);
