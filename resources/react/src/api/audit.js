// src/api/audit.js
// API utility for audit logs (admin)
import api from './index';

export const getAuditLogs = () => api.get('/audit-logs');
export const logAdminAction = (action, details) => api.post('/audit-logs', { action, details });
