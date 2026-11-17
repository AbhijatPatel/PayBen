import React from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { MainLayout } from '../layouts/MainLayout';
import { AdminLayout } from '../layouts/AdminLayout';

import { Home } from '../pages/Home';
import { Login } from '../pages/auth/Login';
import { ForgotPassword } from '../pages/auth/ForgotPassword';
import { ResetPassword } from '../pages/auth/ResetPassword';
import { Register } from '../pages/auth/Register';
import { Dashboard } from '../pages/dashboard/Dashboard';
import { WalletPage } from '../pages/wallet/Wallet';
import { MobileRecharge } from '../pages/recharge/MobileRecharge';
import { DthRecharge } from '../pages/recharge/DthRecharge';
import { ElectricityBill } from '../pages/bills/ElectricityBill';
import { WaterBill } from '../pages/bills/WaterBill';
import { GasBill } from '../pages/bills/GasBill';
import { BroadbandBill } from '../pages/bills/BroadbandBill';
import { Transactions } from '../pages/transactions/Transactions';
import { Profile } from '../pages/profile/Profile';
import { SecuritySettings } from '../pages/profile/SecuritySettings';
import { BankAccounts } from '../pages/banking/BankAccounts';
import { UpiLite } from '../pages/financial/UpiLite';
import { AutoPay } from '../pages/financial/AutoPay';
import { PocketMoney } from '../pages/financial/PocketMoney';
import { CardsPay } from '../pages/financial/CardsPay';
import { CibilLoan } from '../pages/financial/CibilLoan';

// Travel & Bookings
import { TrainBooking } from '../pages/travel/TrainBooking';
import { BusBooking } from '../pages/travel/BusBooking';
import { FlightBooking } from '../pages/travel/FlightBooking';
import { HotelBooking } from '../pages/travel/HotelBooking';
import { MyBookings } from '../pages/travel/MyBookings';

// Shopping & Food
import { ShoppingMarketplace } from '../pages/shopping/ShoppingMarketplace';
import { FoodGrocery } from '../pages/food/FoodGrocery';

// Ride Booking & Mobility
import { RideBooking } from '../pages/mobility/RideBooking';

// Admin Portal
import { AdminLogin } from '../pages/admin/AdminLogin';
import { AdminDashboard } from '../pages/admin/AdminDashboard';
import { AdminUsers } from '../pages/admin/AdminUsers';
import { AdminTransactions } from '../pages/admin/AdminTransactions';
import { AdminBookings } from '../pages/admin/AdminBookings';
import { AdminMerchants } from '../pages/admin/AdminMerchants';

const ProtectedRoute = ({ children }) => {
  const { user, loading } = useAuth();
  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="w-8 h-8 border-4 border-[#101D30] border-t-transparent rounded-full animate-spin"></div>
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
        <Route path="forgot-password" element={<ForgotPassword />} />
        <Route path="reset-password" element={<ResetPassword />} />
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
          path="bills/broadband"
          element={
            <ProtectedRoute>
              <BroadbandBill />
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

        {/* Travel & Bookings */}
        <Route
          path="travel/trains"
          element={
            <ProtectedRoute>
              <TrainBooking />
            </ProtectedRoute>
          }
        />
        <Route
          path="travel/buses"
          element={
            <ProtectedRoute>
              <BusBooking />
            </ProtectedRoute>
          }
        />
        <Route
          path="travel/flights"
          element={
            <ProtectedRoute>
              <FlightBooking />
            </ProtectedRoute>
          }
        />
        <Route
          path="travel/hotels"
          element={
            <ProtectedRoute>
              <HotelBooking />
            </ProtectedRoute>
          }
        />
        <Route
          path="travel/bookings"
          element={
            <ProtectedRoute>
              <MyBookings />
            </ProtectedRoute>
          }
        />
        <Route
          path="bookings"
          element={
            <ProtectedRoute>
              <MyBookings />
            </ProtectedRoute>
          }
        />

        {/* Shopping & Food */}
        <Route
          path="shopping"
          element={
            <ProtectedRoute>
              <ShoppingMarketplace />
            </ProtectedRoute>
          }
        />
        <Route
          path="food"
          element={
            <ProtectedRoute>
              <FoodGrocery />
            </ProtectedRoute>
          }
        />

        {/* Ride Booking & Mobility */}
        <Route
          path="mobility"
          element={
            <ProtectedRoute>
              <RideBooking />
            </ProtectedRoute>
          }
        />
        <Route
          path="rides"
          element={
            <ProtectedRoute>
              <RideBooking />
            </ProtectedRoute>
          }
        />

        {/* Profile & Security */}
        <Route
          path="profile"
          element={
            <ProtectedRoute>
              <Profile />
            </ProtectedRoute>
          }
        />
        <Route
          path="security"
          element={
            <ProtectedRoute>
              <SecuritySettings />
            </ProtectedRoute>
          }
        />

        {/* Fintech Super App Financial Features */}
        <Route
          path="banking/accounts"
          element={
            <ProtectedRoute>
              <BankAccounts />
            </ProtectedRoute>
          }
        />
        <Route
          path="financial/upi-lite"
          element={
            <ProtectedRoute>
              <UpiLite />
            </ProtectedRoute>
          }
        />
        <Route
          path="financial/autopay"
          element={
            <ProtectedRoute>
              <AutoPay />
            </ProtectedRoute>
          }
        />
        <Route
          path="financial/pocket-money"
          element={
            <ProtectedRoute>
              <PocketMoney />
            </ProtectedRoute>
          }
        />
        <Route
          path="financial/cards"
          element={
            <ProtectedRoute>
              <CardsPay />
            </ProtectedRoute>
          }
        />
        <Route
          path="financial/cibil-loans"
          element={
            <ProtectedRoute>
              <CibilLoan />
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
        <Route path="bookings" element={<AdminBookings />} />
        <Route path="merchants" element={<AdminMerchants />} />
      </Route>

      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
};

export default AppRoutes;
