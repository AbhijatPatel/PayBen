import api from './api';

export const walletService = {
  async getBalance() {
    const res = await api.get('/wallet/balance.php');
    return res.data;
  },

  async addMoney(amount, paymentMethod = 'UPI') {
    const res = await api.post('/wallet/add-money.php', { amount, payment_method: paymentMethod });
    return res.data;
  },

  async transfer(recipient, amount, note = '') {
    const res = await api.post('/wallet/transfer.php', { recipient, amount, note });
    return res.data;
  },

  async scratchCard() {
    const res = await api.post('/wallet/scratch-card.php');
    return res.data;
  },

  async getHistory(limit = 20, offset = 0) {
    const res = await api.get(`/wallet/history.php?limit=${limit}&offset=${offset}`);
    return res.data;
  }
};

