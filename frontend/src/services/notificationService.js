import api from './api';

export const notificationService = {
  async getNotifications(limit = 20) {
    const res = await api.get(`/notifications/list.php?limit=${limit}`);
    return res.data;
  },

  async markAsRead(notificationId = null) {
    const res = await api.post('/notifications/read.php', {
      notification_id: notificationId
    });
    return res.data;
  }
};
