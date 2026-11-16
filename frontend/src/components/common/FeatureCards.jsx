import React from 'react';
import { ShieldCheck, FileText, Gift, Sparkles, CheckCircle2 } from 'lucide-react';

/**
 * FeatureCards — Neo-Fintech Hybrid Core Value Pillars
 * Highlights Secure Wallet, Transparent Transactions, and Rewards & Cashback
 * using authentic sandbox disclaimers.
 */
export const FeatureCards = () => {
  const features = [
    {
      title: 'Secure Wallet',
      subtitle: 'Atomic Double-Entry Ledger',
      description:
        'Protected by integer paise precision and row-level database locking to guarantee atomic balance integrity with zero double-spending.',
      icon: ShieldCheck,
      badge: '256-Bit SSL',
      theme: {
        iconBg: 'bg-[#101D30]',
        iconColor: 'text-[#35D6B0]',
        badgeBg: 'bg-emerald-50 text-emerald-700 border-emerald-200'
      }
    },
    {
      title: 'Transparent Transactions',
      subtitle: 'Real-Time Passbook & Audit',
      description:
        'Instant digital receipts, unique reference IDs, and verifiable ledger tracking for all mobile top-ups, utilities, and P2P transfers.',
      icon: FileText,
      badge: 'Immutable Ledger',
      theme: {
        iconBg: 'bg-[#101D30]',
        iconColor: 'text-blue-300',
        badgeBg: 'bg-blue-50 text-blue-700 border-blue-200'
      }
    },
    {
      title: 'Rewards & Cashback',
      subtitle: 'Simulated Gamified Perks',
      description:
        'Earn interactive scratch cards with simulated wallet credits between ₹5 and ₹50 upon completing eligible recharge & utility bills.',
      icon: Gift,
      badge: 'Scratch & Win',
      theme: {
        iconBg: 'bg-[#101D30]',
        iconColor: 'text-amber-400',
        badgeBg: 'bg-amber-50 text-amber-700 border-amber-200'
      }
    }
  ];

  return (
    <section className="space-y-3">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-base font-extrabold text-[#101D30]">Built for Modern Payments</h2>
          <p className="text-xs text-gray-500">Fintech sandbox architecture powered by open standards</p>
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        {features.map((f) => {
          const Icon = f.icon;
          return (
            <div
              key={f.title}
              className="p-5 sm:p-6 bg-white rounded-3xl border border-gray-200/80 shadow-xs hover:shadow-md hover:border-[#35D6B0]/50 transition-all flex flex-col justify-between group"
            >
              <div>
                <div className="flex items-center justify-between mb-4">
                  <div
                    className={`w-12 h-12 rounded-2xl ${f.theme.iconBg} ${f.theme.iconColor} flex items-center justify-center shadow-sm group-hover:scale-105 transition-transform`}
                  >
                    <Icon className="w-6 h-6" />
                  </div>
                  <span
                    className={`text-[10px] font-bold px-2.5 py-0.5 rounded-full border ${f.theme.badgeBg}`}
                  >
                    {f.badge}
                  </span>
                </div>

                <h3 className="font-bold text-[#101D30] text-base group-hover:text-[#101D30] transition-colors">
                  {f.title}
                </h3>
                <span className="text-[11px] font-semibold text-[#35D6B0] block mb-2">
                  {f.subtitle}
                </span>
                <p className="text-xs text-gray-500 leading-relaxed">
                  {f.description}
                </p>
              </div>

              <div className="mt-4 pt-3 border-t border-gray-100 flex items-center gap-1.5 text-[11px] text-gray-400">
                <CheckCircle2 className="w-3.5 h-3.5 text-emerald-500" />
                <span>Simulated Sandbox Environment</span>
              </div>
            </div>
          );
        })}
      </div>
    </section>
  );
};

export default FeatureCards;
