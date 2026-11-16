import React, { useState, useEffect } from 'react';
import { Link, useOutletContext } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { useSecurity } from '../../context/SecurityContext';
import { useLanguage } from '../../context/LanguageContext';
import { useFintech } from '../../context/FintechContext';
import { transactionService } from '../../services/transactionService';
import { soundService } from '../../services/soundService';

import { HeroSection } from '../../components/common/HeroSection';
import { PopularServices } from '../../components/common/PopularServices';
import { ExploreSuperApp } from '../../components/common/ExploreSuperApp';
import { FeatureCards } from '../../components/common/FeatureCards';
import { WalletOverviewDeck } from '../../components/wallet/WalletOverviewDeck';
import { SpendingAnalytics } from '../../components/analytics/SpendingAnalytics';
import { RecentTransactionsList } from '../../components/transactions/RecentTransactionsList';
import { PromotionalBanners } from '../../components/common/PromotionalBanners';

import { SendMoneyModal } from '../../components/modals/SendMoneyModal';
import { ScanPayModal } from '../../components/modals/ScanPayModal';
import { QrModal } from '../../components/modals/QrModal';
import { ScratchCardModal } from '../../components/modals/ScratchCardModal';

import {
  Send,
  QrCode,
  Scan,
  Plus,
  Gift,
  Building2,
  Zap,
  Repeat,
  PiggyBank,
  CreditCard,
  TrendingUp,
  Volume2,
  Shield,
  Sparkles,
  Wifi,
  ChevronRight
} from 'lucide-react';

export const Dashboard = () => {
  const { user, wallet, refreshBalance } = useAuth();
  const { t } = useLanguage();
  const fintech = useFintech();
  const upiLiteBalance = fintech?.upiLiteBalance ?? 1500;
  const pocketMoneyBalance = fintech?.pocketMoneyBalance ?? fintech?.pocketMoney?.balance ?? 1450;
  const cibilScore = fintech?.cibilScore ?? 785;

  const {
    hideBalance,
    toggleHideBalance,
    maskValue,
    soundBoxEnabled,
    toggleSoundBox,
    lockApp,
    lockMethod,
    appLockEnabled
  } = useSecurity();

  const { openAddMoney } = useOutletContext() || {};

  const [summary, setSummary] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  // Modals state
  const [sendOpen, setSendOpen] = useState(false);
  const [sendRecipient, setSendRecipient] = useState('');
  const [scanPayOpen, setScanPayOpen] = useState(false);
  const [qrOpen, setQrOpen] = useState(false);
  const [scratchOpen, setScratchOpen] = useState(false);

  const loadData = async () => {
    try {
      setLoading(true);
      setError(null);
      const res = await transactionService.getDashboard();
      if (res && res.success && res.data) {
        setSummary(res.data);
      }
    } catch (err) {
      setError(err?.message || 'Failed to load dashboard data');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadData();
    if (typeof refreshBalance === 'function') {
      refreshBalance();
    }
  }, []);

  const handleSendToContact = (contact) => {
    soundService.playKeypadClick();
    setSendRecipient(contact.mobile || contact.email);
    setSendOpen(true);
  };

  return (
    <div className="space-y-8 max-w-6xl mx-auto pb-12 animate-in fade-in">
      {/* 1. HERO SECTION */}
      <HeroSection isDashboard={true} />

      {/* 2. WALLET OVERVIEW (Balance, Received, Spent, Add Money) */}
      <WalletOverviewDeck
        wallet={wallet || summary?.wallet}
        stats={summary?.stats}
        user={user || summary?.user}
        onAddMoney={() => {
          soundService.playKeypadClick();
          openAddMoney && openAddMoney();
        }}
      />

      {/* 3. QUICK ACTION COMMAND DECK */}
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <button
          onClick={() => {
            soundService.playKeypadClick();
            setScanPayOpen(true);
          }}
          className="p-4 rounded-2xl bg-[#101D30] text-[#35D6B0] hover:bg-[#1a2f4d] font-bold text-xs flex items-center justify-center gap-2 shadow-sm transition active:scale-95"
        >
          <Scan className="w-4 h-4" />
          <span>Scan Any QR</span>
        </button>

        <button
          onClick={() => {
            soundService.playKeypadClick();
            setQrOpen(true);
          }}
          className="p-4 rounded-2xl bg-white hover:bg-gray-50 text-[#101D30] border border-gray-200/80 font-bold text-xs flex items-center justify-center gap-2 shadow-xs transition active:scale-95"
        >
          <QrCode className="w-4 h-4 text-[#35D6B0]" />
          <span>My PayBen QR</span>
        </button>

        <button
          onClick={() => {
            soundService.playKeypadClick();
            setSendRecipient('');
            setSendOpen(true);
          }}
          className="p-4 rounded-2xl bg-white hover:bg-gray-50 text-[#101D30] border border-gray-200/80 font-bold text-xs flex items-center justify-center gap-2 shadow-xs transition active:scale-95"
        >
          <Send className="w-4 h-4 text-blue-600" />
          <span>Send Money</span>
        </button>

        <button
          onClick={() => {
            soundService.playKeypadClick();
            openAddMoney && openAddMoney();
          }}
          className="p-4 rounded-2xl bg-white hover:bg-gray-50 text-[#101D30] border border-gray-200/80 font-bold text-xs flex items-center justify-center gap-2 shadow-xs transition active:scale-95"
        >
          <Plus className="w-4 h-4 text-emerald-600" />
          <span>Add Cash</span>
        </button>
      </div>

      {/* 4. POPULAR SERVICES GRID */}
      <PopularServices />

      {/* 4.5. EXPLORE EVERYTHING — SUPER APP DIRECTORY */}
      <ExploreSuperApp />

      {/* 5. FINTECH SUPER APP HUB */}
      <section className="bg-white rounded-3xl p-6 border border-gray-200/80 shadow-xs space-y-4">
        <div className="flex items-center justify-between">
          <div>
            <div className="flex items-center gap-2">
              <h2 className="text-base font-extrabold text-[#101D30]">{t('fintechHub')}</h2>
              <span className="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full bg-[#101D30] text-[#35D6B0] tracking-wide">
                Super App
              </span>
            </div>
            <p className="text-xs text-gray-500">Bank accounts, zero-PIN UPI Lite, AutoPay & credit power</p>
          </div>
        </div>

        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
          {/* Bank Accounts */}
          <Link
            to="/banking/accounts"
            className="p-3.5 rounded-2xl border border-gray-100 hover:border-[#35D6B0] hover:bg-[#F5F7FA] transition-all flex flex-col items-center text-center group bg-white"
          >
            <div className="w-11 h-11 rounded-2xl bg-[#F5F7FA] text-[#101D30] flex items-center justify-center mb-2 group-hover:scale-105 group-hover:bg-[#101D30] group-hover:text-[#35D6B0] transition-all shadow-xs">
              <Building2 className="w-5 h-5" />
            </div>
            <span className="text-xs font-bold text-[#101D30]">{t('bankAccounts')}</span>
            <span className="text-[10px] text-gray-400 mt-0.5">Link & Check Bal</span>
          </Link>

          {/* UPI Lite */}
          <Link
            to="/financial/upi-lite"
            className="p-3.5 rounded-2xl border border-gray-100 hover:border-[#35D6B0] hover:bg-[#F5F7FA] transition-all flex flex-col items-center text-center group bg-white relative overflow-hidden"
          >
            <span className="absolute top-1.5 right-1.5 text-[8px] font-black bg-amber-500 text-white px-1.5 py-0.2 rounded-full">
              ₹{Number(upiLiteBalance || 0).toFixed(0)}
            </span>
            <div className="w-11 h-11 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mb-2 group-hover:scale-105 group-hover:bg-[#101D30] group-hover:text-amber-400 transition-all shadow-xs">
              <Zap className="w-5 h-5" />
            </div>
            <span className="text-xs font-bold text-[#101D30]">{t('upiLite')}</span>
            <span className="text-[10px] text-amber-600 font-semibold mt-0.5">Zero-PIN Pay</span>
          </Link>

          {/* AutoPay */}
          <Link
            to="/financial/autopay"
            className="p-3.5 rounded-2xl border border-gray-100 hover:border-[#35D6B0] hover:bg-[#F5F7FA] transition-all flex flex-col items-center text-center group bg-white"
          >
            <div className="w-11 h-11 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mb-2 group-hover:scale-105 group-hover:bg-[#101D30] group-hover:text-indigo-400 transition-all shadow-xs">
              <Repeat className="w-5 h-5" />
            </div>
            <span className="text-xs font-bold text-[#101D30]">{t('autopay')}</span>
            <span className="text-[10px] text-gray-400 mt-0.5">SIP & Mandates</span>
          </Link>

          {/* Pocket Money */}
          <Link
            to="/financial/pocket-money"
            className="p-3.5 rounded-2xl border border-gray-100 hover:border-[#35D6B0] hover:bg-[#F5F7FA] transition-all flex flex-col items-center text-center group bg-white relative overflow-hidden"
          >
            <span className="absolute top-1.5 right-1.5 text-[8px] font-black bg-purple-500 text-white px-1.5 py-0.2 rounded-full">
              ₹{Number(pocketMoneyBalance || 0).toFixed(0)}
            </span>
            <div className="w-11 h-11 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center mb-2 group-hover:scale-105 group-hover:bg-[#101D30] group-hover:text-purple-400 transition-all shadow-xs">
              <PiggyBank className="w-5 h-5" />
            </div>
            <span className="text-xs font-bold text-[#101D30]">{t('pocketMoney')}</span>
            <span className="text-[10px] text-purple-600 font-semibold mt-0.5">Teen Allowance</span>
          </Link>

          {/* Cards Pay */}
          <Link
            to="/financial/cards"
            className="p-3.5 rounded-2xl border border-gray-100 hover:border-[#35D6B0] hover:bg-[#F5F7FA] transition-all flex flex-col items-center text-center group bg-white"
          >
            <div className="w-11 h-11 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center mb-2 group-hover:scale-105 group-hover:bg-[#101D30] group-hover:text-blue-400 transition-all shadow-xs">
              <CreditCard className="w-5 h-5" />
            </div>
            <span className="text-xs font-bold text-[#101D30]">{t('cardsPay')}</span>
            <span className="text-[10px] text-gray-400 mt-0.5">Vault & Pay</span>
          </Link>

          {/* CIBIL Score & Loans */}
          <Link
            to="/financial/cibil-loans"
            className="p-3.5 rounded-2xl border border-gray-100 hover:border-[#35D6B0] hover:bg-[#F5F7FA] transition-all flex flex-col items-center text-center group bg-white relative overflow-hidden"
          >
            <span className="absolute top-1.5 right-1.5 text-[8px] font-black bg-emerald-600 text-white px-1.5 py-0.2 rounded-full">
              {cibilScore}
            </span>
            <div className="w-11 h-11 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mb-2 group-hover:scale-105 group-hover:bg-[#101D30] group-hover:text-rose-400 transition-all shadow-xs">
              <TrendingUp className="w-5 h-5" />
            </div>
            <span className="text-xs font-bold text-[#101D30]">{t('cibilLoan')}</span>
            <span className="text-[10px] text-emerald-600 font-bold mt-0.5">Pre-Approved</span>
          </Link>
        </div>
      </section>

      {/* 6. SPENDING ANALYTICS CHART */}
      <SpendingAnalytics
        monthlyData={summary?.stats?.monthly_spending || []}
        totalSpent={summary?.stats?.total_spent || 0}
      />

      {/* 7. RECENT TRANSACTIONS TABLE */}
      <RecentTransactionsList
        transactions={summary?.recent_transactions || []}
        loading={loading}
        error={error}
      />

      {/* 8. PEOPLE ON PAYBEN (QUICK CONTACT TRANSFERS) */}
      {summary?.contacts?.length > 0 && (
        <section className="bg-white rounded-3xl p-6 border border-gray-200/80 shadow-xs space-y-4">
          <div className="flex items-center justify-between">
            <div>
              <h2 className="text-base font-extrabold text-[#101D30]">Send Money to Contacts</h2>
              <p className="text-xs text-gray-500">1-Tap instant UPI wallet transfer</p>
            </div>
            <button
              onClick={() => {
                soundService.playKeypadClick();
                setSendRecipient('');
                setSendOpen(true);
              }}
              className="text-xs font-bold text-[#101D30] hover:text-[#35D6B0] transition-colors"
            >
              New Transfer
            </button>
          </div>

          <div className="flex items-center gap-4 overflow-x-auto pb-2 scrollbar-none">
            {summary.contacts.map((c) => (
              <div
                key={c.id}
                onClick={() => handleSendToContact(c)}
                className="flex flex-col items-center shrink-0 cursor-pointer group"
              >
                <div className="w-13 h-13 rounded-2xl bg-[#101D30] text-[#35D6B0] border border-[#35D6B0]/30 font-black flex items-center justify-center text-base shadow-xs group-hover:scale-105 group-hover:border-[#35D6B0] transition-all">
                  {c.full_name?.charAt(0).toUpperCase()}
                </div>
                <span className="text-xs font-bold text-gray-800 mt-2 max-w-[70px] truncate text-center">
                  {c.full_name.split(' ')[0]}
                </span>
                <span className="text-[10px] text-gray-400 font-mono">
                  {maskValue(c.mobile)}
                </span>
              </div>
            ))}
          </div>
        </section>
      )}

      {/* 9. REWARDS & CASHBACK SCRATCH CARD BANNER */}
      <section
        onClick={() => {
          soundService.playKeypadClick();
          setScratchOpen(true);
        }}
        className="relative overflow-hidden rounded-3xl bg-[#101D30] text-white p-6 shadow-md cursor-pointer group hover:border-[#35D6B0] border border-slate-800 transition-all"
      >
        <div className="relative z-10 flex items-center justify-between">
          <div className="flex items-center gap-4">
            <div className="w-13 h-13 rounded-2xl bg-white/10 flex items-center justify-center text-[#35D6B0] shrink-0 group-hover:scale-105 transition-transform shadow-inner">
              <Gift className="w-7 h-7" />
            </div>
            <div>
              <div className="inline-flex items-center gap-1 text-[11px] font-bold uppercase tracking-wider text-[#35D6B0]">
                <Sparkles className="w-3.5 h-3.5" />
                <span>Sandbox Rewards Hub</span>
              </div>
              <h3 className="text-base sm:text-lg font-black tracking-tight text-white mt-0.5">
                You have a Golden Scratch Card available!
              </h3>
              <p className="text-xs text-slate-300">
                Scratch with your finger or mouse to unlock simulated cashback between ₹5 and ₹50.
              </p>
            </div>
          </div>
          <button className="hidden sm:inline-flex px-4 py-2 rounded-xl bg-[#35D6B0] text-[#101D30] font-extrabold text-xs shadow-xs group-hover:scale-105 transition-transform">
            Scratch & Win
          </button>
        </div>
      </section>

      {/* 10. PROMOTIONAL AD ROTATOR BANNERS */}
      <PromotionalBanners />

      {/* 11. FEATURE CARDS (Secure Wallet, Transparent Transactions, Rewards & Cashback) */}
      <FeatureCards />

      {/* Connected Modals */}
      <SendMoneyModal
        isOpen={sendOpen}
        onClose={() => setSendOpen(false)}
        initialRecipient={sendRecipient}
      />
      <ScanPayModal
        isOpen={scanPayOpen}
        onClose={() => setScanPayOpen(false)}
      />
      <QrModal
        isOpen={qrOpen}
        onClose={() => setQrOpen(false)}
      />
      <ScratchCardModal
        isOpen={scratchOpen}
        onClose={() => setScratchOpen(false)}
      />
    </div>
  );
};

export default Dashboard;
