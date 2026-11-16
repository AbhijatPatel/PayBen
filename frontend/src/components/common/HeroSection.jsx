import React from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { ArrowRight, Sparkles, ShieldCheck, History, ArrowDown } from 'lucide-react';

/**
 * HeroSection — Neo-Fintech Hybrid Hero Banner
 * Compact height, Midnight Navy (#101D30), Mint (#35D6B0) accents,
 * animated PayBen P SVG monogram illustration, and Sandbox status indicator.
 */
export const HeroSection = ({ isDashboard = false }) => {
  const { user } = useAuth();

  const handleScrollToServices = (e) => {
    e.preventDefault();
    const el = document.getElementById('services');
    if (el) {
      el.scrollIntoView({ behavior: 'smooth' });
    }
  };

  return (
    <section className="relative overflow-hidden rounded-3xl bg-[#101D30] text-white p-6 sm:p-8 lg:p-10 shadow-xl border border-slate-800">
      {/* Ambient Radial Mint Glow in Background */}
      <div
        className="absolute -right-20 -top-20 w-80 h-80 rounded-full bg-[#35D6B0]/10 blur-3xl pointer-events-none"
        aria-hidden="true"
      />
      <div
        className="absolute -left-10 -bottom-10 w-60 h-60 rounded-full bg-blue-500/10 blur-2xl pointer-events-none"
        aria-hidden="true"
      />

      {/* Grid Pattern Mask */}
      <div
        className="absolute inset-0 bg-[linear-gradient(to_right,#ffffff05_1px,transparent_1px),linear-gradient(to_bottom,#ffffff05_1px,transparent_1px)] bg-[size:32px_32px] pointer-events-none"
        aria-hidden="true"
      />

      <div className="relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
        {/* Left Column: Copy & Actions */}
        <div className="lg:col-span-8 space-y-4">
          {/* Top Row: Sandbox Pill + Feature Pill */}
          <div className="flex flex-wrap items-center gap-2">
            {/* Prominent Sandbox Environment Indicator */}
            <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-950/80 text-[#35D6B0] border border-[#35D6B0]/30 text-[11px] font-mono font-bold tracking-wide shadow-xs">
              <span className="w-2 h-2 rounded-full bg-[#35D6B0] animate-pulse" />
              <span>SANDBOX ENVIRONMENT • TEST MODE</span>
            </div>

            <div className="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-white/5 text-slate-300 border border-white/10 text-[11px] font-medium">
              <ShieldCheck className="w-3.5 h-3.5 text-[#35D6B0]" />
              <span>Atomic Double-Entry Ledger</span>
            </div>
          </div>

          {/* Headline */}
          <h1 className="text-2xl sm:text-4xl font-extrabold tracking-tight text-white leading-tight">
            {isDashboard ? (
              <>
                Welcome back,{' '}
                <span className="text-[#35D6B0]">
                  {user?.full_name ? user.full_name.split(' ')[0] : 'Partner'}
                </span>
                .
              </>
            ) : (
              <>
                Smart UPI & <span className="text-[#35D6B0]">Multi-Utility</span> Ecosystem.
              </>
            )}
          </h1>

          {/* Supporting Copy */}
          <p className="text-xs sm:text-sm text-slate-300 leading-relaxed max-w-xl">
            Experience friction-free mobile recharges, utility bill payments, and instant wallet transfers. Built with zero-fee simulated APIs, integer math precision, and real-time passbook statements.
          </p>

          {/* Action CTAs */}
          <div className="flex flex-wrap items-center gap-3 pt-1">
            <button
              onClick={handleScrollToServices}
              className="inline-flex items-center gap-2 bg-[#35D6B0] hover:bg-[#2dc29e] text-[#101D30] font-extrabold text-xs sm:text-sm px-5 py-3 rounded-2xl shadow-md transition active:scale-95"
            >
              <span>Explore Services</span>
              <ArrowDown className="w-4 h-4" />
            </button>

            <Link
              to="/transactions"
              className="inline-flex items-center gap-2 bg-white/10 hover:bg-white/15 text-white font-bold text-xs sm:text-sm px-5 py-3 rounded-2xl border border-white/15 backdrop-blur-md transition active:scale-95"
            >
              <History className="w-4 h-4 text-[#35D6B0]" />
              <span>View Transactions</span>
            </Link>

            {!isDashboard && !user && (
              <Link
                to="/register"
                className="inline-flex items-center gap-2 text-slate-300 hover:text-white font-semibold text-xs sm:text-sm px-3 py-2 transition"
              >
                <span>Create Sandbox Account</span>
                <ArrowRight className="w-4 h-4" />
              </Link>
            )}
          </div>
        </div>

        {/* Right Column: Animated PayBen P Illustration */}
        <div className="lg:col-span-4 flex items-center justify-center">
          <div className="relative w-36 h-36 sm:w-44 sm:h-44 flex items-center justify-center">
            {/* Ambient Animated Rings */}
            <div className="absolute inset-0 rounded-full border border-[#35D6B0]/20 animate-ping opacity-25 pointer-events-none" />
            <div className="absolute -inset-3 rounded-3xl bg-gradient-to-tr from-[#35D6B0]/15 to-transparent blur-xl pointer-events-none" />

            {/* Standalone Geometric P Monogram SVG */}
            <div className="relative z-10 w-32 h-32 sm:w-40 sm:h-40 p-4 rounded-3xl bg-white/5 border border-white/10 backdrop-blur-md shadow-2xl flex items-center justify-center group hover:border-[#35D6B0]/40 transition-colors">
              <svg
                viewBox="0 0 120 120"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
                className="w-full h-full drop-shadow-[0_0_18px_rgba(53,214,176,0.4)]"
              >
                {/* Outer White P Path */}
                <path
                  d="M 36 94 L 36 32 C 36 21 45 16 58 16 L 68 16 C 82 16 92 26 92 42 C 92 57 82 66 66 66 L 36 66"
                  stroke="#FFFFFF"
                  strokeWidth="10"
                  strokeLinecap="round"
                  strokeLinejoin="round"
                />

                {/* Interlocking Mint Flow Ribbon */}
                <path
                  d="M 40 76 C 40 54 52 44 72 44 L 92 44"
                  stroke="#35D6B0"
                  strokeWidth="10"
                  strokeLinecap="round"
                  strokeLinejoin="round"
                />

                {/* Mint Settlement Terminal Node */}
                <circle cx="92" cy="44" r="3.2" fill="#35D6B0" />
                <circle cx="92" cy="44" r="1.6" fill="#101D30" />
              </svg>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
};

export default HeroSection;
