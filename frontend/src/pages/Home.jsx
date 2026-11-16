import React from 'react';
import { HeroSection } from '../components/common/HeroSection';
import { PopularServices } from '../components/common/PopularServices';
import { FeatureCards } from '../components/common/FeatureCards';
import { ShieldCheck, Cpu, Database, CheckCircle2 } from 'lucide-react';

export const Home = () => {
  return (
    <div className="space-y-8 pb-12 animate-in fade-in">
      {/* 1. Hero Section with Reduced Height & Animated P SVG */}
      <HeroSection isDashboard={false} />

      {/* 2. Popular Services (Mobile, DTH, Electricity, Water, Gas) */}
      <PopularServices />

      {/* 3. Feature Cards (Secure Wallet, Transparent Transactions, Rewards & Cashback) */}
      <FeatureCards />

      {/* 4. Open-Source Sandbox Architecture Specs */}
      <section className="bg-white rounded-3xl p-6 border border-gray-200/80 shadow-xs space-y-4">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
          <div>
            <h3 className="text-base font-extrabold text-[#101D30]">
              Zero-Paid Sandbox Architecture
            </h3>
            <p className="text-xs text-gray-500">
              Complete local execution environment powered entirely by free and open-source standards
            </p>
          </div>
          <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-mono font-bold">
            <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
            <span>100% Free Tier</span>
          </span>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
          <div className="p-4 rounded-2xl bg-[#F5F7FA] border border-gray-100 flex items-start gap-3">
            <div className="w-10 h-10 rounded-xl bg-[#101D30] text-[#35D6B0] flex items-center justify-center shrink-0">
              <Database className="w-5 h-5" />
            </div>
            <div>
              <h4 className="text-xs font-bold text-[#101D30]">MySQL Double-Entry Ledger</h4>
              <p className="text-[11px] text-gray-500 mt-0.5 leading-relaxed">
                Row-level atomic locks with debit/credit balance pairing for financial consistency.
              </p>
            </div>
          </div>

          <div className="p-4 rounded-2xl bg-[#F5F7FA] border border-gray-100 flex items-start gap-3">
            <div className="w-10 h-10 rounded-xl bg-[#101D30] text-blue-300 flex items-center justify-center shrink-0">
              <Cpu className="w-5 h-5" />
            </div>
            <div>
              <h4 className="text-xs font-bold text-[#101D30]">Local Sandbox Simulator</h4>
              <p className="text-[11px] text-gray-500 mt-0.5 leading-relaxed">
                BBPS mock biller responses, QR camera scanner, and rule-based fraud detection engine.
              </p>
            </div>
          </div>

          <div className="p-4 rounded-2xl bg-[#F5F7FA] border border-gray-100 flex items-start gap-3">
            <div className="w-10 h-10 rounded-xl bg-[#101D30] text-amber-400 flex items-center justify-center shrink-0">
              <ShieldCheck className="w-5 h-5" />
            </div>
            <div>
              <h4 className="text-xs font-bold text-[#101D30]">Role-Based Auth & PIN</h4>
              <p className="text-[11px] text-gray-500 mt-0.5 leading-relaxed">
                Bcrypt password hashing, biometric device locking, and session protection.
              </p>
            </div>
          </div>
        </div>
      </section>
    </div>
  );
};

export default Home;
