import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { useSecurity } from '../../context/SecurityContext';
import { useLanguage } from '../../context/LanguageContext';
import {
  Wallet as WalletIcon,
  Search,
  Menu,
  User,
  LogOut,
  ChevronDown,
  Plus,
  QrCode,
  Scan,
  Shield,
  Lock,
  Eye,
  EyeOff,
  Globe,
  Landmark,
  Zap,
  Repeat,
  CreditCard,
  TrendingUp
} from 'lucide-react';

export const Navbar = ({ onToggleSidebar, onOpenAddMoney, onOpenScanPay, onOpenMyQr }) => {
  const { user, wallet, logout } = useAuth();
  const { hideBalance, toggleHideBalance, lockApp, appLockEnabled } = useSecurity();
  const { lang, toggleLanguage, t } = useLanguage();
  const [dropdownOpen, setDropdownOpen] = useState(false);
  const navigate = useNavigate();

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  return (
    <header className="sticky top-0 z-30 bg-white/95 backdrop-blur-md border-b border-gray-200/80 px-4 py-2.5">
      <div className="max-w-7xl mx-auto flex items-center justify-between gap-4">
        {/* Left: Brand & Mobile Sidebar Toggle */}
        <div className="flex items-center gap-3">
          <button
            onClick={onToggleSidebar}
            className="p-2 rounded-xl text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition lg:hidden"
            aria-label="Toggle Menu"
          >
            <Menu className="w-5 h-5" />
          </button>

          <Link to="/" className="flex items-center gap-2 group text-decoration-none">
            <img
              src="/payben-logo.png"
              alt="PayBen"
              className="h-10 w-auto object-contain transition-transform group-hover:scale-[1.02]"
            />
          </Link>
        </div>

        {/* Center: Search Bar */}
        <div className="hidden md:flex flex-1 max-w-md mx-4">
          <div className="relative w-full">
            <Search className="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
            <input
              type="text"
              placeholder="Search contacts, bills, recharges, or UPI..."
              className="w-full bg-[#f1f4f9] hover:bg-[#e8edf5] focus:bg-white text-sm rounded-full pl-10 pr-4 py-2 outline-none border border-transparent focus:border-[#0047cc] focus:ring-2 focus:ring-blue-100 transition-all text-gray-800 placeholder-gray-500"
            />
          </div>
        </div>

        {/* Right: Quick Tools, Wallet & Profile */}
        <div className="flex items-center gap-2 sm:gap-3">
          {user ? (
            <>
              {/* Scan & Pay Quick Button */}
              {onOpenScanPay && (
                <button
                  onClick={onOpenScanPay}
                  className="hidden sm:flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-blue-50 text-[#0047cc] hover:bg-blue-100 font-bold text-xs transition border border-blue-200/60 shadow-xs"
                  title="Scan Any UPI QR Code"
                >
                  <Scan className="w-3.5 h-3.5 text-[#ff6200]" />
                  <span>Scan & Pay</span>
                </button>
              )}

              {/* My QR Quick Button */}
              {onOpenMyQr && (
                <button
                  onClick={onOpenMyQr}
                  className="hidden sm:flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-orange-50 text-[#ff6200] hover:bg-orange-100 font-bold text-xs transition border border-orange-200/60 shadow-xs"
                  title="My PayBen QR Code"
                >
                  <QrCode className="w-3.5 h-3.5" />
                  <span>My QR</span>
                </button>
              )}

              {/* Wallet Pill with Eye Balance Toggle */}
              <div className="flex items-center bg-[#f8fafd] border border-gray-200 rounded-full p-1 pl-3 gap-2 shadow-xs">
                <div className="flex items-center gap-1.5">
                  <WalletIcon className="w-4 h-4 text-[#0047cc]" />
                  <span className="text-sm font-black text-gray-900 font-mono">
                    {hideBalance ? '₹ ••••••' : wallet.formatted_balance}
                  </span>
                  <button
                    onClick={toggleHideBalance}
                    className="text-gray-400 hover:text-gray-700 transition p-0.5"
                    title={hideBalance ? 'Show Balance' : 'Hide Balance'}
                  >
                    {hideBalance ? <EyeOff className="w-3.5 h-3.5" /> : <Eye className="w-3.5 h-3.5" />}
                  </button>
                </div>
                <button
                  onClick={onOpenAddMoney}
                  className="w-7 h-7 rounded-full bg-[#ff6200] text-white flex items-center justify-center hover:bg-[#e85800] transition shadow-xs"
                  title="Add Money to Wallet"
                >
                  <Plus className="w-4 h-4" />
                </button>
              </div>

              {/* Language Switcher Pill */}
              <button
                onClick={toggleLanguage}
                className="flex items-center gap-1 px-2.5 py-1.5 rounded-full bg-gray-100 hover:bg-gray-200 text-xs font-bold text-gray-700 transition"
                title="Switch Language (English / हिन्दी)"
              >
                <Globe className="w-3.5 h-3.5 text-[#0047cc]" />
                <span>{lang === 'en' ? 'हिन्दी' : 'English'}</span>
              </button>

              {/* App Lock Button */}
              {appLockEnabled && (
                <button
                  onClick={lockApp}
                  className="p-2 rounded-full text-gray-400 hover:text-[#0047cc] hover:bg-blue-50 transition"
                  title="Lock PayBen App Now"
                >
                  <Lock className="w-4 h-4" />
                </button>
              )}

              {/* User Dropdown */}
              <div className="relative">
                <button
                  onClick={() => setDropdownOpen(!dropdownOpen)}
                  className="flex items-center gap-2 p-1 rounded-full hover:bg-gray-100 transition"
                >
                  <div className="w-9 h-9 rounded-full bg-gradient-to-tr from-[#0047cc] to-[#ff6200] text-white font-black text-sm flex items-center justify-center shadow-md">
                    {user.full_name?.charAt(0).toUpperCase() || 'U'}
                  </div>
                  <ChevronDown className="w-4 h-4 text-gray-500 hidden sm:block" />
                </button>

                {dropdownOpen && (
                  <div
                    className="absolute right-0 mt-2 w-64 bg-white rounded-2xl shadow-xl border border-gray-100 py-2 z-50 animate-in fade-in zoom-in-95"
                    onClick={() => setDropdownOpen(false)}
                  >
                    <div className="px-4 py-3 border-b border-gray-100">
                      <p className="text-sm font-bold text-gray-900 truncate">{user.full_name}</p>
                      <p className="text-xs text-gray-500 truncate">{user.email}</p>
                      <div className="mt-1.5 text-[11px] font-mono bg-blue-50 text-[#0047cc] px-2 py-0.5 rounded-md inline-block font-bold">
                        {user.upi_id || `${user.mobile}@payben`}
                      </div>
                    </div>

                    <Link
                      to="/profile"
                      className="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition"
                    >
                      <User className="w-4 h-4 text-gray-400" />
                      My Profile
                    </Link>

                    <Link
                      to="/banking/accounts"
                      className="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition"
                    >
                      <Landmark className="w-4 h-4 text-[#0047cc]" />
                      Bank Accounts & UPI
                    </Link>

                    <Link
                      to="/financial/upi-lite"
                      className="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition"
                    >
                      <Zap className="w-4 h-4 text-[#ff6200]" />
                      UPI Lite (Zero PIN)
                    </Link>

                    <Link
                      to="/financial/autopay"
                      className="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition"
                    >
                      <Repeat className="w-4 h-4 text-emerald-600" />
                      AutoPay Mandates
                    </Link>

                    <Link
                      to="/financial/cards"
                      className="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition"
                    >
                      <CreditCard className="w-4 h-4 text-indigo-600" />
                      Credit & Debit Cards
                    </Link>

                    <Link
                      to="/financial/cibil-loans"
                      className="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition"
                    >
                      <TrendingUp className="w-4 h-4 text-emerald-600" />
                      CIBIL Score & Loans
                    </Link>

                    <Link
                      to="/security"
                      className="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition"
                    >
                      <Shield className="w-4 h-4 text-[#0047cc]" />
                      Security & Privacy Center
                    </Link>

                    <Link
                      to="/wallet"
                      className="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition"
                    >
                      <WalletIcon className="w-4 h-4 text-gray-400" />
                      Wallet & Rewards
                    </Link>

                    <div className="border-t border-gray-100 my-1"></div>

                    <button
                      onClick={handleLogout}
                      className="w-full flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-red-600 hover:bg-red-50 transition text-left"
                    >
                      <LogOut className="w-4 h-4 text-red-500" />
                      Sign Out
                    </button>
                  </div>
                )}
              </div>
            </>
          ) : (
            <div className="flex items-center gap-2">
              <Link
                to="/login"
                className="text-xs font-bold text-gray-700 hover:text-[#0047cc] px-3 py-1.5 rounded-lg transition"
              >
                Sign In
              </Link>
              <Link
                to="/register"
                className="text-xs font-bold text-white bg-[#ff6200] hover:bg-[#e85800] px-4 py-2 rounded-full shadow-sm transition"
              >
                Register
              </Link>
            </div>
          )}
        </div>
      </div>
    </header>
  );
};
