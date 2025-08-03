// src/api/demo.js
// API utility for demo trading actions (virtual funds)
import api from './index';

export const getDemoBalance = () => api.get('/demo/balance');
export const getDemoLogs = () => api.get('/demo/logs');
export const startDemoTrade = (market) => api.post('/demo/start', { market });
export const stopDemoTrade = () => api.post('/demo/stop');
export const switchDemoMarket = (market) => api.post('/demo/switch', { market });
