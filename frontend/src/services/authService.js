import api from './api';

export const authService = {
  async login(email, password) {
    const res = await api.post('/auth/login.php', { email, password });
    if (res.data.data?.token) {
      localStorage.setItem('payben_token', res.data.data.token);
    }
    return res.data;
  },

  async register(fullName, email, mobile, password) {
    const res = await api.post('/auth/register.php', {
      full_name: fullName,
      email,
      mobile,
      password
    });
    if (res.data.data?.token) {
      localStorage.setItem('payben_token', res.data.data.token);
    }
    return res.data;
  },

  async getMe() {
    const res = await api.get('/auth/me.php');
    return res.data;
  },

  async logout() {
    try {
      await api.post('/auth/logout.php');
    } finally {
      localStorage.removeItem('payben_token');
    }
  }
};
