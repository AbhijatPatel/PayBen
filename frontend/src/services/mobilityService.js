import api from './api';

export const mobilityService = {
  async estimate(pickup = '', drop = '') {
    const res = await api.get(`/mobility/estimate.php?pickup=${encodeURIComponent(pickup)}&drop=${encodeURIComponent(drop)}`);
    return res.data;
  },

  async logRedirect(data) {
    const res = await api.post('/mobility/redirect.php', data);
    return res.data;
  }
};

export default mobilityService;
