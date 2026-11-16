import React, { useState } from 'react';
import { paymentService } from '../../services/paymentService';
import { useAuth } from '../../context/AuthContext';
import { useToast } from '../../context/ToastContext';
import { soundService } from '../../services/soundService';
import {
  X,
  Plus,
  CreditCard,
  CheckCircle2,
  XCircle,
  Loader2,
  Sparkles,
  ShieldCheck,
  Zap,
  ArrowRight
} from 'lucide-react';

export const AddMoneyModal = ({ isOpen, onClose }) => {
  const { refreshBalance } = useAuth();
  const { addToast } = useToast();

  const [step, setStep] = useState('input'); // 'input' | 'checkout' | 'result'
  const [amount, setAmount] = useState('500');
  const [method, setMethod] = useState('UPI FastPay');
  const [loading, setLoading] = useState(false);
  const [activeOrder, setActiveOrder] = useState(null);
  const [resultData, setResultData] = useState(null);

  if (!isOpen) return null;

  const handleStartCheckout = async (e) => {
    e.preventDefault();
    const num = parseFloat(amount);
    if (!num || num < 1) {
      addToast('Minimum top-up amount is ₹1.00', 'error');
      return;
    }

    setLoading(true);
    try {
      soundService.playKeypadClick();
      const res = await paymentService.createOrder(num, `Wallet top-up via ${method}`);
      if (res.success && res.data) {
        setActiveOrder(res.data);
        setStep('checkout');
      }
    } catch (err) {
      addToast(err.message || 'Failed to initialize sandbox checkout', 'error');
    } finally {
      setLoading(false);
    }
  };

  const handleSimulatePayment = async (action) => {
    if (!activeOrder) return;
    setLoading(true);
    soundService.playKeypadClick();

    try {
      const res = await paymentService.verifyPayment(activeOrder.order_id, action);
      if (action === 'SUCCESS') {
        soundService.playBiometricSuccess();
        await refreshBalance();
        setResultData({
          success: true,
          title: 'Payment Successful',
          amount: activeOrder.amount,
          new_balance: res.formatted_balance || `₹${res.new_balance}`,
          transaction_id: res.payment_id || activeOrder.order_id,
          message: 'Funds added to your PayBen sandbox wallet atomically.'
        });
        addToast(`₹${activeOrder.amount} added to wallet!`, 'success');
      } else {
        soundService.playKeypadClick();
        setResultData({
          success: false,
          title: action === 'CANCELLED' ? 'Payment Cancelled' : 'Payment Failed',
          amount: activeOrder.amount,
          transaction_id: activeOrder.order_id,
          message: action === 'CANCELLED'
            ? 'The checkout session was cancelled by user.'
            : 'Simulated bank card decline (Sandbox test mode).'
        });
      }
      setStep('result');
    } catch (err) {
      addToast(err.message || 'Checkout verification error', 'error');
    } finally {
      setLoading(false);
    }
  };

  const handleClose = () => {
    setStep('input');
    setActiveOrder(null);
    setResultData(null);
    onClose();
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-in fade-in">
      <div className="bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden border border-gray-100">
        {/* Header */}
        <div className="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50/50">
          <div className="flex items-center gap-2">
            <div className="w-8 h-8 rounded-full bg-blue-50 text-[#0047cc] flex items-center justify-center">
              <Plus className="w-4 h-4" />
            </div>
            <div>
              <h3 className="font-bold text-gray-900 text-sm">
                {step === 'checkout' ? 'PayBen Sandbox Gateway' : 'Add Cash to Wallet'}
              </h3>
              <span className="text-[10px] text-gray-400 font-medium">Free Sandbox Simulator</span>
            </div>
          </div>
          <button
            onClick={handleClose}
            className="p-1.5 rounded-full text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition"
          >
            <X className="w-4 h-4" />
          </button>
        </div>

        {/* STEP 1: Enter Amount */}
        {step === 'input' && (
          <form onSubmit={handleStartCheckout} className="p-6 space-y-4">
            <div>
              <label className="block text-xs font-semibold text-gray-600 mb-1">Enter Top-Up Amount</label>
              <div className="relative">
                <span className="absolute left-4 top-1/2 -translate-y-1/2 text-2xl font-black text-gray-400">
                  ₹
                </span>
                <input
                  type="number"
                  required
                  min="1"
                  step="any"
                  value={amount}
                  onChange={(e) => setAmount(e.target.value)}
                  className="w-full pl-10 pr-4 py-3 rounded-2xl border border-gray-200 focus:border-[#0047cc] focus:ring-2 focus:ring-blue-100 outline-none text-2xl font-black text-gray-900 font-mono"
                />
              </div>

              {/* Quick Amount Chips */}
              <div className="grid grid-cols-4 gap-2 mt-3">
                {[100, 500, 1000, 2000].map((val) => (
                  <button
                    key={val}
                    type="button"
                    onClick={() => { soundService.playKeypadClick(); setAmount(val.toString()); }}
                    className={`py-2 text-xs font-bold rounded-xl border transition ${
                      amount === val.toString()
                        ? 'bg-blue-50 border-[#0047cc] text-[#0047cc]'
                        : 'bg-gray-50 border-gray-200 text-gray-700 hover:bg-gray-100'
                    }`}
                  >
                    +₹{val}
                  </button>
                ))}
              </div>
            </div>

            <div>
              <label className="block text-xs font-semibold text-gray-600 mb-1.5">Sandbox Payment Instrument</label>
              <div className="grid grid-cols-3 gap-2">
                {['UPI FastPay', 'Net Banking', 'Debit Card'].map((m) => (
                  <button
                    key={m}
                    type="button"
                    onClick={() => { soundService.playKeypadClick(); setMethod(m); }}
                    className={`p-2.5 rounded-xl border text-xs font-bold text-center transition ${
                      method === m
                        ? 'bg-blue-50 border-[#0047cc] text-[#0047cc]'
                        : 'border-gray-200 text-gray-600 hover:bg-gray-50'
                    }`}
                  >
                    {m}
                  </button>
                ))}
              </div>
            </div>

            <div className="p-3 bg-blue-50/60 border border-blue-100 rounded-2xl flex items-center gap-2.5 text-xs text-blue-900">
              <ShieldCheck className="w-5 h-5 text-[#0047cc] shrink-0" />
              <div>
                <span className="font-bold block">100% Free Sandbox Gateway</span>
                <span className="text-[11px] text-blue-700">Atomic ledger update • No real card details charged</span>
              </div>
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full py-3.5 bg-gradient-to-r from-[#0047cc] to-[#ff6200] hover:brightness-110 disabled:opacity-50 text-white font-bold rounded-2xl flex items-center justify-center gap-2 transition shadow-md shadow-blue-500/20 active:scale-95 text-xs"
            >
              {loading ? (
                <>
                  <Loader2 className="w-4 h-4 animate-spin" />
                  <span>Preparing Gateway...</span>
                </>
              ) : (
                <>
                  <span>Proceed to Sandbox Checkout</span>
                  <ArrowRight className="w-4 h-4" />
                </>
              )}
            </button>
          </form>
        )}

        {/* STEP 2: Simulated Checkout Screen */}
        {step === 'checkout' && activeOrder && (
          <div className="p-6 space-y-5">
            <div className="text-center py-2">
              <span className="text-[11px] font-mono font-bold uppercase tracking-wider text-gray-400 block">
                Order ID: {activeOrder.order_id}
              </span>
              <div className="text-3xl font-black text-gray-900 font-mono mt-1">
                ₹{parseFloat(activeOrder.amount).toFixed(2)}
              </div>
              <p className="text-xs text-gray-500 mt-1">
                Instrument: <strong>{method}</strong> • Zero-cost Sandbox
              </p>
            </div>

            <div className="p-3.5 bg-amber-50 rounded-2xl border border-amber-200 text-xs text-amber-900 space-y-1">
              <p className="font-bold flex items-center gap-1.5">
                <Sparkles className="w-4 h-4 text-amber-600 shrink-0" />
                PayBen Sandbox Checkout Simulator
              </p>
              <p className="text-[11px] text-amber-800">
                Choose an action below to test atomic ledger updates, webhooks, or error handling.
              </p>
            </div>

            <div className="space-y-2.5">
              <button
                onClick={() => handleSimulatePayment('SUCCESS')}
                disabled={loading}
                className="w-full py-3 px-4 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center justify-center gap-2 shadow-sm transition active:scale-95 disabled:opacity-50"
              >
                <CheckCircle2 className="w-4 h-4" />
                <span>Simulate Successful Payment</span>
              </button>

              <button
                onClick={() => handleSimulatePayment('FAILED')}
                disabled={loading}
                className="w-full py-3 px-4 rounded-2xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold text-xs flex items-center justify-center gap-2 transition active:scale-95 disabled:opacity-50"
              >
                <XCircle className="w-4 h-4" />
                <span>Simulate Failed Payment</span>
              </button>

              <button
                onClick={() => handleSimulatePayment('CANCELLED')}
                disabled={loading}
                className="w-full py-2.5 px-4 rounded-2xl text-gray-500 hover:bg-gray-100 font-semibold text-xs transition"
              >
                Cancel Checkout
              </button>
            </div>
          </div>
        )}

        {/* STEP 3: Result Summary Screen */}
        {step === 'result' && resultData && (
          <div className="p-6 text-center space-y-4">
            <div className={`w-16 h-16 rounded-full flex items-center justify-center mx-auto ${
              resultData.success ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600'
            }`}>
              {resultData.success ? <CheckCircle2 className="w-10 h-10" /> : <XCircle className="w-10 h-10" />}
            </div>

            <div>
              <span className={`text-[11px] uppercase tracking-wider font-extrabold px-3 py-1 rounded-full ${
                resultData.success ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'
              }`}>
                {resultData.title}
              </span>
              <div className="text-3xl font-black text-gray-900 font-mono mt-3">
                ₹{parseFloat(resultData.amount).toFixed(2)}
              </div>
              <p className="text-xs text-gray-500 mt-1 max-w-xs mx-auto">
                {resultData.message}
              </p>
            </div>

            {resultData.new_balance && (
              <div className="p-3 bg-gray-50 rounded-2xl border border-gray-100 text-xs">
                <span className="text-gray-500">Updated Wallet Balance:</span>{' '}
                <strong className="text-gray-900 font-mono">{resultData.new_balance}</strong>
              </div>
            )}

            <button
              onClick={handleClose}
              className="w-full py-3 bg-[#0047cc] hover:bg-[#003db3] text-white font-bold text-xs rounded-2xl transition shadow-md shadow-blue-500/20"
            >
              Back to Dashboard
            </button>
          </div>
        )}
      </div>
    </div>
  );
};
