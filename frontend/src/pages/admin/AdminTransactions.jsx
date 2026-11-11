import React, { useState, useEffect } from 'react';
import { adminService } from '../../services/adminService';
import { CreditCard, Loader2 } from 'lucide-react';

export const AdminTransactions = () => {
  const [transactions, setTransactions] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    adminService.getTransactions(100, 0).then((res) => {
      if (res.success) setTransactions(res.data);
      setLoading(false);
    });
  }, []);

  return (
    <div className="space-y-4">
      <div>
        <h2 className="text-xl font-bold text-white">All Platform Transactions</h2>
        <p className="text-xs text-slate-400">Complete audit log of all payments and settlements</p>
      </div>

      <div className="rounded-2xl bg-slate-800/80 border border-slate-700/60 p-5 overflow-hidden">
        {loading ? (
          <div className="py-12 flex justify-center">
            <Loader2 className="w-6 h-6 animate-spin text-purple-500" />
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs text-slate-300">
              <thead className="border-b border-slate-700 text-slate-400">
                <tr>
                  <th className="py-2.5 px-3">TX ID</th>
                  <th className="py-2.5 px-3">User</th>
                  <th className="py-2.5 px-3">Type</th>
                  <th className="py-2.5 px-3">Target</th>
                  <th className="py-2.5 px-3">Provider</th>
                  <th className="py-2.5 px-3">Amount</th>
                  <th className="py-2.5 px-3">Status</th>
                  <th className="py-2.5 px-3">Date</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-700/50">
                {transactions.map((tx) => (
                  <tr key={tx.id} className="hover:bg-slate-700/30">
                    <td className="py-2.5 px-3 font-mono font-bold text-slate-200">{tx.transaction_id}</td>
                    <td className="py-2.5 px-3">{tx.full_name || 'User'}</td>
                    <td className="py-2.5 px-3 capitalize">{tx.type?.replace('_', ' ')}</td>
                    <td className="py-2.5 px-3 font-mono">{tx.mobile_or_consumer_number}</td>
                    <td className="py-2.5 px-3">{tx.operator_or_provider}</td>
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
        )}
      </div>
    </div>
  );
};
