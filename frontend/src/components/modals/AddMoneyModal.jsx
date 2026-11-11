import React, { useState } from 'react';
import { walletService } from '../../services/walletService';
import { useAuth } from '../../context/AuthContext';
import { useToast } from '../../context/ToastContext';
import { X, Plus, CreditCard, CheckCircle2, Loader2, Sparkles } from 'lucide-react';

export const AddMoneyModal = ({ isOpen, onClose }) => {
  const { refreshBalance } = useAuth();
  const { addToast } = useToast();

  const [amount, setAmount] = useState('500');
  const [method, setMethod] = useState('UPI');
  const [loading, setLoading] = useState(false);
  const [successData, setSuccessData] = useState(null);

  if (!isOpen) return null;

  const handleSubmit = async (e) => {
    e.preventDefault();
    const num = parseFloat(amount);
    if (!num || num < 1) {
      addToast('Minimum top-up amount is ₹1', 'error');
      return;
    }

    setLoading(true);
    try {
      const res = await walletService.addMoney(num, method);
      if (res.success) {
        setSuccessData(res.data);
        await refreshBalance();
        addToast(`₹${num} added to wallet successfully!`, 'success');
      }
    } catch (err) {
      addToast(err.message, 'error');
    } finally {
      setLoading(false);
    }
  };

  const handleClose = () => {
    setSuccessData(null);
    onClose();
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm animate-in fade-in">
      <div className="bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden border border-gray-100">
        <div className="flex items-center justify-between px-6 py-4 border-b border-gray-100">
          <div className="flex items-center gap-2">
            <div className="w-8 h-8 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center">
              <Plus className="w-4 h-4" />
            </div>
            <h3 className="font-bold text-gray-900">Add Money to Wallet</h3>
          </div>
          <button
            onClick={handleClose}
            className="p-1 rounded-full text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {successData ? (
          <div className="p-6 text-center space-y-4">
            <div className="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto">
              <CheckCircle2 className="w-10 h-10" />
            </div>
            <div>
              <span className="text-xs uppercase tracking-wider text-emerald-700 font-bold bg-emerald-50 px-2.5 py-1 rounded-full">
                Wallet Top-up Successful
              </span>
              <div className="text-3xl font-extrabold text-gray-900 mt-3">
                ₹{parseFloat(successData.amount).toFixed(2)}
              </div>
              <p className="text-sm text-gray-500 mt-1">
                New Available Balance: <span className="font-bold text-gray-800">{successData.formatted_balance}</span>
              </p>
            </div>
            <button
              onClick={handleClose}
              className="w-full py-3 bg-[#1a73e8] hover:bg-[#1557b0] text-white font-bold rounded-2xl transition"
            >
              Continue
            </button>
          </div>
        ) : (
          <form onSubmit={handleSubmit} className="p-6 space-y-4">
            <div>
              <label className="block text-xs font-semibold text-gray-600 mb-1">Enter Amount</label>
              <div className="relative">
                <span className="absolute left-4 top-1/2 -translate-y-1/2 text-2xl font-bold text-gray-500">
                  ₹
                </span>
                <input
                  type="number"
                  required
                  min="1"
                  step="any"
                  value={amount}
                  onChange={(e) => setAmount(e.target.value)}
                  className="w-full pl-10 pr-4 py-3 rounded-2xl border border-gray-200 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100 outline-none text-2xl font-bold text-gray-900"
                />
              </div>

              {/* Quick Amount Chips */}
              <div className="grid grid-cols-4 gap-2 mt-3">
                {[100, 500, 1000, 2000].map((val) => (
                  <button
                    key={val}
                    type="button"
                    onClick={() => setAmount(val.toString())}
                    className={`py-2 text-xs font-bold rounded-xl border transition ${
                      amount === val.toString()
                        ? 'bg-blue-50 border-[#1a73e8] text-[#1a73e8]'
                        : 'bg-gray-50 border-gray-200 text-gray-700 hover:bg-gray-100'
                    }`}
                  >
                    +₹{val}
                  </button>
                ))}
              </div>
            </div>

            <div>
              <label className="block text-xs font-semibold text-gray-600 mb-1.5">Payment Method</label>
              <div className="grid grid-cols-3 gap-2">
                {['UPI', 'Net Banking', 'Debit Card'].map((m) => (
                  <button
                    key={m}
                    type="button"
                    onClick={() => setMethod(m)}
                    className={`p-2.5 rounded-xl border text-xs font-bold text-center transition ${
                      method === m
                        ? 'bg-blue-50 border-[#1a73e8] text-[#1a73e8]'
                        : 'border-gray-200 text-gray-600 hover:bg-gray-50'
                    }`}
                  >
                    {m}
                  </button>
                ))}
              </div>
            </div>

            <div className="p-3 bg-emerald-50 border border-emerald-100 rounded-xl flex items-center gap-2 text-xs text-emerald-800">
              <Sparkles className="w-4 h-4 text-emerald-600 shrink-0" />
              <span>Simulated Instant Deposit • Zero transaction fees</span>
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full py-3 bg-[#1a73e8] hover:bg-[#1557b0] disabled:bg-blue-300 text-white font-bold rounded-2xl flex items-center justify-center gap-2 transition shadow-md shadow-blue-500/20"
            >
              {loading ? (
                <>
                  <Loader2 className="w-5 h-5 animate-spin" />
                  <span>Adding Money...</span>
                </>
              ) : (
                <span>Add ₹{parseFloat(amount || 0).toFixed(2)} to Wallet</span>
              )}
            </button>
          </form>
        )}
      </div>
    </div>
  );
};

