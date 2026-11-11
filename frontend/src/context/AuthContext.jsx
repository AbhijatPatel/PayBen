import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';
import { authService } from '../services/authService';
import { walletService } from '../services/walletService';

const AuthContext = createContext(null);

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [wallet, setWallet] = useState({ balance: 0, formatted_balance: '₹0.00' });
  const [loading, setLoading] = useState(true);

  const refreshBalance = useCallback(async () => {
    try {
      const res = await walletService.getBalance();
      if (res.success && res.data) {
        setWallet({
          balance: res.data.balance,
          formatted_balance: res.data.formatted_balance
        });
      }
    } catch (err) {
      // Ignore background balance refresh error
    }
  }, []);

  const checkAuth = useCallback(async () => {
    try {
      const res = await authService.getMe();
      if (res.success && res.data) {
        setUser(res.data.user);
        setWallet(res.data.wallet);
      } else {
        setUser(null);
      }
    } catch (err) {
      setUser(null);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    checkAuth();
  }, [checkAuth]);

  const login = async (email, password) => {
    const res = await authService.login(email, password);
    if (res.success && res.data) {
      setUser(res.data.user);
      setWallet(res.data.wallet);
    }
    return res;
  };

  const register = async (fullName, email, mobile, password) => {
    const res = await authService.register(fullName, email, mobile, password);
    if (res.success && res.data) {
      setUser(res.data.user);
      setWallet(res.data.wallet);
    }
    return res;
  };

  const logout = async () => {
    await authService.logout();
    setUser(null);
    setWallet({ balance: 0, formatted_balance: '₹0.00' });
  };

  return (
    <AuthContext.Provider
      value={{
        user,
        wallet,
        loading,
        login,
        register,
        logout,
        refreshBalance,
        checkAuth
      }}
    >
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) throw new Error('useAuth must be used within AuthProvider');
  return context;
};
