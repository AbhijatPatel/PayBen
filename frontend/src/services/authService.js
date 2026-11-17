import api from './api';

const getAuthError = (error, flow) => {
  const status = error.status ?? error.response?.status;
  const responseData = error.responseData ?? error.response?.data;
  if (!status) {
    if (error.code === 'ECONNABORTED') {
      return new Error('The server took too long to respond. Please try again.');
    }
    return new Error('Unable to connect to PayBen. Check your network and try again.');
  }
  if (status === 401 && flow === 'login') {
    return new Error('Incorrect email or password.');
  }
  if (status >= 500) {
    const message = responseData?.message;
    if (status === 503 && message?.toLowerCase().includes('not configured')) {
      return new Error(message);
    }
    return new Error('PayBen could not complete your request right now. Please try again.');
  }
  return new Error(responseData?.message || error.message || 'Unable to complete authentication. Please try again.');
};

export const authService = {
  async login(email, password) {
    try {
      const res = await api.post('/auth/login.php', { email, password });
      if (res.data.data?.token) {
        localStorage.setItem('payben_token', res.data.data.token);
      }
      return res.data;
    } catch (error) {
      throw getAuthError(error, 'login');
    }
  },

  async googleLogin(payload) {
    try {
      const res = await api.post('/auth/google.php', payload);
      if (res.data.data?.token) {
        localStorage.setItem('payben_token', res.data.data.token);
      }
      return res.data;
    } catch (error) {
      throw getAuthError(error, 'google');
    }
  },

  async register(fullName, email, mobile, password, confirmPassword) {
    try {
      const res = await api.post('/auth/register.php', {
        full_name: fullName,
        email,
        mobile,
        password,
        confirm_password: confirmPassword
      });
      if (res.data.data?.token) {
        localStorage.setItem('payben_token', res.data.data.token);
      }
      return res.data;
    } catch (error) {
      throw getAuthError(error, 'register');
    }
  },

  async requestPasswordReset(email) {
    try {
      const res = await api.post('/auth/request-password-reset.php', { email });
      return res.data;
    } catch (error) {
      throw getAuthError(error, 'password reset');
    }
  },

  async resetPassword(email, token, password, confirmPassword) {
    try {
      const res = await api.post('/auth/reset-password.php', {
        email,
        token,
        password,
        confirm_password: confirmPassword
      });
      return res.data;
    } catch (error) {
      throw getAuthError(error, 'password reset');
    }
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
  },

  async sendOtp(identifier, purpose = 'LOGIN') {
    const res = await api.post('/auth/send-otp.php', { identifier, purpose });
    return res.data;
  },

  async verifyOtp(identifier, otp, purpose = 'LOGIN') {
    const res = await api.post('/auth/verify-otp.php', { identifier, otp, purpose });
    return res.data;
  }
};

