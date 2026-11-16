import React, { useState } from 'react';
import { useFintech } from '../../context/FintechContext';
import { useToast } from '../../context/ToastContext';
import { soundService } from '../../services/soundService';
import {
  Zap,
  CheckCircle2,
  ShieldCheck,
  Plus,
  ArrowRight,
  Sparkles,
  Smartphone,
  Lock
} from 'lucide-react';

export const UpiLite = () => {
  const { upiLiteEnabled, toggleUpiLite, upiLiteBalance, topUpUpiLite } = useFintech();
  const { addToast } = useToast();
  const [topUpAmount, setTopUpAmount] = useState('500');

  const handleTopUp = (e) => {
    e.preventDefault();
    const ok = topUpUpiLite(topUpAmount);
    if (ok) {
      addToast(`₹${topUpAmount} added to UPI Lite balance!`, 'success');
      setTopUpAmount('');
    } else {
      addToast('Invalid amount', 'error');
    }
  };

  return (
    <div className="max-w-3xl mx-auto space-y-6 pb-12 animate-in fade-in">
      {/* Hero Banner */}
      <div className="bg-gradient-to-r from-[#0047cc] to-[#ff6200] rounded-3xl p-6 sm:p-8 text-white shadow-xl flex items-center justify-between relative overflow-hidden">
        <div className="relative z-10 space-y-2">
          <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md text-xs font-bold text-yellow-300 border border-white/20">
            <Zap className="w-3.5 h-3.5" />
            <span>Zero-PIN Superfast Payments</span>
          </div>
          <h2 className="text-2xl sm:text-3xl font-black tracking-tight">PayBen UPI Lite</h2>
          <p className="text-xs sm:text-sm text-blue-100 max-w-md">
            Pay instantly under ₹500 without entering your UPI PIN. Never fail due to bank server downtime!
          </p>
        </div>
        <div className="hidden sm:block opacity-20">
          <Zap className="w-32 h-32" />
        </div>
      </div>

      {/* Balance & Status Card */}
      <div className="bg-white rounded-3xl border border-gray-200/80 p-6 shadow-xs space-y-6">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-gray-100">
          <div>
            <span className="text-xs font-bold text-gray-500 uppercase tracking-wider block">
              UPI Lite Available Balance
            </span>
            <div className="text-3xl sm:text-4xl font-black text-gray-900 font-mono mt-1">
              ₹{(upiLiteBalance || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 })}
            </div>
            <span className="text-[11px] text-gray-400 font-medium mt-1 block">
              Maximum allowable balance: ₹2,000.00
            </span>
          </div>

          <div className="flex items-center gap-3">
            <span className="text-xs font-bold text-gray-600">
              UPI Lite {upiLiteEnabled ? 'Enabled' : 'Disabled'}
            </span>
            <label className="relative inline-flex items-center cursor-pointer">
              <input
                type="checkbox"
                checked={upiLiteEnabled}
                onChange={toggleUpiLite}
                className="sr-only peer"
              />
              <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#0047cc]"></div>
            </label>
          </div>
        </div>

        {/* Top Up Box */}
        <form onSubmit={handleTopUp} className="space-y-4">
          <label className="block text-xs font-bold text-gray-700 uppercase tracking-wider">
            Top-Up UPI Lite from Primary Bank
          </label>
          <div className="flex flex-col sm:flex-row gap-3">
            <div className="relative flex-1">
              <span className="absolute left-4 top-1/2 -translate-y-1/2 text-lg font-bold text-gray-400">₹</span>
              <input
                type="number"
                min="10"
                max={2000 - upiLiteBalance}
                value={topUpAmount}
                onChange={(e) => setTopUpAmount(e.target.value)}
                placeholder="Enter amount"
                className="w-full pl-9 pr-4 py-3 rounded-2xl border border-gray-200 font-bold text-base outline-none focus:border-[#0047cc]"
              />
            </div>
            <div className="flex gap-2">
              {[200, 500, 1000].map((amt) => (
                <button
                  key={amt}
                  type="button"
                  onClick={() => setTopUpAmount(String(amt))}
                  className="px-3.5 py-2.5 rounded-xl bg-gray-100 hover:bg-gray-200 text-xs font-bold text-gray-700 transition"
                >
                  +₹{amt}
                </button>
              ))}
            </div>
            <button
              type="submit"
              disabled={upiLiteBalance >= 2000}
              className="px-6 py-3 rounded-2xl bg-[#0047cc] hover:bg-[#003db3] text-white font-bold text-xs shadow-md transition disabled:opacity-50"
            >
              Add Money
            </button>
          </div>
        </form>

        {/* Benefits Grid */}
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-4 border-t border-gray-100">
          <div className="p-3.5 rounded-2xl bg-blue-50/50 border border-blue-100 flex items-start gap-2.5">
            <Zap className="w-4 h-4 text-[#0047cc] shrink-0 mt-0.5" />
            <div>
              <h4 className="text-xs font-bold text-gray-900">100% Success Rate</h4>
              <p className="text-[11px] text-gray-500">Processed locally on-device without bank server lag.</p>
            </div>
          </div>

          <div className="p-3.5 rounded-2xl bg-orange-50/50 border border-orange-100 flex items-start gap-2.5">
            <Lock className="w-4 h-4 text-[#ff6200] shrink-0 mt-0.5" />
            <div>
              <h4 className="text-xs font-bold text-gray-900">No PIN Required</h4>
              <p className="text-[11px] text-gray-500">Fast 1-tap checkout for small chai, grocery & auto rides.</p>
            </div>
          </div>

          <div className="p-3.5 rounded-2xl bg-emerald-50/50 border border-emerald-100 flex items-start gap-2.5">
            <ShieldCheck className="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
            <div>
              <h4 className="text-xs font-bold text-gray-900">Clean Bank Passbook</h4>
              <p className="text-[11px] text-gray-500">Small daily expenses don't clutter your bank statement.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
