import React, { useState, useEffect } from 'react';
import { transactionService } from '../../services/transactionService';
import {
  History,
  Filter,
  Search,
  ArrowDownLeft,
  ArrowUpRight,
  CheckCircle2,
  Clock,
  XCircle,
  FileText,
  X,
  Printer
} from 'lucide-react';

export const Transactions = () => {
  const [transactions, setTransactions] = useState([]);
  const [stats, setStats] = useState(null);
  const [filterType, setFilterType] = useState('all');
  const [filterStatus, setFilterStatus] = useState('all');
  const [search, setSearch] = useState('');
  const [loading, setLoading] = useState(true);
  const [selectedTx, setSelectedTx] = useState(null);

  const loadData = async () => {
    setLoading(true);
    try {
      const res = await transactionService.list(filterType, filterStatus, 100, 0);
      if (res.success && res.data) {
        setTransactions(res.data.transactions || []);
        setStats(res.data.stats || null);
      }
    } catch (err) {
      // Ignored
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadData();
  }, [filterType, filterStatus]);

  const filtered = transactions.filter((t) => {
    if (!search.trim()) return true;
    const q = search.toLowerCase();
    return (
      t.transaction_id?.toLowerCase().includes(q) ||
      t.description?.toLowerCase().includes(q) ||
      t.mobile_or_consumer_number?.toLowerCase().includes(q) ||
      t.operator_or_provider?.toLowerCase().includes(q)
    );
  });

  return (
    <div className="space-y-6 max-w-5xl mx-auto pb-12">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 rounded-2xl bg-blue-50 text-[#1a73e8] flex items-center justify-center">
            <History className="w-5 h-5" />
          </div>
          <div>
            <h1 className="text-xl font-bold text-gray-900">Transaction History</h1>
            <p className="text-xs text-gray-500">Statements, receipts and payment records</p>
          </div>
        </div>

        {stats && (
          <div className="flex gap-2">
            <div className="bg-white px-3 py-1.5 rounded-xl border border-gray-100 text-xs shadow-xs">
              <span className="text-gray-400">Total: </span>
              <strong className="text-gray-800">{stats.total_transactions}</strong>
            </div>
            <div className="bg-white px-3 py-1.5 rounded-xl border border-gray-100 text-xs shadow-xs">
              <span className="text-gray-400">Success: </span>
              <strong className="text-emerald-600">{stats.successful_transactions}</strong>
            </div>
            <div className="bg-white px-3 py-1.5 rounded-xl border border-gray-100 text-xs shadow-xs">
              <span className="text-gray-400">Spent: </span>
              <strong className="text-gray-900">₹{parseFloat(stats.total_spent || 0).toFixed(2)}</strong>
            </div>
          </div>
        )}
      </div>

      {/* Filter and Search Bar */}
      <div className="bg-white p-4 rounded-3xl border border-gray-100 shadow-xs flex flex-col md:flex-row items-center gap-3 justify-between">
        <div className="relative w-full md:w-80">
          <Search className="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
          <input
            type="text"
            placeholder="Search by ID, number, provider..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full pl-10 pr-4 py-2 rounded-xl bg-gray-50 text-sm outline-none border border-transparent focus:border-[#1a73e8] focus:bg-white transition"
          />
        </div>

        <div className="flex flex-wrap items-center gap-2 w-full md:w-auto">
          <select
            value={filterType}
            onChange={(e) => setFilterType(e.target.value)}
            className="px-3 py-2 rounded-xl bg-gray-50 border border-gray-200 text-xs font-semibold text-gray-700 outline-none"
          >
            <option value="all">All Categories</option>
            <option value="mobile_recharge">Mobile Recharge</option>
            <option value="dth_recharge">DTH Recharge</option>
            <option value="electricity">Electricity</option>
            <option value="water">Water</option>
            <option value="gas">Gas</option>
            <option value="wallet_topup">Wallet Transfer/Topup</option>
          </select>

          <select
            value={filterStatus}
            onChange={(e) => setFilterStatus(e.target.value)}
            className="px-3 py-2 rounded-xl bg-gray-50 border border-gray-200 text-xs font-semibold text-gray-700 outline-none"
          >
            <option value="all">All Statuses</option>
            <option value="SUCCESS">Success</option>
            <option value="PENDING">Pending</option>
            <option value="FAILED">Failed</option>
          </select>
        </div>
      </div>

      {/* Transactions Table / List */}
      <div className="bg-white rounded-3xl border border-gray-100 shadow-xs overflow-hidden">
        {filtered.length > 0 ? (
          <div className="divide-y divide-gray-100">
            {filtered.map((tx) => (
              <div
                key={tx.id}
                onClick={() => setSelectedTx(tx)}
                className="p-4 hover:bg-gray-50/70 transition cursor-pointer flex items-center justify-between gap-4"
              >
                <div className="flex items-center gap-3">
                  <div className={`w-10 h-10 rounded-xl flex items-center justify-center shrink-0 ${
                    tx.status === 'SUCCESS' ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600'
                  }`}>
                    {tx.type === 'wallet_topup' && tx.description?.includes('Added') ? (
                      <ArrowDownLeft className="w-5 h-5" />
                    ) : (
                      <ArrowUpRight className="w-5 h-5" />
                    )}
                  </div>
                  <div>
                    <h3 className="text-sm font-bold text-gray-900">{tx.description || tx.operator_or_provider}</h3>
                    <p className="text-xs text-gray-400">
                      {new Date(tx.created_at).toLocaleDateString('en-IN', {
                        day: 'numeric',
                        month: 'short',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                      })}{' '}
                      • {tx.transaction_id}
                    </p>
                  </div>
                </div>

                <div className="text-right">
                  <div className={`text-base font-black ${
                    tx.type === 'wallet_topup' && tx.description?.includes('Added') ? 'text-emerald-600' : 'text-gray-900'
                  }`}>
                    {tx.type === 'wallet_topup' && tx.description?.includes('Added') ? '+' : '-'}₹{parseFloat(tx.amount).toFixed(2)}
                  </div>
                  <span className={`text-[10px] font-bold uppercase ${
                    tx.status === 'SUCCESS' ? 'text-emerald-600' : 'text-red-500'
                  }`}>
                    {tx.status}
                  </span>
                </div>
              </div>
            ))}
          </div>
        ) : (
          <div className="text-center py-12 text-gray-400 text-xs">
            No matching transactions found.
          </div>
        )}
      </div>

      {/* Transaction Details Modal */}
      {selectedTx && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm animate-in fade-in">
          <div className="bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden border border-gray-100 p-6 space-y-4">
            <div className="flex justify-between items-center">
              <span className="text-xs font-bold uppercase tracking-wider text-gray-400">Transaction Receipt</span>
              <button
                onClick={() => setSelectedTx(null)}
                className="p-1 rounded-full text-gray-400 hover:text-gray-700"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            <div className="text-center py-2 space-y-1">
              <div className="text-3xl font-black text-gray-900">₹{parseFloat(selectedTx.amount).toFixed(2)}</div>
              <p className="text-xs font-semibold text-emerald-600 flex items-center justify-center gap-1">
                <CheckCircle2 className="w-4 h-4" />
                <span>{selectedTx.status}</span>
              </p>
            </div>

            <div className="bg-gray-50 rounded-2xl p-4 text-xs space-y-2.5">
              <div className="flex justify-between">
                <span className="text-gray-500">Transaction ID</span>
                <span className="font-mono font-bold text-gray-900">{selectedTx.transaction_id}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-500">Service Category</span>
                <span className="font-semibold text-gray-800 capitalize">{selectedTx.type?.replace('_', ' ')}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-500">Provider / Operator</span>
                <span className="font-semibold text-gray-800">{selectedTx.operator_or_provider}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-500">Target / Consumer No</span>
                <span className="font-mono font-semibold text-gray-800">{selectedTx.mobile_or_consumer_number}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-500">Payment Mode</span>
                <span className="font-semibold text-gray-800">{selectedTx.payment_method}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-500">Timestamp</span>
                <span className="text-gray-800">{selectedTx.created_at}</span>
              </div>
            </div>

            <div className="flex gap-2 pt-2">
              <button
                onClick={() => window.print()}
                className="flex-1 py-2.5 rounded-xl border border-gray-200 text-gray-700 font-bold text-xs flex items-center justify-center gap-1.5 hover:bg-gray-50"
              >
                <Printer className="w-4 h-4" />
                <span>Print Receipt</span>
              </button>
              <button
                onClick={() => setSelectedTx(null)}
                className="flex-1 py-2.5 rounded-xl bg-[#1a73e8] hover:bg-[#1557b0] text-white font-bold text-xs"
              >
                Close
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
