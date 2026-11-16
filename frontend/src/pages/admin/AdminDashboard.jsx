import React, { useState, useEffect } from 'react';
import { adminService } from '../../services/adminService';
import {
  Users,
  CreditCard,
  Wallet,
  TrendingUp,
  Loader2,
  ShieldAlert,
  CheckCircle2,
  XCircle,
  Cpu,
  Server
} from 'lucide-react';

export const AdminDashboard = () => {
  const [stats, setStats] = useState(null);
  const [fraudAlerts, setFraudAlerts] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    Promise.all([
      adminService.getDashboard(),
      adminService.getFraudAlerts(10, 0).catch(() => ({ success: false, data: [] }))
    ]).then(([dashRes, fraudRes]) => {
      if (dashRes.success) setStats(dashRes.data);
      if (fraudRes.success && fraudRes.data) setFraudAlerts(fraudRes.data);
      setLoading(false);
    });
  }, []);

  if (loading) {
    return (
      <div className="flex items-center justify-center py-20">
        <Loader2 className="w-8 h-8 animate-spin text-orange-500" />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h2 className="text-xl font-bold text-white">System Analytics & Security Telemetry</h2>
          <p className="text-xs text-slate-400">Real-time ledger audit, fraud detection engine and sandbox providers</p>
        </div>
        <div className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-950/80 border border-emerald-800 text-xs text-emerald-400 font-bold">
          <span className="w-2 h-2 rounded-full bg-emerald-400 animate-pulse" />
          <span>Fintech Sandbox Active</span>
        </div>
      </div>

      {/* Primary Metrics Cards */}
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
            <CreditCard className="w-4 h-4 text-orange-400" />
          </div>
          <div className="text-2xl font-black text-white">{stats?.total_transactions || 0}</div>
          <span className="text-[10px] text-orange-400">
            {stats?.successful_transactions || 0} Success • {stats?.failed_transactions || 0} Failed
          </span>
        </div>

        <div className="p-5 rounded-2xl bg-slate-800/80 border border-slate-700/60 space-y-2">
          <div className="flex justify-between items-center text-slate-400">
            <span className="text-xs font-semibold">Fraud Alerts</span>
            <ShieldAlert className="w-4 h-4 text-rose-400" />
          </div>
          <div className="text-2xl font-black text-rose-400">{stats?.fraud_alerts_count || 0}</div>
          <span className="text-[10px] text-slate-400">Rule-based triggers</span>
        </div>
      </div>

      {/* Sandbox Provider Configuration Matrix */}
      <div className="rounded-2xl bg-slate-800/80 border border-slate-700/60 p-5 space-y-3">
        <div className="flex items-center gap-2 text-white">
          <Server className="w-4 h-4 text-[#ff6200]" />
          <h3 className="text-sm font-bold">Zero-Paid Architecture • Sandbox Providers</h3>
        </div>
        <div className="grid grid-cols-2 sm:grid-cols-5 gap-3">
          {[
            { name: 'Payment Gateway', provider: stats?.sandbox_providers?.payment || 'Mock Provider', status: 'Active (Zero Cost)' },
            { name: 'OTP Service', provider: stats?.sandbox_providers?.otp || 'Local Hashed OTP', status: 'Free Sandbox' },
            { name: 'Email Delivery', provider: stats?.sandbox_providers?.email || 'Mock / Brevo Free', status: 'Zero Paid API' },
            { name: 'Mobile / DTH', provider: stats?.sandbox_providers?.recharge || 'Local Mock Provider', status: 'Sample Catalog' },
            { name: 'BBPS Bill Payments', provider: stats?.sandbox_providers?.bills || 'Local Mock BBPS', status: 'Sample Ledger' }
          ].map((p, idx) => (
            <div key={idx} className="p-3 bg-slate-900/60 rounded-xl border border-slate-700/40 space-y-1">
              <span className="text-[10px] uppercase font-bold text-slate-400 block">{p.name}</span>
              <span className="text-xs font-bold text-white block capitalize">{p.provider}</span>
              <span className="text-[10px] text-emerald-400 font-mono block">✓ {p.status}</span>
            </div>
          ))}
        </div>
      </div>

      {/* Fraud Detection Alerts */}
      {fraudAlerts?.length > 0 && (
        <div className="rounded-2xl bg-slate-800/80 border border-rose-900/40 p-5 space-y-3">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2">
              <ShieldAlert className="w-4 h-4 text-rose-400" />
              <h3 className="text-sm font-bold text-white">Security & Rule-Based Fraud Detection Alerts</h3>
            </div>
            <span className="text-[10px] font-bold text-rose-300 bg-rose-950 px-2 py-0.5 rounded-full border border-rose-800">
              Live Monitor
            </span>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs text-slate-300">
              <thead className="border-b border-slate-700 text-slate-400">
                <tr>
                  <th className="py-2 px-3">User</th>
                  <th className="py-2 px-3">Risk Level</th>
                  <th className="py-2 px-3">Score</th>
                  <th className="py-2 px-3">Triggered Rules</th>
                  <th className="py-2 px-3">Time</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-700/50">
                {fraudAlerts.slice(0, 5).map((f) => (
                  <tr key={f.id} className="hover:bg-slate-700/30">
                    <td className="py-2.5 px-3">
                      <span className="font-bold text-white block">{f.user_name || 'User'}</span>
                      <span className="text-[10px] text-slate-400 font-mono">{f.user_mobile}</span>
                    </td>
                    <td className="py-2.5 px-3">
                      <span className={`px-2 py-0.5 rounded-full text-[10px] font-bold uppercase ${
                        f.risk_level === 'HIGH' ? 'bg-rose-950 text-rose-400 border border-rose-800' : 'bg-amber-950 text-amber-400 border border-amber-800'
                      }`}>
                        {f.risk_level}
                      </span>
                    </td>
                    <td className="py-2.5 px-3 font-mono font-bold text-white">{f.risk_score}</td>
                    <td className="py-2.5 px-3 text-slate-300 max-w-xs">{f.triggered_rules}</td>
                    <td className="py-2.5 px-3 text-slate-400 font-mono text-[11px]">{f.created_at}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

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
