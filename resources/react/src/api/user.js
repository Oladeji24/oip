// src/api/user.js
// API utility for user management (admin)
import api from './index';

export const getUsers = () => api.get('/users');
export const getUser = (id) => api.get(`/users/${id}`);
export const flagUser = (id, status) => api.post(`/users/${id}/flag`, { status });
export const updateProfile = async (data) => {
  const token = localStorage.getItem('token');
  const res = await fetch('/api/user/profile', {
    method: 'PATCH',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`,
    },
    body: JSON.stringify(data),
  });
  if (!res.ok) throw new Error((await res.json()).message || 'Profile update failed');
  return res.json();
};
export const promoteUser = (id) => api.post(`/users/${id}/promote`);
export const demoteUser = (id) => api.post(`/users/${id}/demote`);
export const updateUser = (id, data) => api.patch(`/users/${id}`, data);
export const impersonateUser = (id) => api.post(`/users/${id}/impersonate`);
