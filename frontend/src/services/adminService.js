import api from './api';

export const adminService = {
  async login(username, password) {
    const res = await api.post('/admin/login.php', { username, password });
    return res.data;
  },

  async getDashboard() {
    const res = await api.get('/admin/dashboard.php');
    return res.data;
  },

  async getUsers(limit = 50, offset = 0) {
    const res = await api.get(`/admin/users.php?limit=${limit}&offset=${offset}`);
    return res.data;
  },

  async toggleUserStatus(userId) {
    const res = await api.post('/admin/users.php', { user_id: userId });
    return res.data;
  },

  async getTransactions(limit = 50, offset = 0) {
    const res = await api.get(`/admin/transactions.php?limit=${limit}&offset=${offset}`);
    return res.data;
  }
};
