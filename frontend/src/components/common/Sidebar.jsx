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
  Shield,
  User,
  X,
  Landmark,
  Repeat,
  CreditCard,
  TrendingUp,
  Sparkles,
  ShieldCheck,
  ChevronRight
} from 'lucide-react';

const navGroups = [
  {
    title: 'Overview',
    items: [
      { name: 'Dashboard', path: '/dashboard', icon: LayoutDashboard },
      { name: 'Wallet & UPI', path: '/wallet', icon: Wallet },
      { name: 'Passbook Statements', path: '/transactions', icon: History }
    ]
  },
  {
    title: 'Popular Services',
    items: [
      { name: 'Mobile Recharge', path: '/recharge/mobile', icon: Smartphone },
      { name: 'DTH Recharge', path: '/recharge/dth', icon: Tv },
      { name: 'Electricity Bill', path: '/bills/electricity', icon: Zap },
      { name: 'Water Bill', path: '/bills/water', icon: Droplet },
      { name: 'Gas Bill', path: '/bills/gas', icon: Flame }
    ]
  },
  {
    title: 'Fintech Super App',
    items: [
      { name: 'Bank Accounts', path: '/banking/accounts', icon: Landmark },
      { name: 'UPI Lite (Zero PIN)', path: '/financial/upi-lite', icon: Zap },
      { name: 'AutoPay Mandates', path: '/financial/autopay', icon: Repeat },
      { name: 'Pocket Money', path: '/financial/pocket-money', icon: Sparkles },
      { name: 'Cards Vault', path: '/financial/cards', icon: CreditCard },
      { name: 'CIBIL & Loans', path: '/financial/cibil-loans', icon: TrendingUp }
    ]
  },
  {
    title: 'Settings & Security',
    items: [
      { name: 'Security & App Lock', path: '/security', icon: Shield },
      { name: 'Profile Account', path: '/profile', icon: User }
    ]
  }
];

export const Sidebar = ({ isOpen, onClose }) => {
  return (
    <>
      {/* Mobile Backdrop */}
      {isOpen && (
        <div
          className="fixed inset-0 bg-black/50 backdrop-blur-sm z-40 lg:hidden"
          onClick={onClose}
          aria-hidden="true"
        />
      )}

      {/* Sidebar Container */}
      <aside
        className={`fixed top-0 bottom-0 left-0 w-64 bg-white border-r border-gray-200/90 z-50 flex flex-col transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:z-auto ${
          isOpen ? 'translate-x-0 shadow-2xl' : '-translate-x-full'
        }`}
        aria-label="Sidebar Navigation"
      >
        {/* Mobile Header Drawer Close */}
        <div className="flex items-center justify-between p-4 border-b border-gray-100 lg:hidden">
          <div className="flex items-center gap-2">
            <img src="/payben-app-icon.svg" alt="PayBen" className="w-7 h-7 rounded-lg" />
            <span className="font-extrabold text-base text-[#101D30]">Navigation</span>
          </div>
          <button
            onClick={onClose}
            className="p-1.5 rounded-xl text-gray-500 hover:bg-gray-100 transition"
            aria-label="Close sidebar"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Grouped Nav Items */}
        <div className="flex-1 overflow-y-auto p-3 space-y-5 scrollbar-thin">
          {navGroups.map((group) => (
            <div key={group.title} className="space-y-1">
              <div className="px-3 py-1 text-[10px] font-extrabold uppercase tracking-wider text-gray-400">
                {group.title}
              </div>

              {group.items.map((item) => {
                const Icon = item.icon;
                return (
                  <NavLink
                    key={item.path}
                    to={item.path}
                    onClick={onClose}
                    className={({ isActive }) =>
                      `flex items-center justify-between px-3.5 py-2.5 rounded-2xl text-xs font-bold transition-all ${
                        isActive
                          ? 'bg-[#101D30] text-[#35D6B0] shadow-sm'
                          : 'text-gray-600 hover:bg-[#F5F7FA] hover:text-[#101D30]'
                      }`
                    }
                  >
                    <div className="flex items-center gap-3">
                      <Icon className="w-4 h-4 shrink-0" />
                      <span>{item.name}</span>
                    </div>
                  </NavLink>
                );
              })}
            </div>
          ))}
        </div>

        {/* Bottom Sandbox Network Indicator */}
        <div className="p-3 border-t border-gray-100 bg-[#F5F7FA]">
          <div className="p-3 rounded-2xl bg-[#101D30] text-white space-y-1">
            <div className="flex items-center justify-between">
              <span className="text-[10px] font-mono font-bold text-[#35D6B0] uppercase flex items-center gap-1.5">
                <span className="w-2 h-2 rounded-full bg-[#35D6B0] animate-pulse" />
                <span>Simulated Network</span>
              </span>
              <ShieldCheck className="w-3.5 h-3.5 text-slate-400" />
            </div>
            <p className="text-[10px] text-slate-400 leading-tight">
              Test mode active • Atomic double-entry ledger enabled.
            </p>
          </div>
        </div>
      </aside>
    </>
  );
};

export default Sidebar;
