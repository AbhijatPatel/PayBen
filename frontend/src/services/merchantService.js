import api from './api';

export const merchantService = {
  async list(category = 'all', all = false) {
    const res = await api.get(`/merchants/list.php?category=${encodeURIComponent(category)}${all ? '&all=true' : ''}`);
    return res.data;
  },

  async redirect(slug) {
    const res = await api.get(`/merchants/redirect.php?slug=${encodeURIComponent(slug)}`);
    return res.data;
  },

  async toggleStatus(slug, isEnabled) {
    const res = await api.post('/merchants/toggle.php', {
      slug,
      is_enabled: isEnabled ? 1 : 0
    });
    return res.data;
  }
};

export default merchantService;
