import React, { useState } from 'react';
import { useFintech } from '../../context/FintechContext';
import { useToast } from '../../context/ToastContext';
import {
  Wallet,
  Plus,
  ArrowDownLeft,
  ArrowUpRight,
  Sparkles,
  Wifi,
  CreditCard,
  PieChart,
  ShieldCheck,
  User
} from 'lucide-react';

export const PocketMoney = () => {
  const { pocketMoney, topUpPocketMoney } = useFintech();
  const { addToast } = useToast();
  const [topUpAmount, setTopUpAmount] = useState('500');

  const handleTopUp = (e) => {
    e.preventDefault();
    const ok = topUpPocketMoney(topUpAmount);
    if (ok) {
      addToast(`₹${topUpAmount} sent to ${pocketMoney.account_name}!`, 'success');
      setTopUpAmount('');
    } else {
      addToast('Invalid amount', 'error');
    }
  };

  const percentSpent = Math.min(100, Math.round((pocketMoney.spent_this_month / pocketMoney.monthly_budget) * 100));

  return (
    <div className="max-w-4xl mx-auto space-y-6 pb-12 animate-in fade-in">
      {/* Banner */}
      <div className="bg-gradient-to-r from-[#ff6200] to-[#0047cc] rounded-3xl p-6 sm:p-8 text-white shadow-xl flex items-center justify-between relative overflow-hidden">
        <div className="relative z-10 space-y-2">
          <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md text-xs font-bold text-yellow-300 border border-white/20">
            <Sparkles className="w-3.5 h-3.5" />
            <span>Family & Teen Allowance Sub-Wallet</span>
          </div>
          <h2 className="text-2xl sm:text-3xl font-black tracking-tight">{pocketMoney.account_name}</h2>
          <p className="text-xs sm:text-sm text-orange-100 max-w-md">
            Safe, parent-controlled digital wallet for kids and teens. Set monthly budget caps and track every expense.
          </p>
        </div>
        <div className="hidden sm:block opacity-20">
          <Wallet className="w-32 h-32" />
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        {/* Left: Virtual Student Debit Card */}
        <div className="lg:col-span-5 relative h-56 rounded-3xl p-6 text-white shadow-2xl bg-gradient-to-tr from-orange-600 via-amber-600 to-yellow-600 border border-white/20 flex flex-col justify-between overflow-hidden">
          <div className="flex items-center justify-between">
            <span className="font-mono font-black text-xs tracking-widest uppercase bg-black/20 px-2 py-0.5 rounded-full">
              PAYBEN TEEN PASS
            </span>
            <Wifi className="w-5 h-5 rotate-90 opacity-80" />
          </div>

          <div className="flex items-center gap-2">
            <div className="w-10 h-7 rounded bg-amber-300 border border-yellow-100 shadow-inner" />
            <span className="text-sm font-mono tracking-widest font-bold">
              {pocketMoney.card_number}
            </span>
          </div>

          <div className="flex items-end justify-between">
            <div>
              <span className="text-[10px] text-amber-100 uppercase tracking-wider block">Available Balance</span>
              <div className="text-2xl font-black font-mono">
                ₹{pocketMoney.balance.toLocaleString('en-IN', { minimumFractionDigits: 2 })}
              </div>
            </div>
            <div className="text-right">
              <span className="text-[10px] text-amber-100 uppercase tracking-wider block">Account Holder</span>
              <span className="text-xs font-bold uppercase block">Aryan Patel</span>
            </div>
          </div>
        </div>

        {/* Right: Budget Tracking & Top-Up */}
        <div className="lg:col-span-7 bg-white rounded-3xl border border-gray-200/80 p-6 shadow-xs space-y-6">
          <div>
            <div className="flex justify-between items-center text-xs font-bold text-gray-700 mb-2">
              <span>Monthly Budget Utilization</span>
              <span>₹{pocketMoney.spent_this_month} / ₹{pocketMoney.monthly_budget} ({percentSpent}%)</span>
            </div>
            <div className="w-full h-3 bg-gray-100 rounded-full overflow-hidden">
              <div
                className="h-full bg-gradient-to-r from-emerald-500 via-amber-500 to-[#ff6200] transition-all duration-500"
                style={{ width: `${percentSpent}%` }}
              />
            </div>
          </div>

          {/* Quick Top-Up Box */}
          <form onSubmit={handleTopUp} className="space-y-3 pt-2">
            <label className="block text-xs font-bold text-gray-700 uppercase tracking-wider">
              Send Instant Allowance Top-Up
            </label>
            <div className="flex gap-2">
              <div className="relative flex-1">
                <span className="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-bold text-gray-400">₹</span>
                <input
                  type="number"
                  min="1"
                  value={topUpAmount}
                  onChange={(e) => setTopUpAmount(e.target.value)}
                  placeholder="Enter top-up amount"
                  className="w-full pl-8 pr-3 py-2.5 rounded-xl border border-gray-200 text-xs font-bold outline-none focus:border-[#0047cc]"
                />
              </div>
              <button
                type="submit"
                className="px-5 py-2.5 rounded-xl bg-[#0047cc] hover:bg-[#003db3] text-white text-xs font-bold shadow-sm transition"
              >
                Send Money
              </button>
            </div>
            <div className="flex gap-2">
              {[200, 500, 1000].map((amt) => (
                <button
                  key={amt}
                  type="button"
                  onClick={() => setTopUpAmount(String(amt))}
                  className="px-3 py-1 rounded-lg bg-gray-100 hover:bg-gray-200 text-[11px] font-bold text-gray-600 transition"
                >
                  +₹{amt}
                </button>
              ))}
            </div>
          </form>
        </div>
      </div>

      {/* Expense History */}
      <div className="bg-white rounded-3xl border border-gray-200/80 p-6 shadow-xs space-y-4">
        <h3 className="text-base font-bold text-gray-900">Allowance Activity & Spends</h3>
        <div className="divide-y divide-gray-100">
          {pocketMoney.transactions.map((tx) => (
            <div key={tx.id} className="py-3 flex items-center justify-between">
              <div className="flex items-center gap-3">
                <div
                  className={`w-9 h-9 rounded-xl flex items-center justify-center ${
                    tx.type === 'credit'
                      ? 'bg-emerald-50 text-emerald-600'
                      : 'bg-rose-50 text-rose-600'
                  }`}
                >
                  {tx.type === 'credit' ? <ArrowDownLeft className="w-4 h-4" /> : <ArrowUpRight className="w-4 h-4" />}
                </div>
                <div>
                  <h4 className="text-xs font-bold text-gray-900">{tx.title}</h4>
                  <p className="text-[10px] text-gray-400 font-mono">{tx.date}</p>
                </div>
              </div>
              <span
                className={`text-xs font-black font-mono ${
                  tx.type === 'credit' ? 'text-emerald-600' : 'text-gray-900'
                }`}
              >
                {tx.type === 'credit' ? '+' : '-'}₹{tx.amount}
              </span>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};
