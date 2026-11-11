import React, { useState, useEffect } from 'react';
import { rechargeService } from '../../services/rechargeService';
import { useAuth } from '../../context/AuthContext';
import { useToast } from '../../context/ToastContext';
import { Tv, CheckCircle2, Loader2, ArrowRight } from 'lucide-react';

export const DthRecharge = () => {
  const { wallet, refreshBalance } = useAuth();
  const { addToast } = useToast();

  const [operators, setOperators] = useState([]);
  const [selectedOperator, setSelectedOperator] = useState('');
  const [subscriberId, setSubscriberId] = useState('');
  const [amount, setAmount] = useState('350');
  const [loading, setLoading] = useState(false);
  const [receipt, setReceipt] = useState(null);

  useEffect(() => {
    rechargeService.getOperators('dth').then((res) => {
      if (res.success && res.data.length > 0) {
        setOperators(res.data);
        setSelectedOperator(res.data[0].id.toString());
      }
    });
  }, []);

  const handleRecharge = async (e) => {
    e.preventDefault();
    if (subscriberId.length < 5) {
      addToast('Please enter a valid Subscriber ID / VC Number', 'error');
      return;
    }
    const num = parseFloat(amount);
    if (!num || num < 50) {
      addToast('Minimum DTH recharge amount is ₹50', 'error');
      return;
    }
    if (num > wallet.balance) {
      addToast('Insufficient wallet balance. Please add funds first.', 'error');
      return;
    }

    setLoading(true);
    try {
      const res = await rechargeService.dthRecharge(
        subscriberId,
        parseInt(selectedOperator),
        num
      );
      if (res.success) {
        setReceipt(res.data);
        await refreshBalance();
        addToast('DTH recharge successful!', 'success');
      }
    } catch (err) {
      addToast(err.message, 'error');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="max-w-xl mx-auto space-y-6 pb-12">
      <div className="flex items-center gap-3">
        <div className="w-10 h-10 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center">
          <Tv className="w-5 h-5" />
        </div>
        <div>
          <h1 className="text-xl font-bold text-gray-900">DTH TV Recharge</h1>
          <p className="text-xs text-gray-500">Tata Play, Airtel DTH, Dish TV, Sun Direct, D2H</p>
        </div>
      </div>

      {receipt ? (
        <div className="bg-white rounded-3xl p-8 border border-gray-100 shadow-xl text-center space-y-4">
          <div className="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto">
            <CheckCircle2 className="w-10 h-10" />
          </div>
          <div>
            <span className="text-xs uppercase tracking-wider text-emerald-700 font-bold bg-emerald-50 px-3 py-1 rounded-full">
              DTH Recharge Successful
            </span>
            <div className="text-3xl font-black text-gray-900 mt-3">
              ₹{parseFloat(receipt.amount).toFixed(2)}
            </div>
            <p className="text-sm text-gray-500 mt-1">
              Subscriber ID: <strong className="text-gray-800">{receipt.target_number}</strong> ({receipt.operator})
            </p>
          </div>

          <div className="bg-gray-50 rounded-2xl p-4 text-xs space-y-2 text-left">
            <div className="flex justify-between">
              <span className="text-gray-500">Transaction ID</span>
              <span className="font-mono font-bold text-gray-800">{receipt.transaction_id}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-gray-500">Remaining Balance</span>
              <span className="font-bold text-gray-800">₹{parseFloat(receipt.new_balance).toFixed(2)}</span>
            </div>
          </div>

          <button
            onClick={() => { setReceipt(null); setSubscriberId(''); }}
            className="w-full py-3 bg-[#1a73e8] hover:bg-[#1557b0] text-white font-bold rounded-2xl transition"
          >
            Recharge Another DTH
          </button>
        </div>
      ) : (
        <div className="bg-white rounded-3xl p-6 sm:p-8 border border-gray-100 shadow-sm space-y-4">
          <form onSubmit={handleRecharge} className="space-y-4">
            <div>
              <label className="block text-xs font-semibold text-gray-600 mb-1">DTH Operator</label>
              <select
                value={selectedOperator}
                onChange={(e) => setSelectedOperator(e.target.value)}
                className="w-full px-3 py-2.5 rounded-2xl border border-gray-200 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100 outline-none text-sm font-medium bg-white"
              >
                {operators.map((op) => (
                  <option key={op.id} value={op.id}>{op.name}</option>
                ))}
              </select>
            </div>

            <div>
              <label className="block text-xs font-semibold text-gray-600 mb-1">Subscriber ID / Smartcard Number</label>
              <input
                type="text"
                required
                value={subscriberId}
                onChange={(e) => setSubscriberId(e.target.value)}
                placeholder="e.g. 1002345678"
                className="w-full px-4 py-2.5 rounded-2xl border border-gray-200 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100 outline-none text-sm font-medium"
              />
            </div>

            <div>
              <div className="flex justify-between items-center mb-1">
                <label className="text-xs font-semibold text-gray-600">Recharge Amount (₹)</label>
                <span className="text-xs text-gray-500">
                  Wallet: <strong className="text-gray-800">{wallet.formatted_balance}</strong>
                </span>
              </div>
              <input
                type="number"
                required
                min="50"
                step="any"
                value={amount}
                onChange={(e) => setAmount(e.target.value)}
                className="w-full px-4 py-2.5 rounded-2xl border border-gray-200 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100 outline-none text-lg font-bold text-gray-900"
              />
              <div className="flex gap-2 mt-2">
                {[200, 350, 500, 1000].map((v) => (
                  <button
                    key={v}
                    type="button"
                    onClick={() => setAmount(v.toString())}
                    className="text-xs font-semibold bg-gray-100 hover:bg-purple-50 hover:text-purple-600 px-3 py-1.5 rounded-lg transition"
                  >
                    ₹{v}
                  </button>
                ))}
              </div>
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full py-3.5 bg-purple-600 hover:bg-purple-700 disabled:bg-purple-300 text-white font-bold rounded-2xl flex items-center justify-center gap-2 transition shadow-md shadow-purple-500/20"
            >
              {loading ? (
                <>
                  <Loader2 className="w-5 h-5 animate-spin" />
                  <span>Processing...</span>
                </>
              ) : (
                <>
                  <span>Pay ₹{parseFloat(amount || 0).toFixed(2)}</span>
                  <ArrowRight className="w-4 h-4" />
                </>
              )}
            </button>
          </form>
        </div>
      )}
    </div>
  );
};
