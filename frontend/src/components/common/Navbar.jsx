import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import {
  Zap,
  Wallet as WalletIcon,
  Search,
  Bell,
  Menu,
  User,
  LogOut,
  ChevronDown,
  ShieldCheck,
  Plus
} from 'lucide-react';

export const Navbar = ({ onToggleSidebar, onOpenAddMoney }) => {
  const { user, wallet, logout } = useAuth();
  const [dropdownOpen, setDropdownOpen] = useState(false);
  const navigate = useNavigate();

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  return (
    <header className="sticky top-0 z-30 bg-white/95 backdrop-blur-md border-b border-[#dadce0] px-4 py-2.5">
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

          <Link to="/" className="flex items-center gap-2.5 group text-decoration-none">
            <div className="w-10 h-10 rounded-xl bg-gradient-to-tr from-[#1a73e8] to-[#34a853] flex items-center justify-center text-white shadow-md shadow-blue-500/20 group-hover:scale-105 transition-transform">
              <Zap className="w-5 h-5 fill-current" />
            </div>
            <div className="flex flex-col">
              <div className="flex items-center gap-1.5 leading-none">
                <span className="text-xl font-bold tracking-tight text-[#202124]">
                  Pay<span className="text-[#1a73e8]">Ben</span>
                </span>
                <span className="text-[10px] font-bold uppercase tracking-wider bg-blue-50 text-[#1a73e8] border border-blue-200 px-1.5 py-0.5 rounded-full">
                  UPI
                </span>
              </div>
              <span className="text-[11px] font-medium text-gray-500 tracking-wide">
                Instant Recharge & Bills
              </span>
            </div>
          </Link>
        </div>

        {/* Center: Search Bar */}
        <div className="hidden md:flex flex-1 max-w-md mx-4">
          <div className="relative w-full">
            <Search className="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
            <input
              type="text"
              placeholder="Search people, bills, plans, or transactions..."
              className="w-full bg-[#f1f3f4] hover:bg-[#e8eaed] focus:bg-white text-sm rounded-full pl-10 pr-4 py-2 outline-none border border-transparent focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100 transition-all text-gray-800 placeholder-gray-500"
            />
          </div>
        </div>

        {/* Right: Wallet Balance & User Profile */}
        <div className="flex items-center gap-3">
          {user ? (
            <>
              {/* Wallet Pill */}
              <div className="flex items-center bg-[#f8fafd] border border-[#dadce0] rounded-full p-1 pl-3 gap-2 shadow-sm">
                <div className="flex items-center gap-1.5">
                  <WalletIcon className="w-4 h-4 text-[#1a73e8]" />
                  <span className="text-xs text-gray-500 font-medium hidden sm:inline">Balance:</span>
                  <span className="text-sm font-bold text-gray-900">{wallet.formatted_balance}</span>
                </div>
                <button
                  onClick={onOpenAddMoney}
                  className="w-7 h-7 rounded-full bg-[#1a73e8] text-white flex items-center justify-center hover:bg-[#1557b0] transition shadow-sm"
                  title="Add Money"
                >
                  <Plus className="w-4 h-4" />
                </button>
              </div>

              {/* User Dropdown */}
              <div className="relative">
                <button
                  onClick={() => setDropdownOpen(!dropdownOpen)}
                  className="flex items-center gap-2 p-1.5 rounded-full hover:bg-gray-100 transition"
                >
                  <div className="w-9 h-9 rounded-full bg-gradient-to-tr from-blue-500 to-indigo-600 text-white font-bold text-sm flex items-center justify-center shadow-sm">
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
                      <div className="mt-2 text-[11px] font-mono bg-blue-50 text-[#1a73e8] px-2 py-0.5 rounded-md inline-block">
                        {user.upi_id}
                      </div>
                    </div>

                    <Link
                      to="/profile"
                      className="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition"
                    >
                      <User className="w-4 h-4 text-gray-400" />
                      My Profile
                    </Link>

                    <Link
                      to="/wallet"
                      className="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition"
                    >
                      <WalletIcon className="w-4 h-4 text-gray-400" />
                      Wallet & Rewards
                    </Link>

                    <Link
                      to="/admin"
                      className="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition"
                    >
                      <ShieldCheck className="w-4 h-4 text-gray-400" />
                      Admin Portal
                    </Link>

                    <div className="border-t border-gray-100 my-1"></div>

                    <button
                      onClick={handleLogout}
                      className="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition text-left"
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
                className="text-sm font-semibold text-gray-700 hover:text-[#1a73e8] px-3 py-1.5 rounded-lg transition"
              >
                Sign In
              </Link>
              <Link
                to="/register"
                className="text-sm font-semibold text-white bg-[#1a73e8] hover:bg-[#1557b0] px-4 py-2 rounded-full shadow-sm transition"
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

