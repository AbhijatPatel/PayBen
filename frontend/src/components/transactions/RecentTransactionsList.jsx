import React from 'react';
import { Link } from 'react-router-dom';
import {
  ArrowDownLeft,
  ArrowUpRight,
  ChevronRight,
  History,
  Smartphone,
  Tv,
  Zap,
  Droplet,
  Flame,
  Wallet,
  AlertCircle,
  Clock,
  CheckCircle2,
  XCircle
} from 'lucide-react';

/**
 * Helper to select appropriate icon for transaction type
 */
const getTxIcon = (type) => {
  switch (type) {
    case 'wallet_topup':
      return { icon: Wallet, bg: 'bg-emerald-50', text: 'text-emerald-600' };
    case 'mobile_recharge':
      return { icon: Smartphone, bg: 'bg-blue-50', text: 'text-blue-600' };
    case 'dth_recharge':
      return { icon: Tv, bg: 'bg-indigo-50', text: 'text-indigo-600' };
    case 'electricity':
      return { icon: Zap, bg: 'bg-amber-50', text: 'text-amber-600' };
    case 'water':
      return { icon: Droplet, bg: 'bg-cyan-50', text: 'text-cyan-600' };
    case 'gas':
      return { icon: Flame, bg: 'bg-rose-50', text: 'text-rose-600' };
    default:
      return { icon: ArrowUpRight, bg: 'bg-slate-100', text: 'text-[#101D30]' };
  }
};

/**
 * RecentTransactionsList — Neo-Fintech Transaction Activity Table
 * Displays actual backend transactions with skeleton loading and empty states.
 */
export const RecentTransactionsList = ({ transactions = [], loading = false, error = null }) => {
  return (
    <section className="bg-white rounded-3xl p-6 border border-gray-200/80 shadow-xs space-y-4">
      {/* Table Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-2">
          <div className="w-8 h-8 rounded-xl bg-[#101D30] text-[#35D6B0] flex items-center justify-center shrink-0">
            <History className="w-4 h-4" />
          </div>
          <div>
            <h2 className="text-base font-extrabold text-[#101D30]">Recent Transactions</h2>
            <p className="text-xs text-gray-500">Live payment statements and UPI transfers</p>
          </div>
        </div>

        <Link
          to="/transactions"
          className="inline-flex items-center gap-1 text-xs font-bold text-[#101D30] hover:text-[#35D6B0] transition-colors"
        >
          <span>View All Passbook</span>
          <ChevronRight className="w-4 h-4" />
        </Link>
      </div>

      {/* Loading Skeleton */}
      {loading && (
        <div className="divide-y divide-gray-100 animate-pulse">
          {[1, 2, 3, 4].map((i) => (
            <div key={i} className="py-3.5 flex items-center justify-between gap-4">
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-2xl bg-gray-200 shrink-0" />
                <div className="space-y-1.5">
                  <div className="w-36 h-4 bg-gray-200 rounded" />
                  <div className="w-24 h-3 bg-gray-100 rounded" />
                </div>
              </div>
              <div className="text-right space-y-1">
                <div className="w-16 h-4 bg-gray-200 rounded ml-auto" />
                <div className="w-10 h-3 bg-gray-100 rounded ml-auto" />
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Error State */}
      {!loading && error && (
        <div className="p-4 rounded-2xl bg-rose-50 border border-rose-100 flex items-center gap-3 text-xs text-rose-700">
          <AlertCircle className="w-5 h-5 shrink-0 text-rose-600" />
          <span>Unable to sync latest transaction feed. Please refresh the page.</span>
        </div>
      )}

      {/* Populated List */}
      {!loading && !error && transactions.length > 0 && (
        <div className="divide-y divide-gray-100">
          {transactions.map((tx) => {
            const { icon: TypeIcon, bg: iconBg, text: iconColor } = getTxIcon(tx.type);
            const isCredit = tx.type === 'wallet_topup' || tx.description?.toLowerCase().includes('received');
            const amountNum = parseFloat(tx.amount || 0);

            // Format date safely
            let formattedDate = 'Recent';
            try {
              if (tx.created_at) {
                formattedDate = new Date(tx.created_at).toLocaleDateString('en-IN', {
                  day: 'numeric',
                  month: 'short',
                  hour: '2-digit',
                  minute: '2-digit'
                });
              }
            } catch {
              formattedDate = tx.created_at || 'Recent';
            }

            return (
              <div
                key={tx.id || tx.transaction_id}
                className="py-3.5 flex items-center justify-between gap-4 hover:bg-[#F5F7FA] px-2 rounded-2xl transition-colors"
              >
                {/* Left: Type Icon & Recipient / Details */}
                <div className="flex items-center gap-3 min-w-0">
                  <div
                    className={`w-11 h-11 rounded-2xl ${iconBg} ${iconColor} flex items-center justify-center shrink-0 shadow-xs`}
                  >
                    <TypeIcon className="w-5 h-5" />
                  </div>
                  <div className="min-w-0">
                    <p className="text-sm font-bold text-[#101D30] truncate">
                      {tx.operator_or_provider || tx.description || 'Utility Payment'}
                    </p>
                    <div className="flex items-center gap-2 text-[11px] text-gray-400 font-mono mt-0.5">
                      <span>{formattedDate}</span>
                      <span>•</span>
                      <span className="truncate max-w-[120px] sm:max-w-none">{tx.transaction_id}</span>
                    </div>
                  </div>
                </div>

                {/* Right: Amount & Status Badge */}
                <div className="text-right shrink-0">
                  <div
                    className={`text-sm font-black font-mono ${
                      isCredit ? 'text-emerald-600' : 'text-[#101D30]'
                    }`}
                  >
                    {isCredit ? '+' : '-'}₹{amountNum.toFixed(2)}
                  </div>

                  <div className="inline-flex items-center gap-1 mt-0.5">
                    {tx.status === 'SUCCESS' ? (
                      <span className="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.2 rounded-full border border-emerald-200 flex items-center gap-0.5">
                        <CheckCircle2 className="w-2.5 h-2.5 text-emerald-600" />
                        <span>SUCCESS</span>
                      </span>
                    ) : tx.status === 'PENDING' ? (
                      <span className="text-[10px] font-bold text-amber-700 bg-amber-50 px-2 py-0.2 rounded-full border border-amber-200 flex items-center gap-0.5">
                        <Clock className="w-2.5 h-2.5 text-amber-600" />
                        <span>PENDING</span>
                      </span>
                    ) : (
                      <span className="text-[10px] font-bold text-rose-700 bg-rose-50 px-2 py-0.2 rounded-full border border-rose-200 flex items-center gap-0.5">
                        <XCircle className="w-2.5 h-2.5 text-rose-600" />
                        <span>FAILED</span>
                      </span>
                    )}
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      )}

      {/* Empty State */}
      {!loading && !error && transactions.length === 0 && (
        <div className="text-center py-10 px-4 bg-[#F5F7FA] rounded-2xl border border-dashed border-gray-200 space-y-3">
          <div className="w-12 h-12 rounded-2xl bg-white text-gray-400 flex items-center justify-center mx-auto shadow-xs">
            <History className="w-6 h-6" />
          </div>
          <div>
            <h4 className="text-sm font-bold text-[#101D30]">No Transactions Yet</h4>
            <p className="text-xs text-gray-500 max-w-sm mx-auto mt-1">
              Complete your first simulated mobile top-up or electricity bill payment to see live ledger statements.
            </p>
          </div>
          <Link
            to="/recharge/mobile"
            className="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[#101D30] text-[#35D6B0] text-xs font-bold hover:bg-[#1a2f4d] transition"
          >
            <span>Recharge Mobile Now</span>
            <ChevronRight className="w-3.5 h-3.5" />
          </Link>
        </div>
      )}
    </section>
  );
};

export default RecentTransactionsList;
