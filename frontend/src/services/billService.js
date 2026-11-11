import api from './api';

export const billService = {
  async getProviders(type = 'electricity') {
    const res = await api.get(`/bills/${type}.php?action=providers`);
    return res.data;
  },

  async fetchBill(type, providerId, consumerNumber) {
    const res = await api.get(`/bills/${type}.php?action=fetch&provider_id=${providerId}&consumer_number=${encodeURIComponent(consumerNumber)}`);
    return res.data;
  },

  async payBill(type, providerId, consumerNumber, amount) {
    const res = await api.post(`/bills/${type}.php`, {
      provider_id: providerId,
      consumer_number: consumerNumber,
      amount
    });
    return res.data;
  }
};
