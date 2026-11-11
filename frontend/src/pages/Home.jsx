import React from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import {
  Zap,
  Smartphone,
  Tv,
  Droplet,
  Flame,
  ShieldCheck,
  ArrowRight,
  Sparkles,
  CreditCard,
  Lock,
  RefreshCw
} from 'lucide-react';

export const Home = () => {
  const { user } = useAuth();

  return (
    <div className="space-y-12 pb-12">
      {/* Hero Section */}
      <section className="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#0f172a] via-[#1e3a5f] to-[#1a73e8] text-white p-8 sm:p-12 shadow-xl">
        <div className="relative z-10 max-w-2xl space-y-6">
          <div className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 backdrop-blur-md text-xs font-semibold text-blue-200 border border-white/15">
            <Sparkles className="w-4 h-4 text-amber-400" />
            <span>Next-Gen Smart UPI & Utility Payments</span>
          </div>

          <h1 className="text-3xl sm:text-5xl font-black tracking-tight leading-tight">
            Seamless payments for all your recharges and bills.
          </h1>

          <p className="text-sm sm:text-base text-blue-100/90 leading-relaxed max-w-xl">
            Instant mobile top-ups, DTH packages, and electricity, water, and gas bill payments with a zero-fee digital wallet and cashbacks.
          </p>

          <div className="flex flex-wrap items-center gap-3 pt-2">
            {user ? (
              <Link
                to="/dashboard"
                className="inline-flex items-center gap-2 bg-white text-gray-900 hover:bg-gray-100 font-bold px-6 py-3.5 rounded-2xl shadow-lg transition"
              >
                <span>Go to Dashboard</span>
                <ArrowRight className="w-4 h-4" />
              </Link>
            ) : (
              <>
                <Link
                  to="/register"
                  className="inline-flex items-center gap-2 bg-[#34a853] hover:bg-emerald-600 text-white font-bold px-6 py-3.5 rounded-2xl shadow-lg shadow-emerald-500/20 transition"
                >
                  <span>Create Free Account</span>
                  <ArrowRight className="w-4 h-4" />
                </Link>
                <Link
                  to="/login"
                  className="inline-flex items-center gap-2 bg-white/10 hover:bg-white/20 text-white font-semibold px-6 py-3.5 rounded-2xl backdrop-blur-md border border-white/20 transition"
                >
                  <span>Sign In</span>
                </Link>
              </>
            )}
          </div>
        </div>
      </section>

      {/* Services Grid */}
      <section className="space-y-4">
        <div className="flex items-center justify-between">
          <div>
            <h2 className="text-xl font-bold text-gray-900">Popular Services</h2>
            <p className="text-xs text-gray-500">Pick any service to get started</p>
          </div>
        </div>

        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
          {[
            { name: 'Mobile Recharge', path: '/recharge/mobile', icon: Smartphone, color: 'text-blue-600', bg: 'bg-blue-50' },
            { name: 'DTH Recharge', path: '/recharge/dth', icon: Tv, color: 'text-purple-600', bg: 'bg-purple-50' },
            { name: 'Electricity Bill', path: '/bills/electricity', icon: Zap, color: 'text-amber-500', bg: 'bg-amber-50' },
            { name: 'Water Bill', path: '/bills/water', icon: Droplet, color: 'text-cyan-600', bg: 'bg-cyan-50' },
            { name: 'Gas Cylinder/Piped', path: '/bills/gas', icon: Flame, color: 'text-red-500', bg: 'bg-red-50' },
          ].map((s) => {
            const Icon = s.icon;
            return (
              <Link
                key={s.name}
                to={s.path}
                className="flex flex-col items-center justify-center p-5 bg-white rounded-2xl border border-gray-200/80 shadow-xs hover:shadow-md hover:border-blue-300 transition-all group text-center"
              >
                <div className={`w-14 h-14 rounded-2xl ${s.bg} ${s.color} flex items-center justify-center mb-3 group-hover:scale-110 transition-transform`}>
                  <Icon className="w-7 h-7" />
                </div>
                <span className="text-sm font-bold text-gray-800">{s.name}</span>
                <span className="text-[11px] text-gray-400 mt-1">Instant confirmation</span>
              </Link>
            );
          })}
        </div>
      </section>

      {/* Trust & Safety Features */}
      <section className="grid grid-cols-1 md:grid-cols-3 gap-6 pt-4">
        <div className="p-6 bg-white rounded-3xl border border-gray-100 shadow-sm flex items-start gap-4">
          <div className="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
            <ShieldCheck className="w-6 h-6" />
          </div>
          <div>
            <h3 className="font-bold text-gray-900 text-base">Bank-Grade Security</h3>
            <p className="text-xs text-gray-500 mt-1 leading-relaxed">
              256-bit encryption with atomic double-entry balance locking ensures zero double-spending.
            </p>
          </div>
        </div>

        <div className="p-6 bg-white rounded-3xl border border-gray-100 shadow-sm flex items-start gap-4">
          <div className="w-12 h-12 rounded-2xl bg-blue-50 text-[#1a73e8] flex items-center justify-center shrink-0">
            <RefreshCw className="w-6 h-6" />
          </div>
          <div>
            <h3 className="font-bold text-gray-900 text-base">Instant Settlement</h3>
            <p className="text-xs text-gray-500 mt-1 leading-relaxed">
              Transactions, recharges, and bill settlements reflect in real-time on your dashboard.
            </p>
          </div>
        </div>

        <div className="p-6 bg-white rounded-3xl border border-gray-100 shadow-sm flex items-start gap-4">
          <div className="w-12 h-12 rounded-2xl bg-amber-50 text-amber-500 flex items-center justify-center shrink-0">
            <Sparkles className="w-6 h-6" />
          </div>
          <div>
            <h3 className="font-bold text-gray-900 text-base">Exciting Cashbacks</h3>
            <p className="text-xs text-gray-500 mt-1 leading-relaxed">
              Earn scratch cards and win random cashback rewards between ₹5 and ₹50 with every payment.
            </p>
          </div>
        </div>
      </section>
    </div>
  );
};
