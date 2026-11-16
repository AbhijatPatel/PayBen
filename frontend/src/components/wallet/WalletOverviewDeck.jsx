import React from 'react';
import { Link } from 'react-router-dom';
import { useSecurity } from '../../context/SecurityContext';
import {
  Wallet,
  ArrowDownLeft,
  ArrowUpRight,
  Plus,
  Eye,
  EyeOff,
  History,
  ShieldCheck,
  CheckCircle2
} from 'lucide-react';

/**
 * WalletOverviewDeck — Neo-Fintech Financial Pulse Cards
 * Shows Wallet Balance, Money Received, Money Spent, and 1-Click Add Money shortcut.
 * Displays live data from backend APIs.
 */
export const WalletOverviewDeck = ({ wallet, stats, user, onAddMoney }) => {
  const { hideBalance, toggleHideBalance } = useSecurity();

  const balanceNumber = parseFloat(wallet?.balance || 0);
  const formattedBalance = wallet?.formatted_balance || `₹${balanceNumber.toFixed(2)}`;
  const totalReceived = parseFloat(stats?.total_received || 0);
  const totalSpent = parseFloat(stats?.total_spent || 0);
  const successCount = parseInt(stats?.successful_transactions || 0, 10);
  const totalCount = parseInt(stats?.total_transactions || 0, 10);

  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      {/* 1. Main Wallet Balance Card (Featured) */}
      <div className="sm:col-span-2 lg:col-span-2 bg-[#101D30] text-white rounded-3xl p-6 border border-slate-800 shadow-md flex flex-col justify-between relative overflow-hidden group">
        {/* Subtle mint ambient corner */}
        <div className="absolute -right-12 -top-12 w-40 h-40 rounded-full bg-[#35D6B0]/15 blur-2xl pointer-events-none" />

        <div>
          <div className="flex items-center justify-between mb-3">
            <div className="flex items-center gap-2">
              <div className="w-8 h-8 rounded-xl bg-white/10 flex items-center justify-center text-[#35D6B0]">
                <Wallet className="w-4 h-4" />
              </div>
              <span className="text-xs font-bold text-slate-300 uppercase tracking-wider">
                Simulated Wallet Balance
              </span>
            </div>

            <button
              onClick={toggleHideBalance}
              className="text-slate-400 hover:text-white p-1 rounded-lg transition"
              title={hideBalance ? 'Show Balance' : 'Hide Balance'}
              aria-label="Toggle Balance Visibility"
            >
              {hideBalance ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
            </button>
          </div>

          <div className="text-3xl sm:text-4xl font-black font-mono tracking-tight text-white mt-1">
            {hideBalance ? '₹ ••••••' : formattedBalance}
          </div>

          <div className="mt-2 flex flex-wrap items-center gap-2 text-[11px] text-slate-400 font-mono">
            <span>UPI ID:</span>
            <strong className="text-[#35D6B0]">{user?.upi_id || `${user?.mobile || '9876543210'}@payben`}</strong>
          </div>
        </div>

        <div className="mt-6 pt-4 border-t border-slate-800/80 flex items-center justify-between">
          <div className="flex items-center gap-1.5 text-[11px] text-slate-400">
            <ShieldCheck className="w-3.5 h-3.5 text-[#35D6B0]" />
            <span>Integer math ledger precision</span>
          </div>

          <button
            onClick={onAddMoney}
            className="inline-flex items-center gap-1.5 bg-[#35D6B0] hover:bg-[#2dc29e] text-[#101D30] font-extrabold text-xs px-3.5 py-2 rounded-xl shadow-xs transition active:scale-95"
          >
            <Plus className="w-3.5 h-3.5" />
            <span>Add Money</span>
          </button>
        </div>
      </div>

      {/* 2. Total Money Received */}
      <div className="bg-white rounded-3xl p-5 border border-gray-200/80 shadow-xs flex flex-col justify-between">
        <div className="flex items-center justify-between mb-2">
          <div className="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
            <ArrowDownLeft className="w-5 h-5" />
          </div>
          <span className="text-[10px] font-bold uppercase tracking-wider text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
            Inflow
          </span>
        </div>

        <div>
          <span className="text-xs font-bold text-gray-500 uppercase tracking-wider block">
            Money Received
          </span>
          <div className="text-2xl font-black font-mono text-emerald-600 mt-1">
            {hideBalance ? '₹ ••••' : `₹${totalReceived.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`}
          </div>
        </div>

        <p className="text-[11px] text-gray-400 mt-3 pt-2 border-t border-gray-100 flex items-center gap-1">
          <CheckCircle2 className="w-3 h-3 text-emerald-500" />
          <span>Topups & incoming transfers</span>
        </p>
      </div>

      {/* 3. Total Money Spent */}
      <div className="bg-white rounded-3xl p-5 border border-gray-200/80 shadow-xs flex flex-col justify-between">
        <div className="flex items-center justify-between mb-2">
          <div className="w-10 h-10 rounded-2xl bg-slate-100 text-[#101D30] flex items-center justify-center">
            <ArrowUpRight className="w-5 h-5" />
          </div>
          <span className="text-[10px] font-bold uppercase tracking-wider text-slate-700 bg-slate-100 px-2 py-0.5 rounded-full border border-slate-200">
            Outflow
          </span>
        </div>

        <div>
          <span className="text-xs font-bold text-gray-500 uppercase tracking-wider block">
            Money Spent
          </span>
          <div className="text-2xl font-black font-mono text-[#101D30] mt-1">
            {hideBalance ? '₹ ••••' : `₹${totalSpent.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`}
          </div>
        </div>

        <div className="mt-3 pt-2 border-t border-gray-100 flex items-center justify-between text-[11px] text-gray-400">
          <span>{successCount} settled</span>
          <Link to="/transactions" className="font-bold text-[#101D30] hover:underline flex items-center gap-0.5">
            <History className="w-3 h-3" />
            <span>Passbook</span>
          </Link>
        </div>
      </div>
    </div>
  );
};

export default WalletOverviewDeck;
