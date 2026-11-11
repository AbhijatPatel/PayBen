import React, { useState } from 'react';
import { walletService } from '../../services/walletService';
import { useAuth } from '../../context/AuthContext';
import { useToast } from '../../context/ToastContext';
import { X, Send, ArrowRight, CheckCircle2, Loader2, User } from 'lucide-react';

export const SendMoneyModal = ({ isOpen, onClose, initialRecipient = '' }) => {
  const { wallet, refreshBalance } = useAuth();
  const { addToast } = useToast();

  const [recipient, setRecipient] = useState(initialRecipient);
  const [amount, setAmount] = useState('');
  const [note, setNote] = useState('');
  const [loading, setLoading] = useState(false);
  const [result, setResult] = useState(null);

  if (!isOpen) return null;

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!recipient.trim()) {
      addToast('Please enter recipient mobile number or email', 'error');
      return;
    }
    const numAmount = parseFloat(amount);
    if (!numAmount || numAmount < 1) {
      addToast('Please enter a valid amount (minimum ₹1)', 'error');
      return;
    }
    if (numAmount > wallet.balance) {
      addToast('Insufficient wallet balance. Please add funds first.', 'error');
      return;
    }

    setLoading(true);
    try {
      const res = await walletService.transfer(recipient.trim(), numAmount, note.trim());
      if (res.success) {
        setResult(res.data);
        await refreshBalance();
        addToast('Money transferred successfully!', 'success');
      }
    } catch (err) {
      addToast(err.message, 'error');
    } finally {
      setLoading(false);
    }
  };

  const handleClose = () => {
    setResult(null);
    setAmount('');
    setNote('');
    onClose();
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm animate-in fade-in">
      <div className="bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden border border-gray-100">
        {/* Header */}
        <div className="flex items-center justify-between px-6 py-4 border-b border-gray-100">
          <div className="flex items-center gap-2">
            <div className="w-8 h-8 rounded-full bg-blue-50 text-[#1a73e8] flex items-center justify-center">
              <Send className="w-4 h-4" />
            </div>
            <h3 className="font-bold text-gray-900">Send Money via UPI</h3>
          </div>
          <button
            onClick={handleClose}
            className="p-1 rounded-full text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {result ? (
          /* Success Screen */
          <div className="p-6 text-center space-y-4">
            <div className="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto shadow-inner">
              <CheckCircle2 className="w-10 h-10" />
            </div>

            <div>
              <span className="text-xs uppercase tracking-wider text-emerald-700 font-bold bg-emerald-50 px-2.5 py-1 rounded-full">
                Payment Successful
              </span>
              <div className="text-3xl font-extrabold text-gray-900 mt-3">
                ₹{parseFloat(result.amount).toFixed(2)}
              </div>
              <p className="text-sm text-gray-500 mt-1">
                Sent to <span className="font-bold text-gray-800">{result.recipient_name}</span> ({result.recipient_mobile})
              </p>
            </div>

            <div className="bg-gray-50 rounded-2xl p-4 text-xs space-y-2 text-left">
              <div className="flex justify-between">
                <span className="text-gray-500">Transaction ID</span>
                <span className="font-mono font-bold text-gray-800">{result.transaction_id}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-500">Remaining Balance</span>
                <span className="font-bold text-gray-800">{result.formatted_balance}</span>
              </div>
            </div>

            <button
              onClick={handleClose}
              className="w-full py-3 bg-[#1a73e8] hover:bg-[#1557b0] text-white font-bold rounded-2xl transition shadow-sm"
            >
              Done
            </button>
          </div>
        ) : (
          /* Form */
          <form onSubmit={handleSubmit} className="p-6 space-y-4">
            <div>
              <label className="block text-xs font-semibold text-gray-600 mb-1">
                Recipient (Mobile Number, Email, or @payben UPI)
              </label>
              <div className="relative">
                <User className="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                <input
                  type="text"
                  required
                  placeholder="e.g. 9876543210 or user@test.com"
                  value={recipient}
                  onChange={(e) => setRecipient(e.target.value)}
                  className="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100 outline-none text-sm font-medium"
                />
              </div>
            </div>

            <div>
              <div className="flex justify-between items-center mb-1">
                <label className="text-xs font-semibold text-gray-600">Amount (₹)</label>
                <span className="text-xs text-gray-500">
                  Available: <span className="font-bold text-gray-800">{wallet.formatted_balance}</span>
                </span>
              </div>
              <div className="relative">
                <span className="absolute left-4 top-1/2 -translate-y-1/2 text-xl font-bold text-gray-500">
                  ₹
                </span>
                <input
                  type="number"
                  required
                  min="1"
                  step="any"
                  placeholder="0.00"
                  value={amount}
                  onChange={(e) => setAmount(e.target.value)}
                  className="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100 outline-none text-xl font-bold text-gray-900"
                />
              </div>

              {/* Quick chips */}
              <div className="flex gap-2 mt-2">
                {[50, 100, 200, 500].map((amt) => (
                  <button
                    key={amt}
                    type="button"
                    onClick={() => setAmount(amt.toString())}
                    className="text-xs font-semibold bg-gray-100 hover:bg-blue-50 hover:text-[#1a73e8] px-3 py-1.5 rounded-lg transition"
                  >
                    +₹{amt}
                  </button>
                ))}
              </div>
            </div>

            <div>
              <label className="block text-xs font-semibold text-gray-600 mb-1">
                Note (Optional)
              </label>
              <input
                type="text"
                placeholder="Dinner, Recharge, Gift..."
                value={note}
                onChange={(e) => setNote(e.target.value)}
                className="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100 outline-none text-sm font-medium"
              />
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full py-3 bg-[#1a73e8] hover:bg-[#1557b0] disabled:bg-blue-300 text-white font-bold rounded-2xl flex items-center justify-center gap-2 transition shadow-md shadow-blue-500/20"
            >
              {loading ? (
                <>
                  <Loader2 className="w-5 h-5 animate-spin" />
                  <span>Processing Payment...</span>
                </>
              ) : (
                <>
                  <span>Pay Now</span>
                  <ArrowRight className="w-4 h-4" />
                </>
              )}
            </button>
          </form>
        )}
      </div>
    </div>
  );
};
