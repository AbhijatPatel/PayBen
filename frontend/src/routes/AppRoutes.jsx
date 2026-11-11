import React from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { MainLayout } from '../layouts/MainLayout';
import { AdminLayout } from '../layouts/AdminLayout';

import { Home } from '../pages/Home';
import { Login } from '../pages/auth/Login';
import { Register } from '../pages/auth/Register';
import { Dashboard } from '../pages/dashboard/Dashboard';
import { WalletPage } from '../pages/wallet/Wallet';
import { MobileRecharge } from '../pages/recharge/MobileRecharge';
import { DthRecharge } from '../pages/recharge/DthRecharge';
import { ElectricityBill } from '../pages/bills/ElectricityBill';
import { WaterBill } from '../pages/bills/WaterBill';
import { GasBill } from '../pages/bills/GasBill';
import { Transactions } from '../pages/transactions/Transactions';
import { Profile } from '../pages/profile/Profile';

import { AdminLogin } from '../pages/admin/AdminLogin';
import { AdminDashboard } from '../pages/admin/AdminDashboard';
import { AdminUsers } from '../pages/admin/AdminUsers';
import { AdminTransactions } from '../pages/admin/AdminTransactions';

const ProtectedRoute = ({ children }) => {
  const { user, loading } = useAuth();
  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="w-8 h-8 border-4 border-[#1a73e8] border-t-transparent rounded-full animate-spin"></div>
      </div>
    );
  }
  return user ? children : <Navigate to="/login" replace />;
};

const AdminRoute = ({ children }) => {
  const admin = localStorage.getItem('payben_admin');
  return admin ? children : <Navigate to="/admin/login" replace />;
};

export const AppRoutes = () => {
  return (
    <Routes>
      {/* Public / User App Shell */}
      <Route path="/" element={<MainLayout />}>
        <Route index element={<Home />} />
        <Route path="login" element={<Login />} />
        <Route path="register" element={<Register />} />

        {/* Protected User Routes */}
        <Route
          path="dashboard"
          element={
            <ProtectedRoute>
              <Dashboard />
            </ProtectedRoute>
          }
        />
        <Route
          path="wallet"
          element={
            <ProtectedRoute>
              <WalletPage />
            </ProtectedRoute>
          }
        />
        <Route
          path="recharge/mobile"
          element={
            <ProtectedRoute>
              <MobileRecharge />
            </ProtectedRoute>
          }
        />
        <Route
          path="recharge/dth"
          element={
            <ProtectedRoute>
              <DthRecharge />
            </ProtectedRoute>
          }
        />
        <Route
          path="bills/electricity"
          element={
            <ProtectedRoute>
              <ElectricityBill />
            </ProtectedRoute>
          }
        />
        <Route
          path="bills/water"
          element={
            <ProtectedRoute>
              <WaterBill />
            </ProtectedRoute>
          }
        />
        <Route
          path="bills/gas"
          element={
            <ProtectedRoute>
              <GasBill />
            </ProtectedRoute>
          }
        />
        <Route
          path="transactions"
          element={
            <ProtectedRoute>
              <Transactions />
            </ProtectedRoute>
          }
        />
        <Route
          path="profile"
          element={
            <ProtectedRoute>
              <Profile />
            </ProtectedRoute>
          }
        />
      </Route>

      {/* Admin Portal Routes */}
      <Route path="/admin/login" element={<AdminLogin />} />
      <Route
        path="/admin"
        element={
          <AdminRoute>
            <AdminLayout />
          </AdminRoute>
        }
      >
        <Route index element={<Navigate to="/admin/dashboard" replace />} />
        <Route path="dashboard" element={<AdminDashboard />} />
        <Route path="users" element={<AdminUsers />} />
        <Route path="transactions" element={<AdminTransactions />} />
      </Route>

      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
};

