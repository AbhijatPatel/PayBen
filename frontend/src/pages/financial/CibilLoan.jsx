import React, { useState } from 'react';
import { useFintech } from '../../context/FintechContext';
import { useToast } from '../../context/ToastContext';
import { soundService } from '../../services/soundService';
import confetti from 'canvas-confetti';
import {
  TrendingUp,
  Award,
  ShieldCheck,
  CheckCircle2,
  AlertCircle,
  HelpCircle,
  Coins,
  ArrowRight,
  Clock,
  Briefcase,
  Zap
} from 'lucide-react';

export const CibilLoan = () => {
  const { cibilScore, loanOffers } = useFintech();
  const { addToast } = useToast();

  const [loanAmount, setLoanAmount] = useState(200000);
  const [tenureMonths, setTenureMonths] = useState(12);
  const [applyLoading, setApplyLoading] = useState(false);
  const [loanApproved, setLoanApproved] = useState(false);

  // EMI Calculation: P * r * (1+r)^n / ((1+r)^n - 1)
  const annualRate = 0.105; // 10.5%
  const monthlyRate = annualRate / 12;
  const emi = Math.round(
    (loanAmount * monthlyRate * Math.pow(1 + monthlyRate, tenureMonths)) /
    (Math.pow(1 + monthlyRate, tenureMonths) - 1)
  );

  const totalPayment = emi * tenureMonths;
  const totalInterest = totalPayment - loanAmount;

  const handleApplyLoan = () => {
    setApplyLoading(true);
    soundService.playKeypadClick();

    setTimeout(() => {
      setApplyLoading(false);
      setLoanApproved(true);
      soundService.announcePayment(loanAmount, 'Approved Instant Loan');
      confetti({ particleCount: 120, spread: 80, origin: { y: 0.6 } });
      addToast(`🎉 Pre-approved loan of ₹${loanAmount.toLocaleString('en-IN')} approved!`, 'success');
    }, 1500);
  };

  return (
    <div className="max-w-4xl mx-auto space-y-6 pb-12 animate-in fade-in">
      {/* Banner */}
      <div className="bg-gradient-to-r from-[#0047cc] to-[#ff6200] rounded-3xl p-6 sm:p-8 text-white shadow-xl flex items-center justify-between relative overflow-hidden">
        <div className="relative z-10 space-y-2">
          <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md text-xs font-bold text-yellow-300 border border-white/20">
            <Award className="w-3.5 h-3.5" />
            <span>Official Credit Health & Lending Hub</span>
          </div>
          <h2 className="text-2xl sm:text-3xl font-black tracking-tight">CIBIL Score & Instant Loans</h2>
          <p className="text-xs sm:text-sm text-blue-100 max-w-md">
            Check your verified credit report for free and unlock instant collateral-free pre-approved loans up to ₹5,00,000.
          </p>
        </div>
        <div className="hidden sm:block opacity-20">
          <TrendingUp className="w-32 h-32" />
        </div>
      </div>

      {/* ---------------- 1. 3D CIBIL SCORE GAUGE ---------------- */}
      <div className="bg-white rounded-3xl border border-gray-200/80 p-6 sm:p-8 shadow-xs">
        <div className="flex flex-col md:flex-row items-center justify-between gap-8">
          {/* Radial Meter Visual */}
          <div className="relative flex flex-col items-center justify-center">
            <div className="relative w-56 h-36 overflow-hidden flex items-end justify-center">
              {/* Semi-circle Gauge SVG */}
              <svg viewBox="0 0 200 110" className="w-56 h-32">
                <path
                  d="M 20 100 A 80 80 0 0 1 180 100"
                  fill="none"
                  stroke="#e2e8f0"
                  strokeWidth="18"
                  strokeLinecap="round"
                />
                <path
                  d="M 20 100 A 80 80 0 0 1 180 100"
                  fill="none"
                  stroke="url(#scoreGradient)"
                  strokeWidth="18"
                  strokeLinecap="round"
                  strokeDasharray="251.2"
                  strokeDashoffset="50" // ~785 score
                />
                <defs>
                  <linearGradient id="scoreGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                    <stop offset="0%" stopColor="#ef4444" />
                    <stop offset="30%" stopColor="#f59e0b" />
                    <stop offset="70%" stopColor="#10b981" />
                    <stop offset="100%" stopColor="#059669" />
                  </linearGradient>
                </defs>
              </svg>

              <div className="absolute bottom-0 inset-x-0 text-center">
                <div className="text-4xl font-black text-gray-900 font-mono tracking-tight">
                  {cibilScore}
                </div>
                <span className="text-xs font-black uppercase tracking-wider text-emerald-600 bg-emerald-50 px-3 py-0.5 rounded-full border border-emerald-200">
                  EXCELLENT
                </span>
              </div>
            </div>

            <p className="text-[11px] text-gray-400 font-medium mt-2">
              Score range: 300 to 900 • Powered by TransUnion CIBIL
            </p>
          </div>

          {/* Credit Factors */}
          <div className="flex-1 w-full space-y-3">
            <h3 className="text-sm font-bold text-gray-800 uppercase tracking-wider">
              Credit Factor Insights
            </h3>

            <div className="grid grid-cols-2 gap-3 text-xs">
              <div className="p-3 rounded-2xl bg-gray-50 border border-gray-100">
                <span className="text-gray-500 block mb-0.5">On-Time Payments</span>
                <span className="font-black text-emerald-600 text-sm">100% (High Impact)</span>
              </div>

              <div className="p-3 rounded-2xl bg-gray-50 border border-gray-100">
                <span className="text-gray-500 block mb-0.5">Credit Utilization</span>
                <span className="font-black text-emerald-600 text-sm">14% (Ideal &lt; 30%)</span>
              </div>

              <div className="p-3 rounded-2xl bg-gray-50 border border-gray-100">
                <span className="text-gray-500 block mb-0.5">Credit Age</span>
                <span className="font-black text-blue-600 text-sm">4.2 Years (Good)</span>
              </div>

              <div className="p-3 rounded-2xl bg-gray-50 border border-gray-100">
                <span className="text-gray-500 block mb-0.5">Total Active Accounts</span>
                <span className="font-black text-gray-900 text-sm">5 Accounts</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* ---------------- 2. INSTANT PRE-APPROVED LOAN CALCULATOR ---------------- */}
      <div className="bg-white rounded-3xl border border-gray-200/80 p-6 sm:p-8 shadow-xs space-y-6">
        <div>
          <span className="text-xs font-bold text-[#ff6200] uppercase tracking-wider flex items-center gap-1">
            <Zap className="w-3.5 h-3.5" />
            <span>Pre-Approved for You</span>
          </span>
          <h3 className="text-xl font-black text-gray-900 mt-1">
            Instant Personal Loan Calculator
          </h3>
          <p className="text-xs text-gray-500">
            Funds credited directly to your primary linked bank account in 2 minutes.
          </p>
        </div>

        {/* Loan Amount Slider */}
        <div className="space-y-2">
          <div className="flex justify-between items-center text-xs font-bold text-gray-700">
            <span>Loan Amount Required</span>
            <span className="text-xl font-black text-[#0047cc] font-mono">
              ₹{loanAmount.toLocaleString('en-IN')}
            </span>
          </div>
          <input
            type="range"
            min="25000"
            max="500000"
            step="5000"
            value={loanAmount}
            onChange={(e) => setLoanAmount(Number(e.target.value))}
            className="w-full h-2.5 bg-blue-100 rounded-lg appearance-none cursor-pointer accent-[#0047cc]"
          />
          <div className="flex justify-between text-[11px] text-gray-400 font-mono">
            <span>₹25,000</span>
            <span>₹5,00,000 Max</span>
          </div>
        </div>

        {/* Tenure Selection */}
        <div className="space-y-2">
          <label className="block text-xs font-bold text-gray-700">
            Select Repayment Tenure
          </label>
          <div className="grid grid-cols-4 gap-2">
            {[6, 12, 24, 36].map((m) => (
              <button
                key={m}
                onClick={() => setTenureMonths(m)}
                className={`py-2 rounded-xl text-xs font-bold transition border ${
                  tenureMonths === m
                    ? 'bg-[#0047cc] text-white border-[#0047cc] shadow-sm'
                    : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100'
                }`}
              >
                {m} Months
              </button>
            ))}
          </div>
        </div>

        {/* Calculated Breakdown Card */}
        <div className="p-4 rounded-2xl bg-gradient-to-r from-blue-50 to-orange-50/50 border border-blue-100 flex flex-col sm:flex-row items-center justify-between gap-4">
          <div>
            <span className="text-[11px] font-bold text-gray-500 uppercase tracking-wider block">
              Estimated Monthly EMI
            </span>
            <div className="text-3xl font-black text-gray-900 font-mono">
              ₹{emi.toLocaleString('en-IN')} <span className="text-xs font-normal text-gray-500">/month</span>
            </div>
            <p className="text-[11px] text-gray-500 mt-0.5">
              Interest Rate: 10.5% p.a. • Total Interest: ₹{totalInterest.toLocaleString('en-IN')}
            </p>
          </div>

          <button
            onClick={handleApplyLoan}
            disabled={applyLoading || loanApproved}
            className="w-full sm:w-auto px-6 py-3.5 bg-gradient-to-r from-[#0047cc] to-[#ff6200] hover:brightness-110 text-white font-black text-xs rounded-2xl shadow-md transition disabled:opacity-50"
          >
            {applyLoading ? 'Processing Approval...' : loanApproved ? 'Disbursal Complete ✓' : 'Instant Disbursal Now'}
          </button>
        </div>
      </div>
    </div>
  );
};
