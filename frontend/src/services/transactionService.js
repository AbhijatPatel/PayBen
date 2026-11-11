import api from './api';

export const transactionService = {
  async list(type = 'all', status = 'all', limit = 50, offset = 0) {
    const res = await api.get(`/transactions/list.php?type=${type}&status=${status}&limit=${limit}&offset=${offset}`);
    return res.data;
  },

  async details(id) {
    const res = await api.get(`/transactions/details.php?id=${encodeURIComponent(id)}`);
    return res.data;
  },

  async getDashboard() {
    const res = await api.get('/transactions/dashboard.php');
    return res.data;
  }
};
