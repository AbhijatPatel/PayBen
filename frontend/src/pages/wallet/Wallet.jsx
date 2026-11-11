import React, { useState, useEffect } from 'react';
import { useAuth } from '../../context/AuthContext';
import { walletService } from '../../services/walletService';
import { useToast } from '../../context/ToastContext';
import {
  Wallet as WalletIcon,
  Plus,
  ArrowDownLeft,
  ArrowUpRight,
  Copy,
  ShieldCheck,
  Sparkles,
  History,
  QrCode
} from 'lucide-react';
import { AddMoneyModal } from '../../components/modals/AddMoneyModal';
import { QrModal } from '../../components/modals/QrModal';

export const WalletPage = () => {
  const { user, wallet } = useAuth();
  const { addToast } = useToast();

  const [history, setHistory] = useState([]);
  const [loading, setLoading] = useState(true);
  const [addOpen, setAddOpen] = useState(false);
  const [qrOpen, setQrOpen] = useState(false);

  const loadHistory = async () => {
    try {
      const res = await walletService.getHistory(50, 0);
      if (res.success && res.data) {
        setHistory(res.data);
      }
    } catch (err) {
      // Ignored
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadHistory();
  }, []);

  const handleCopyUpi = () => {
    if (user?.upi_id) {
      navigator.clipboard.writeText(user.upi_id);
      addToast('UPI ID copied to clipboard!', 'success');
    }
  };

  return (
    <div className="space-y-6 max-w-4xl mx-auto pb-10">
      {/* Wallet Card */}
      <div className="rounded-3xl bg-gradient-to-tr from-[#1a73e8] to-[#1557b0] text-white p-6 sm:p-8 shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div className="space-y-2">
          <div className="flex items-center gap-2">
            <WalletIcon className="w-6 h-6 text-blue-200" />
            <span className="text-xs uppercase tracking-wider text-blue-200 font-bold">PayBen Digital Wallet</span>
          </div>
          <div className="text-3xl sm:text-4xl font-black tracking-tight">
            {wallet?.formatted_balance || '₹0.00'}
          </div>
          <div className="flex items-center gap-2 pt-1">
            <span className="text-xs text-blue-100 font-mono bg-white/10 px-2.5 py-1 rounded-lg">
              {user?.upi_id}
            </span>
            <button
              onClick={handleCopyUpi}
              className="p-1 hover:bg-white/20 rounded-lg text-blue-200 hover:text-white transition"
              title="Copy UPI ID"
            >
              <Copy className="w-4 h-4" />
            </button>
          </div>
        </div>

        <div className="flex gap-3">
          <button
            onClick={() => setAddOpen(true)}
            className="px-5 py-3 rounded-2xl bg-white text-gray-900 font-bold text-sm flex items-center gap-2 hover:bg-gray-100 transition shadow-md"
          >
            <Plus className="w-4 h-4 text-[#1a73e8]" />
            <span>Add Funds</span>
          </button>
          <button
            onClick={() => setQrOpen(true)}
            className="p-3 rounded-2xl bg-white/15 hover:bg-white/25 text-white transition backdrop-blur-md border border-white/20"
            title="My QR Code"
          >
            <QrCode className="w-5 h-5" />
          </button>
        </div>
      </div>

      {/* Ledger History */}
      <div className="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs space-y-4">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            <History className="w-5 h-5 text-gray-600" />
            <h2 className="text-base font-bold text-gray-900">Wallet Ledger History</h2>
          </div>
          <span className="text-xs text-gray-400">Atomic Double-Entry</span>
        </div>

        {history.length > 0 ? (
          <div className="divide-y divide-gray-100">
            {history.map((item) => (
              <div key={item.id} className="py-3.5 flex items-center justify-between gap-4">
                <div className="flex items-center gap-3">
                  <div className={`w-10 h-10 rounded-xl flex items-center justify-center shrink-0 ${
                    item.type === 'credit' ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600'
                  }`}>
                    {item.type === 'credit' ? (
                      <ArrowDownLeft className="w-5 h-5" />
                    ) : (
                      <ArrowUpRight className="w-5 h-5" />
                    )}
                  </div>
                  <div>
                    <p className="text-sm font-bold text-gray-900">{item.description}</p>
                    <p className="text-xs text-gray-400">
                      {new Date(item.created_at).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })} • Ref: {item.reference_id}
                    </p>
                  </div>
                </div>

                <div className="text-right">
                  <div className={`text-sm font-black ${
                    item.type === 'credit' ? 'text-emerald-600' : 'text-gray-900'
                  }`}>
                    {item.type === 'credit' ? '+' : '-'}₹{parseFloat(item.amount).toFixed(2)}
                  </div>
                  <span className="text-[10px] text-gray-400">
                    Bal: ₹{parseFloat(item.balance_after).toFixed(2)}
                  </span>
                </div>
              </div>
            ))}
          </div>
        ) : (
          <div className="text-center py-8 text-gray-400 text-xs">
            No ledger entries found.
          </div>
        )}
      </div>

      <AddMoneyModal isOpen={addOpen} onClose={() => { setAddOpen(false); loadHistory(); }} />
      <QrModal isOpen={qrOpen} onClose={() => setQrOpen(false)} />
    </div>
  );
};

