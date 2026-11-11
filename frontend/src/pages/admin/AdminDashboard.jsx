import React, { useState, useEffect } from 'react';
import { adminService } from '../../services/adminService';
import { Users, CreditCard, Wallet, TrendingUp, Loader2 } from 'lucide-react';

export const AdminDashboard = () => {
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    adminService.getDashboard().then((res) => {
      if (res.success) setStats(res.data);
      setLoading(false);
    });
  }, []);

  if (loading) {
    return (
      <div className="flex items-center justify-center py-20">
        <Loader2 className="w-8 h-8 animate-spin text-purple-500" />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-xl font-bold text-white">System Analytics & Health</h2>
        <p className="text-xs text-slate-400">Real-time telemetry across users, wallets and settlement logs</p>
      </div>

      {/* Metrics Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="p-5 rounded-2xl bg-slate-800/80 border border-slate-700/60 space-y-2">
          <div className="flex justify-between items-center text-slate-400">
            <span className="text-xs font-semibold">Registered Users</span>
            <Users className="w-4 h-4 text-blue-400" />
          </div>
          <div className="text-2xl font-black text-white">{stats?.total_users || 0}</div>
          <span className="text-[10px] text-emerald-400">Active accounts</span>
        </div>

        <div className="p-5 rounded-2xl bg-slate-800/80 border border-slate-700/60 space-y-2">
          <div className="flex justify-between items-center text-slate-400">
            <span className="text-xs font-semibold">Platform Volume</span>
            <TrendingUp className="w-4 h-4 text-emerald-400" />
          </div>
          <div className="text-2xl font-black text-white">₹{parseFloat(stats?.total_volume || 0).toFixed(2)}</div>
          <span className="text-[10px] text-emerald-400">Completed GMV</span>
        </div>

        <div className="p-5 rounded-2xl bg-slate-800/80 border border-slate-700/60 space-y-2">
          <div className="flex justify-between items-center text-slate-400">
            <span className="text-xs font-semibold">Total Transactions</span>
            <CreditCard className="w-4 h-4 text-purple-400" />
          </div>
          <div className="text-2xl font-black text-white">{stats?.total_transactions || 0}</div>
          <span className="text-[10px] text-purple-400">Ledger entries</span>
        </div>

        <div className="p-5 rounded-2xl bg-slate-800/80 border border-slate-700/60 space-y-2">
          <div className="flex justify-between items-center text-slate-400">
            <span className="text-xs font-semibold">Wallet Reserves</span>
            <Wallet className="w-4 h-4 text-amber-400" />
          </div>
          <div className="text-2xl font-black text-white">₹{parseFloat(stats?.wallet_holdings || 0).toFixed(2)}</div>
          <span className="text-[10px] text-amber-400">Total liability</span>
        </div>
      </div>

      {/* Recent Activity Table */}
      <div className="rounded-2xl bg-slate-800/80 border border-slate-700/60 p-5 space-y-3">
        <h3 className="text-sm font-bold text-white">Recent System Transactions</h3>

        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs text-slate-300">
            <thead className="border-b border-slate-700 text-slate-400">
              <tr>
                <th className="py-2.5 px-3">TX ID</th>
                <th className="py-2.5 px-3">User</th>
                <th className="py-2.5 px-3">Type</th>
                <th className="py-2.5 px-3">Amount</th>
                <th className="py-2.5 px-3">Status</th>
                <th className="py-2.5 px-3">Date</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-700/50">
              {stats?.recent_transactions?.map((tx) => (
                <tr key={tx.id} className="hover:bg-slate-700/30">
                  <td className="py-2.5 px-3 font-mono font-bold text-slate-200">{tx.transaction_id}</td>
                  <td className="py-2.5 px-3">{tx.full_name || 'User'}</td>
                  <td className="py-2.5 px-3 capitalize">{tx.type?.replace('_', ' ')}</td>
                  <td className="py-2.5 px-3 font-bold text-white">₹{parseFloat(tx.amount).toFixed(2)}</td>
                  <td className="py-2.5 px-3">
                    <span className="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-950 text-emerald-400 border border-emerald-800/50">
                      {tx.status}
                    </span>
                  </td>
                  <td className="py-2.5 px-3 text-slate-400">{tx.created_at}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};
