import axios from 'axios';

const API_BASE = import.meta.env.VITE_API_BASE_URL || '/api';

const api = axios.create({
  baseURL: API_BASE,
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  timeout: 45000 // 45s timeout to comfortably accommodate Railway container cold-starts
});

// Attach Bearer token if stored
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('payben_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
}, (error) => Promise.reject(error));

// Response error handler with graceful timeout messaging
api.interceptors.response.use(
  (response) => response,
  (error) => {
    let message = error.response?.data?.message;
    if (!message) {
      if (error.code === 'ECONNABORTED' || (error.message && error.message.toLowerCase().includes('timeout'))) {
        message = 'Server response took longer than expected (cloud container waking up). Please try again in a moment.';
      } else if (error.message === 'Network Error') {
        message = 'Unable to connect to PayBen backend. Please check your network connection.';
      } else {
        message = error.message || 'Something went wrong';
      }
    }
    const normalizedError = new Error(message);
    normalizedError.status = error.response?.status;
    normalizedError.code = error.code;
    normalizedError.responseData = error.response?.data;
    return Promise.reject(normalizedError);
  }
);

export default api;

