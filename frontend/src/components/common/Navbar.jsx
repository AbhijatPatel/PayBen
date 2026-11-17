import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { useSecurity } from '../../context/SecurityContext';
import { useLanguage } from '../../context/LanguageContext';
import { useToast } from '../../context/ToastContext';
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
  TrendingUp,
  Bell,
  CheckCircle2
} from 'lucide-react';

export const Navbar = ({ onToggleSidebar, onOpenAddMoney, onOpenScanPay, onOpenMyQr }) => {
  const { user, wallet, logout } = useAuth();
  const { addToast } = useToast();
  const { hideBalance, toggleHideBalance, lockApp, appLockEnabled } = useSecurity();
  const { lang, toggleLanguage, t } = useLanguage();
  const [dropdownOpen, setDropdownOpen] = useState(false);
  const [notifOpen, setNotifOpen] = useState(false);
  const navigate = useNavigate();

  const handleLogout = async () => {
    try {
      await logout();
      navigate('/login');
    } catch {
      addToast('Signed out on this device, but the server could not confirm the session ended.', 'error');
      navigate('/login');
    }
  };

  return (
    <header className="sticky top-0 z-30 bg-white/95 backdrop-blur-md border-b border-gray-200/90 px-4 py-2.5">
      <div className="max-w-7xl mx-auto flex items-center justify-between gap-4">
        {/* Left: Brand & Mobile Sidebar Toggle */}
        <div className="flex items-center gap-3">
          <button
            onClick={onToggleSidebar}
            className="p-2 rounded-xl text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition lg:hidden"
            aria-label="Toggle Navigation Menu"
          >
            <Menu className="w-5 h-5" />
          </button>

          <Link to="/" className="flex items-center gap-2 group text-decoration-none">
            <img
              src="/payben-logo.svg"
              alt="PayBen — Payments Made Simple."
              className="h-9 sm:h-10 w-auto object-contain transition-transform group-hover:scale-[1.02]"
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
              className="w-full bg-[#F5F7FA] hover:bg-gray-100 focus:bg-white text-xs sm:text-sm rounded-full pl-10 pr-4 py-2 outline-none border border-transparent focus:border-[#35D6B0] focus:ring-2 focus:ring-[#35D6B0]/20 transition-all text-gray-800 placeholder-gray-400"
            />
          </div>
        </div>

        {/* Right: Quick Tools, Wallet & Profile */}
        <div className="flex items-center gap-2 sm:gap-2.5">
          {user ? (
            <>
              {/* Scan & Pay Quick Button */}
              {onOpenScanPay && (
                <button
                  onClick={onOpenScanPay}
                  className="hidden sm:flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-[#101D30] text-[#35D6B0] hover:bg-[#1a2f4d] font-extrabold text-xs transition shadow-xs"
                  title="Scan Any UPI QR Code"
                >
                  <Scan className="w-3.5 h-3.5" />
                  <span>Scan & Pay</span>
                </button>
              )}

              {/* My QR Quick Button */}
              {onOpenMyQr && (
                <button
                  onClick={onOpenMyQr}
                  className="hidden sm:flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-slate-100 text-[#101D30] hover:bg-slate-200 font-bold text-xs transition border border-slate-200 shadow-xs"
                  title="My PayBen QR Code"
                >
                  <QrCode className="w-3.5 h-3.5" />
                  <span>My QR</span>
                </button>
              )}

              {/* Wallet Pill with Eye Balance Toggle */}
              <div className="flex items-center bg-[#F5F7FA] border border-gray-200/90 rounded-full p-1 pl-3 gap-2 shadow-xs">
                <div className="flex items-center gap-1.5">
                  <WalletIcon className="w-3.5 h-3.5 text-[#101D30]" />
                  <span className="text-xs sm:text-sm font-extrabold text-[#101D30] font-mono">
                    {hideBalance ? '₹ ••••••' : (wallet?.formatted_balance || '₹0.00')}
                  </span>
                  <button
                    onClick={toggleHideBalance}
                    className="text-gray-400 hover:text-gray-700 transition p-0.5"
                    title={hideBalance ? 'Show Balance' : 'Hide Balance'}
                    aria-label="Toggle Balance Visibility"
                  >
                    {hideBalance ? <EyeOff className="w-3 h-3" /> : <Eye className="w-3 h-3" />}
                  </button>
                </div>
                <button
                  onClick={onOpenAddMoney}
                  className="w-6 h-6 sm:w-7 sm:h-7 rounded-full bg-[#101D30] text-[#35D6B0] hover:bg-[#1a2f4d] flex items-center justify-center transition shadow-xs"
                  title="Add Money to Wallet"
                  aria-label="Add Money to Wallet"
                >
                  <Plus className="w-3.5 h-3.5" />
                </button>
              </div>

              {/* Notification Bell Dropdown */}
              <div className="relative">
                <button
                  onClick={() => setNotifOpen(!notifOpen)}
                  className="p-2 rounded-full text-gray-500 hover:text-[#101D30] hover:bg-gray-100 transition relative"
                  title="Notifications & Alerts"
                  aria-label="Notifications"
                >
                  <Bell className="w-4 h-4" />
                  <span className="w-2 h-2 rounded-full bg-[#35D6B0] absolute top-1.5 right-1.5 ring-2 ring-white" />
                </button>

                {notifOpen && (
                  <div
                    className="absolute right-0 mt-2 w-72 sm:w-80 bg-white rounded-2xl shadow-xl border border-gray-100 p-3 z-50 animate-in fade-in zoom-in-95"
                    onClick={() => setNotifOpen(false)}
                  >
                    <div className="flex items-center justify-between pb-2 border-b border-gray-100">
                      <span className="text-xs font-extrabold text-[#101D30]">System Notifications</span>
                      <span className="text-[10px] text-[#35D6B0] font-mono font-bold">Live Sandbox</span>
                    </div>
                    <div className="py-2 space-y-2">
                      <div className="p-2.5 rounded-xl bg-emerald-50/70 border border-emerald-100 flex items-start gap-2 text-xs">
                        <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
                        <div>
                          <p className="font-bold text-emerald-950">Double-Entry Ledger Active</p>
                          <p className="text-[11px] text-emerald-800">Atomic balance locking verified.</p>
                        </div>
                      </div>
                      <div className="p-2.5 rounded-xl bg-blue-50/70 border border-blue-100 flex items-start gap-2 text-xs">
                        <Zap className="w-4 h-4 text-blue-600 shrink-0 mt-0.5" />
                        <div>
                          <p className="font-bold text-blue-950">Zero-PIN FastPay Ready</p>
                          <p className="text-[11px] text-blue-800">UPI Lite enabled for small micro-recharges.</p>
                        </div>
                      </div>
                    </div>
                  </div>
                )}
              </div>

              {/* Language Switcher Pill */}
              <button
                onClick={toggleLanguage}
                className="hidden sm:flex items-center gap-1 px-2.5 py-1.5 rounded-full bg-gray-100 hover:bg-gray-200 text-xs font-bold text-gray-700 transition"
                title="Switch Language (English / हिन्दी)"
              >
                <Globe className="w-3.5 h-3.5 text-[#101D30]" />
                <span>{lang === 'en' ? 'हिन्दी' : 'English'}</span>
              </button>

              {/* App Lock Button */}
              {appLockEnabled && (
                <button
                  onClick={lockApp}
                  className="p-2 rounded-full text-gray-400 hover:text-[#101D30] hover:bg-gray-100 transition"
                  title="Lock PayBen App Now"
                  aria-label="Lock App"
                >
                  <Lock className="w-4 h-4" />
                </button>
              )}

              {/* User Dropdown */}
              <div className="relative">
                <button
                  onClick={() => setDropdownOpen(!dropdownOpen)}
                  className="flex items-center gap-1.5 p-1 rounded-full hover:bg-gray-100 transition"
                  aria-label="User Profile Menu"
                >
                  <div className="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-[#101D30] text-[#35D6B0] border border-[#35D6B0]/40 font-extrabold text-xs sm:text-sm flex items-center justify-center shadow-xs">
                    {user.full_name?.charAt(0).toUpperCase() || 'U'}
                  </div>
                  <ChevronDown className="w-3.5 h-3.5 text-gray-500 hidden sm:block" />
                </button>

                {dropdownOpen && (
                  <div
                    className="absolute right-0 mt-2 w-64 bg-white rounded-2xl shadow-xl border border-gray-100 py-2 z-50 animate-in fade-in zoom-in-95"
                    onClick={() => setDropdownOpen(false)}
                  >
                    <div className="px-4 py-3 border-b border-gray-100 bg-[#F5F7FA]/50 rounded-t-2xl">
                      <p className="text-sm font-extrabold text-[#101D30] truncate">{user.full_name}</p>
                      <p className="text-xs text-gray-500 truncate">{user.email}</p>
                      <div className="mt-1.5 text-[11px] font-mono bg-emerald-50 text-emerald-800 border border-emerald-200 px-2 py-0.5 rounded-md inline-block font-bold">
                        {user.upi_id || `${user.mobile}@payben`}
                      </div>
                    </div>

                    <Link
                      to="/profile"
                      className="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-[#F5F7FA] transition"
                    >
                      <User className="w-4 h-4 text-gray-400" />
                      My Profile
                    </Link>

                    <Link
                      to="/banking/accounts"
                      className="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-[#F5F7FA] transition"
                    >
                      <Landmark className="w-4 h-4 text-[#101D30]" />
                      Bank Accounts & UPI
                    </Link>

                    <Link
                      to="/financial/upi-lite"
                      className="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-[#F5F7FA] transition"
                    >
                      <Zap className="w-4 h-4 text-amber-500" />
                      UPI Lite (Zero PIN)
                    </Link>

                    <Link
                      to="/financial/autopay"
                      className="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-[#F5F7FA] transition"
                    >
                      <Repeat className="w-4 h-4 text-indigo-600" />
                      AutoPay Mandates
                    </Link>

                    <Link
                      to="/financial/cards"
                      className="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-[#F5F7FA] transition"
                    >
                      <CreditCard className="w-4 h-4 text-blue-600" />
                      Credit & Debit Cards
                    </Link>

                    <Link
                      to="/financial/cibil-loans"
                      className="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-[#F5F7FA] transition"
                    >
                      <TrendingUp className="w-4 h-4 text-rose-500" />
                      CIBIL Score & Loans
                    </Link>

                    <Link
                      to="/security"
                      className="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-[#F5F7FA] transition"
                    >
                      <Shield className="w-4 h-4 text-[#35D6B0]" />
                      Security & Privacy Center
                    </Link>

                    <Link
                      to="/wallet"
                      className="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-[#F5F7FA] transition"
                    >
                      <WalletIcon className="w-4 h-4 text-gray-400" />
                      Wallet & Rewards
                    </Link>

                    <div className="border-t border-gray-100 my-1"></div>

                    <button
                      onClick={handleLogout}
                      className="w-full flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 transition text-left"
                    >
                      <LogOut className="w-4 h-4 text-rose-500" />
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
                className="text-xs font-bold text-gray-700 hover:text-[#101D30] px-3 py-1.5 rounded-xl transition"
              >
                Sign In
              </Link>
              <Link
                to="/register"
                className="text-xs font-extrabold text-[#101D30] bg-[#35D6B0] hover:bg-[#2dc29e] px-4 py-2 rounded-xl shadow-xs transition"
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

export default Navbar;
