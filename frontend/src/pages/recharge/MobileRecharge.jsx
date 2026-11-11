import React, { useState, useEffect } from 'react';
import { rechargeService } from '../../services/rechargeService';
import { useAuth } from '../../context/AuthContext';
import { useToast } from '../../context/ToastContext';
import {
  Smartphone,
  CheckCircle2,
  Loader2,
  ArrowRight,
  ShieldCheck,
  Zap,
  Sparkles
} from 'lucide-react';

export const MobileRecharge = () => {
  const { wallet, refreshBalance } = useAuth();
  const { addToast } = useToast();

  const [operators, setOperators] = useState([]);
  const [selectedOperator, setSelectedOperator] = useState('');
  const [mobile, setMobile] = useState('');
  const [plans, setPlans] = useState([]);
  const [selectedPlan, setSelectedPlan] = useState(null);
  const [amount, setAmount] = useState('');
  const [loading, setLoading] = useState(false);
  const [plansLoading, setPlansLoading] = useState(false);
  const [receipt, setReceipt] = useState(null);

  useEffect(() => {
    rechargeService.getOperators('mobile').then((res) => {
      if (res.success) {
        setOperators(res.data);
        if (res.data.length > 0) setSelectedOperator(res.data[0].id.toString());
      }
    });
  }, []);

  useEffect(() => {
    if (selectedOperator) {
      setPlansLoading(true);
      rechargeService.getPlans(parseInt(selectedOperator))
        .then((res) => {
          if (res.success) setPlans(res.data);
        })
        .finally(() => setPlansLoading(false));
    }
  }, [selectedOperator]);

  const handleSelectPlan = (plan) => {
    setSelectedPlan(plan);
    setAmount(plan.amount.toString());
  };

  const handleRecharge = async (e) => {
    e.preventDefault();
    if (mobile.length !== 10) {
      addToast('Please enter a valid 10-digit Indian mobile number', 'error');
      return;
    }
    const num = parseFloat(amount);
    if (!num || num < 10) {
      addToast('Minimum recharge amount is ₹10', 'error');
      return;
    }
    if (num > wallet.balance) {
      addToast('Insufficient wallet balance. Please add funds first.', 'error');
      return;
    }

    setLoading(true);
    try {
      const res = await rechargeService.mobileRecharge(
        mobile,
        parseInt(selectedOperator),
        num,
        selectedPlan?.id || null
      );
      if (res.success) {
        setReceipt(res.data);
        await refreshBalance();
        addToast('Recharge successful!', 'success');
      }
    } catch (err) {
      addToast(err.message, 'error');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="max-w-4xl mx-auto space-y-6 pb-12">
      <div className="flex items-center gap-3">
        <div className="w-10 h-10 rounded-2xl bg-blue-50 text-[#1a73e8] flex items-center justify-center">
          <Smartphone className="w-5 h-5" />
        </div>
        <div>
          <h1 className="text-xl font-bold text-gray-900">Prepaid Mobile Recharge</h1>
          <p className="text-xs text-gray-500">Fast & zero-fee recharge for all major Indian operators</p>
        </div>
      </div>

      {receipt ? (
        /* Success Receipt */
        <div className="bg-white rounded-3xl p-8 border border-gray-100 shadow-xl max-w-md mx-auto text-center space-y-4">
          <div className="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto">
            <CheckCircle2 className="w-10 h-10" />
          </div>
          <div>
            <span className="text-xs uppercase tracking-wider text-emerald-700 font-bold bg-emerald-50 px-3 py-1 rounded-full">
              Recharge Successful
            </span>
            <div className="text-3xl font-black text-gray-900 mt-3">
              ₹{parseFloat(receipt.amount).toFixed(2)}
            </div>
            <p className="text-sm text-gray-500 mt-1">
              Recharged for <span className="font-bold text-gray-800">{receipt.target_number}</span> ({receipt.operator})
            </p>
          </div>

          <div className="bg-gray-50 rounded-2xl p-4 text-xs space-y-2 text-left">
            <div className="flex justify-between">
              <span className="text-gray-500">Transaction ID</span>
              <span className="font-mono font-bold text-gray-800">{receipt.transaction_id}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-gray-500">Wallet Balance</span>
              <span className="font-bold text-gray-800">₹{parseFloat(receipt.new_balance).toFixed(2)}</span>
            </div>
          </div>

          <button
            onClick={() => { setReceipt(null); setMobile(''); setAmount(''); setSelectedPlan(null); }}
            className="w-full py-3 bg-[#1a73e8] hover:bg-[#1557b0] text-white font-bold rounded-2xl transition"
          >
            Recharge Another Number
          </button>
        </div>
      ) : (
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
          {/* Left Form */}
          <div className="lg:col-span-5 bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-4">
            <form onSubmit={handleRecharge} className="space-y-4">
              <div>
                <label className="block text-xs font-semibold text-gray-600 mb-1">Mobile Number</label>
                <div className="relative">
                  <span className="absolute left-3.5 top-1/2 -translate-y-1/2 text-xs font-bold text-gray-400">
                    +91
                  </span>
                  <input
                    type="tel"
                    required
                    maxLength={10}
                    value={mobile}
                    onChange={(e) => setMobile(e.target.value.replace(/\D/g, ''))}
                    placeholder="9876543210"
                    className="w-full pl-12 pr-4 py-2.5 rounded-2xl border border-gray-200 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100 outline-none text-sm font-medium"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-semibold text-gray-600 mb-1">Operator</label>
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
                <div className="flex justify-between items-center mb-1">
                  <label className="text-xs font-semibold text-gray-600">Recharge Amount (₹)</label>
                  <span className="text-xs text-gray-500">
                    Bal: <strong className="text-gray-800">{wallet.formatted_balance}</strong>
                  </span>
                </div>
                <div className="relative">
                  <span className="absolute left-4 top-1/2 -translate-y-1/2 text-lg font-bold text-gray-500">
                    ₹
                  </span>
                  <input
                    type="number"
                    required
                    min="10"
                    step="any"
                    value={amount}
                    onChange={(e) => setAmount(e.target.value)}
                    placeholder="Select or enter amount"
                    className="w-full pl-10 pr-4 py-2.5 rounded-2xl border border-gray-200 focus:border-[#1a73e8] focus:ring-2 focus:ring-blue-100 outline-none text-lg font-bold text-gray-900"
                  />
                </div>
              </div>

              {selectedPlan && (
                <div className="p-3 bg-blue-50 border border-blue-100 rounded-xl text-xs space-y-1">
                  <div className="font-bold text-[#1a73e8]">{selectedPlan.plan_name}</div>
                  <div className="text-gray-600">{selectedPlan.description}</div>
                  <div className="text-gray-400">Validity: {selectedPlan.validity} • Data: {selectedPlan.data_limit}</div>
                </div>
              )}

              <button
                type="submit"
                disabled={loading}
                className="w-full py-3.5 bg-[#1a73e8] hover:bg-[#1557b0] disabled:bg-blue-300 text-white font-bold rounded-2xl flex items-center justify-center gap-2 transition shadow-md shadow-blue-500/20"
              >
                {loading ? (
                  <>
                    <Loader2 className="w-5 h-5 animate-spin" />
                    <span>Processing Recharge...</span>
                  </>
                ) : (
                  <>
                    <span>Proceed to Recharge</span>
                    <ArrowRight className="w-4 h-4" />
                  </>
                )}
              </button>
            </form>
          </div>

          {/* Right Browse Plans */}
          <div className="lg:col-span-7 bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-4">
            <div className="flex items-center justify-between">
              <h2 className="text-sm font-bold text-gray-900">Browse Available Plans</h2>
              <span className="text-xs text-gray-400">{plans.length} plans</span>
            </div>

            {plansLoading ? (
              <div className="py-12 flex justify-center">
                <Loader2 className="w-6 h-6 animate-spin text-[#1a73e8]" />
              </div>
            ) : plans.length > 0 ? (
              <div className="space-y-3 max-h-[460px] overflow-y-auto pr-1">
                {plans.map((p) => (
                  <div
                    key={p.id}
                    onClick={() => handleSelectPlan(p)}
                    className={`p-4 rounded-2xl border cursor-pointer transition-all flex items-center justify-between gap-4 ${
                      selectedPlan?.id === p.id
                        ? 'border-[#1a73e8] bg-blue-50/40 ring-2 ring-blue-100'
                        : 'border-gray-100 hover:border-blue-200 hover:bg-gray-50/50'
                    }`}
                  >
                    <div>
                      <div className="flex items-center gap-2">
                        <span className="text-lg font-black text-gray-900">₹{parseFloat(p.amount).toFixed(0)}</span>
                        <span className="text-xs font-bold text-blue-700 bg-blue-100/70 px-2 py-0.5 rounded-full">
                          {p.validity}
                        </span>
                        <span className="text-xs font-semibold text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full">
                          {p.data_limit}
                        </span>
                      </div>
                      <p className="text-xs text-gray-600 mt-1">{p.description}</p>
                    </div>

                    <button
                      type="button"
                      className="px-3 py-1.5 rounded-xl text-xs font-bold bg-white border border-gray-200 text-[#1a73e8] hover:bg-[#1a73e8] hover:text-white transition"
                    >
                      Select
                    </button>
                  </div>
                ))}
              </div>
            ) : (
              <div className="text-center py-12 text-gray-400 text-xs">
                Select an operator to see tariff plans.
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  );
};
