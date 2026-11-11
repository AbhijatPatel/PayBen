import React from 'react';
import { NavLink } from 'react-router-dom';
import {
  LayoutDashboard,
  Smartphone,
  Tv,
  Zap,
  Droplet,
  Flame,
  Wallet,
  History,
  User,
  ShieldAlert,
  X
} from 'lucide-react';

const navItems = [
  { name: 'Dashboard', path: '/dashboard', icon: LayoutDashboard },
  { name: 'Wallet & UPI', path: '/wallet', icon: Wallet },
  { name: 'Mobile Recharge', path: '/recharge/mobile', icon: Smartphone },
  { name: 'DTH Recharge', path: '/recharge/dth', icon: Tv },
  { name: 'Electricity Bill', path: '/bills/electricity', icon: Zap },
  { name: 'Water Bill', path: '/bills/water', icon: Droplet },
  { name: 'Gas Bill', path: '/bills/gas', icon: Flame },
  { name: 'Transactions', path: '/transactions', icon: History },
  { name: 'Profile', path: '/profile', icon: User },
];

export const Sidebar = ({ isOpen, onClose }) => {
  return (
    <>
      {/* Mobile Backdrop */}
      {isOpen && (
        <div
          className="fixed inset-0 bg-black/40 backdrop-blur-sm z-40 lg:hidden"
          onClick={onClose}
        />
      )}

      {/* Sidebar Container */}
      <aside
        className={`fixed top-0 bottom-0 left-0 w-64 bg-white border-r border-[#dadce0] z-50 flex flex-col transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:z-auto ${
          isOpen ? 'translate-x-0' : '-translate-x-full'
        }`}
      >
        {/* Header / Brand for Drawer */}
        <div className="flex items-center justify-between p-4 border-b border-gray-100 lg:hidden">
          <span className="font-bold text-lg text-gray-900">Menu</span>
          <button
            onClick={onClose}
            className="p-1 rounded-lg text-gray-500 hover:bg-gray-100"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Links Navigation */}
        <div className="flex-1 overflow-y-auto p-3 space-y-1">
          <div className="px-3 py-2 text-[11px] font-bold uppercase tracking-wider text-gray-400">
            Services & Payments
          </div>

          {navItems.map((item) => {
            const Icon = item.icon;
            return (
              <NavLink
                key={item.path}
                to={item.path}
                onClick={onClose}
                className={({ isActive }) =>
                  `flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all ${
                    isActive
                      ? 'bg-blue-50 text-[#1a73e8] shadow-sm'
                      : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                  }`
                }
              >
                <Icon className="w-5 h-5 shrink-0" />
                <span>{item.name}</span>
              </NavLink>
            );
          })}

          <div className="pt-4 px-3 py-2 text-[11px] font-bold uppercase tracking-wider text-gray-400 border-t border-gray-100 mt-4">
            Management
          </div>

          <NavLink
            to="/admin"
            onClick={onClose}
            className={({ isActive }) =>
              `flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all ${
                isActive
                  ? 'bg-purple-50 text-purple-700 shadow-sm'
                  : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
              }`
            }
          >
            <ShieldAlert className="w-5 h-5 shrink-0 text-purple-600" />
            <span>Admin Portal</span>
          </NavLink>
        </div>

        {/* Bottom Helper / Footer info */}
        <div className="p-3 border-t border-gray-100 bg-[#f8fafd]">
          <div className="flex items-center gap-2 p-2 rounded-xl bg-white border border-gray-200/60 shadow-xs">
            <div className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
            <div className="text-[11px] text-gray-500">
              <span className="font-semibold text-gray-700">Simulated Network</span>
              <p className="text-[10px]">Zero real money risk</p>
            </div>
          </div>
        </div>
      </aside>
    </>
  );
};

