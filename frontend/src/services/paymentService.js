import api from './api';

export const paymentService = {
  async createOrder(amount, notes = 'Wallet Load') {
    const res = await api.post('/payments/create-order.php', { amount, notes });
    return res.data;
  },

  async verifyPayment(orderId, action = 'SUCCESS', paymentId = null, signature = null) {
    const res = await api.post('/payments/verify-payment.php', {
      order_id: orderId,
      action,
      payment_id: paymentId,
      signature
    });
    return res.data;
  },

  async getStatus(orderId) {
    const res = await api.get(`/payments/status.php?order_id=${encodeURIComponent(orderId)}`);
    return res.data;
  }
};
