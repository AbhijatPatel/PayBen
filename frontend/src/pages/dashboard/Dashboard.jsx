import React, { useState, useEffect } from 'react';
import { Link, useOutletContext } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { useSecurity } from '../../context/SecurityContext';
import { useLanguage } from '../../context/LanguageContext';
import { useFintech } from '../../context/FintechContext';
import { transactionService } from '../../services/transactionService';
import { soundService } from '../../services/soundService';
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
  Smartphone,
  Tv,
  Zap,
  Droplet,
  Flame,
  Gift,
  ArrowUpRight,
  ArrowDownLeft,
  ChevronRight,
  CheckCircle2,
  Clock,
  Sparkles,
  RefreshCw,
  Eye,
  EyeOff,
  Shield,
  Volume2,
  Lock,
  Wallet as WalletIcon,
  Wifi,
  CreditCard,
  Building2,
  PiggyBank,
  TrendingUp,
  Percent
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

  // Modals state
  const [sendOpen, setSendOpen] = useState(false);
  const [sendRecipient, setSendRecipient] = useState('');
  const [scanPayOpen, setScanPayOpen] = useState(false);
  const [qrOpen, setQrOpen] = useState(false);
  const [scratchOpen, setScratchOpen] = useState(false);

  // 3D Card tilt state
  const [tilt, setTilt] = useState({ x: 0, y: 0 });

  const loadData = async () => {
    try {
      const res = await transactionService.getDashboard();
      if (res.success && res.data) {
        setSummary(res.data);
      }
    } catch (err) {
      // Handled gracefully
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadData();
    refreshBalance();
  }, []);

  const handleSendToContact = (contact) => {
    soundService.playKeypadClick();
    setSendRecipient(contact.mobile || contact.email);
    setSendOpen(true);
  };

  const handleCardMouseMove = (e) => {
    const card = e.currentTarget.getBoundingClientRect();
    const centerX = card.left + card.width / 2;
    const centerY = card.top + card.height / 2;
    const rotateX = ((e.clientY - centerY) / (card.height / 2)) * -8;
    const rotateY = ((e.clientX - centerX) / (card.width / 2)) * 8;
    setTilt({ x: rotateX, y: rotateY });
  };

  const handleCardMouseLeave = () => {
    setTilt({ x: 0, y: 0 });
  };

  return (
    <div className="space-y-6 max-w-5xl mx-auto pb-12 animate-in fade-in">
      {/* ---------------- 1. 3D METALLIC WALLET CARD & HERO ---------------- */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
        {/* Left: 3D Holographic Wallet Card */}
        <div
          onMouseMove={handleCardMouseMove}
          onMouseLeave={handleCardMouseLeave}
          style={{
            transform: `perspective(1000px) rotateX(${tilt.x}deg) rotateY(${tilt.y}deg)`,
            transition: 'transform 0.15s ease-out'
          }}
          className="lg:col-span-7 relative h-56 sm:h-64 rounded-3xl p-6 sm:p-7 text-white shadow-2xl overflow-hidden cursor-pointer select-none bg-gradient-to-tr from-[#003db3] via-[#0047cc] to-[#ff6200] border border-white/20"
        >
          {/* Holographic Sheen Overlay */}
          <div className="absolute -inset-full bg-gradient-to-r from-transparent via-white/10 to-transparent rotate-45 pointer-events-none" />

          {/* Card Top: Brand Logo + Contactless Waves */}
          <div className="relative z-10 flex items-center justify-between mb-4">
            <div className="bg-white/95 px-3 py-1 rounded-xl shadow-md">
              <img
                src="/payben-logo.svg"
                alt="PayBen"
                className="h-6 w-auto object-contain"
              />
            </div>
            <div className="flex items-center gap-2 text-white/80">
              <Wifi className="w-5 h-5 rotate-90" />
              <span className="text-[10px] font-bold tracking-widest uppercase bg-black/20 px-2 py-0.5 rounded-full border border-white/10">
                UPI FastPay
              </span>
            </div>
          </div>

          {/* EMV Gold Chip Icon */}
          <div className="relative z-10 flex items-center gap-3 mb-4">
            <div className="w-11 h-8 rounded-lg bg-gradient-to-br from-amber-200 via-yellow-400 to-amber-600 border border-yellow-200/60 shadow-inner flex flex-col justify-around p-1">
              <div className="w-full h-0.5 bg-amber-800/40 rounded-full" />
              <div className="w-full h-0.5 bg-amber-800/40 rounded-full" />
              <div className="w-full h-0.5 bg-amber-800/40 rounded-full" />
            </div>
            <span className="text-xs font-mono tracking-widest text-blue-100 font-bold">
              PAYBEN VIRTUAL PREMIER
            </span>
          </div>

          {/* Card Balance Display with Eye Toggle */}
          <div className="relative z-10 flex items-end justify-between mt-auto">
            <div>
              <div className="flex items-center gap-2 text-xs text-blue-100 font-medium">
                <span>Wallet Balance</span>
                <button
                  onClick={toggleHideBalance}
                  className="text-white hover:text-yellow-300 transition"
                  title={hideBalance ? 'Show Balance' : 'Hide Balance'}
                >
                  {hideBalance ? <EyeOff className="w-3.5 h-3.5" /> : <Eye className="w-3.5 h-3.5" />}
                </button>
              </div>
              <div className="text-2xl sm:text-3xl font-black tracking-tight text-white font-mono mt-0.5">
                {hideBalance ? '₹ ••••••' : wallet?.formatted_balance || '₹0.00'}
              </div>
              <p className="text-[11px] font-mono text-blue-200/90 mt-1">
                UPI ID: <strong className="text-white">{user?.upi_id}</strong>
              </p>
            </div>

            <div className="text-right">
              <span className="text-[10px] uppercase tracking-wider text-blue-200 block">Cardholder</span>
              <span className="text-sm font-bold text-white uppercase tracking-wide truncate max-w-[140px] block">
                {user?.full_name || 'PayBen User'}
              </span>
            </div>
          </div>
        </div>

        {/* Right: Quick Action Power Deck */}
        <div className="lg:col-span-5 bg-white rounded-3xl p-6 border border-gray-200/80 shadow-sm space-y-4">
          <div className="flex items-center justify-between">
            <span className="text-xs font-bold text-gray-500 uppercase tracking-wider">Quick Actions</span>
            {appLockEnabled && (
              <span className="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                <Shield className="w-3 h-3 text-emerald-600" />
                <span>{lockMethod.toUpperCase()} Protected</span>
              </span>
            )}
          </div>

          <div className="grid grid-cols-2 gap-3">
            <button
              onClick={() => { soundService.playKeypadClick(); setScanPayOpen(true); }}
              className="p-3.5 rounded-2xl bg-gradient-to-r from-[#0047cc] to-[#1a66ff] hover:brightness-110 text-white font-bold text-xs flex items-center justify-center gap-2 shadow-md shadow-blue-500/20 transition active:scale-95"
            >
              <Scan className="w-4 h-4 text-orange-300" />
              <span>Scan & Pay Any QR</span>
            </button>

            <button
              onClick={() => { soundService.playKeypadClick(); setQrOpen(true); }}
              className="p-3.5 rounded-2xl bg-orange-50 hover:bg-orange-100 text-[#ff6200] border border-orange-200/80 font-bold text-xs flex items-center justify-center gap-2 transition active:scale-95"
            >
              <QrCode className="w-4 h-4" />
              <span>My PayBen QR</span>
            </button>

            <button
              onClick={() => { soundService.playKeypadClick(); setSendRecipient(''); setSendOpen(true); }}
              className="p-3.5 rounded-2xl bg-blue-50 hover:bg-blue-100 text-[#0047cc] border border-blue-200/80 font-bold text-xs flex items-center justify-center gap-2 transition active:scale-95"
            >
              <Send className="w-4 h-4" />
              <span>To Mobile / UPI</span>
            </button>

            <button
              onClick={() => { soundService.playKeypadClick(); openAddMoney && openAddMoney(); }}
              className="p-3.5 rounded-2xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200/80 font-bold text-xs flex items-center justify-center gap-2 transition active:scale-95"
            >
              <Plus className="w-4 h-4 text-emerald-600" />
              <span>Add Cash</span>
            </button>
          </div>

          {/* SoundBox Voice Banner */}
          <div className="pt-2 flex items-center justify-between border-t border-gray-100 text-xs">
            <div className="flex items-center gap-2 text-gray-600">
              <Volume2 className="w-4 h-4 text-[#0047cc]" />
              <span className="font-semibold">Smart Voice SoundBox</span>
            </div>
            <button
              onClick={toggleSoundBox}
              className={`px-3 py-1 rounded-full text-[11px] font-bold transition ${
                soundBoxEnabled ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'
              }`}
            >
              {soundBoxEnabled ? 'Active 🔊' : 'Muted'}
            </button>
          </div>
        </div>
      </div>

      {/* ---------------- PROMOTIONAL AD ROTATOR BANNERS ---------------- */}
      <PromotionalBanners />

      {/* ---------------- SUPER-APP FINTECH HUB (PhonePe / GPay / Paytm) ---------------- */}
      <section className="bg-white rounded-3xl p-6 border border-gray-200/80 shadow-xs space-y-4">
        <div className="flex items-center justify-between">
          <div>
            <div className="flex items-center gap-2">
              <h2 className="text-base font-black text-gray-900">{t('fintechHub')}</h2>
              <span className="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white tracking-wide">
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
            className="p-3.5 rounded-2xl border border-gray-100 hover:border-blue-300 hover:bg-blue-50/30 transition-all flex flex-col items-center text-center group bg-gradient-to-b from-white to-gray-50/50"
          >
            <div className="w-11 h-11 rounded-2xl bg-blue-50 text-[#0047cc] flex items-center justify-center mb-2 group-hover:scale-110 group-hover:bg-[#0047cc] group-hover:text-white transition-all shadow-xs">
              <Building2 className="w-5 h-5" />
            </div>
            <span className="text-xs font-bold text-gray-900">{t('bankAccounts')}</span>
            <span className="text-[10px] text-gray-400 mt-0.5">Link & Check Bal</span>
          </Link>

          {/* UPI Lite */}
          <Link
            to="/financial/upi-lite"
            className="p-3.5 rounded-2xl border border-gray-100 hover:border-amber-300 hover:bg-amber-50/30 transition-all flex flex-col items-center text-center group bg-gradient-to-b from-white to-gray-50/50 relative overflow-hidden"
          >
            <span className="absolute top-1.5 right-1.5 text-[8px] font-black bg-amber-500 text-white px-1.5 py-0.2 rounded-full">
              ₹{Number(upiLiteBalance || 0).toFixed(0)}
            </span>
            <div className="w-11 h-11 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mb-2 group-hover:scale-110 group-hover:bg-amber-500 group-hover:text-white transition-all shadow-xs">
              <Zap className="w-5 h-5" />
            </div>
            <span className="text-xs font-bold text-gray-900">{t('upiLite')}</span>
            <span className="text-[10px] text-amber-600 font-semibold mt-0.5">Zero-PIN Pay</span>
          </Link>

          {/* AutoPay */}
          <Link
            to="/financial/autopay"
            className="p-3.5 rounded-2xl border border-gray-100 hover:border-indigo-300 hover:bg-indigo-50/30 transition-all flex flex-col items-center text-center group bg-gradient-to-b from-white to-gray-50/50"
          >
            <div className="w-11 h-11 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mb-2 group-hover:scale-110 group-hover:bg-indigo-600 group-hover:text-white transition-all shadow-xs">
              <RefreshCw className="w-5 h-5" />
            </div>
            <span className="text-xs font-bold text-gray-900">{t('autopay')}</span>
            <span className="text-[10px] text-gray-400 mt-0.5">SIP & Mandates</span>
          </Link>

          {/* Pocket Money */}
          <Link
            to="/financial/pocket-money"
            className="p-3.5 rounded-2xl border border-gray-100 hover:border-purple-300 hover:bg-purple-50/30 transition-all flex flex-col items-center text-center group bg-gradient-to-b from-white to-gray-50/50 relative overflow-hidden"
          >
            <span className="absolute top-1.5 right-1.5 text-[8px] font-black bg-purple-500 text-white px-1.5 py-0.2 rounded-full">
              ₹{Number(pocketMoneyBalance || 0).toFixed(0)}
            </span>
            <div className="w-11 h-11 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center mb-2 group-hover:scale-110 group-hover:bg-purple-600 group-hover:text-white transition-all shadow-xs">
              <PiggyBank className="w-5 h-5" />
            </div>
            <span className="text-xs font-bold text-gray-900">{t('pocketMoney')}</span>
            <span className="text-[10px] text-purple-600 font-semibold mt-0.5">Teen Allowance</span>
          </Link>

          {/* Cards Pay */}
          <Link
            to="/financial/cards"
            className="p-3.5 rounded-2xl border border-gray-100 hover:border-emerald-300 hover:bg-emerald-50/30 transition-all flex flex-col items-center text-center group bg-gradient-to-b from-white to-gray-50/50"
          >
            <div className="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-2 group-hover:scale-110 group-hover:bg-emerald-600 group-hover:text-white transition-all shadow-xs">
              <CreditCard className="w-5 h-5" />
            </div>
            <span className="text-xs font-bold text-gray-900">{t('cardsPay')}</span>
            <span className="text-[10px] text-gray-400 mt-0.5">Vault & Pay</span>
          </Link>

          {/* CIBIL Score & Loans */}
          <Link
            to="/financial/cibil-loans"
            className="p-3.5 rounded-2xl border border-gray-100 hover:border-rose-300 hover:bg-rose-50/30 transition-all flex flex-col items-center text-center group bg-gradient-to-b from-white to-gray-50/50 relative overflow-hidden"
          >
            <span className="absolute top-1.5 right-1.5 text-[8px] font-black bg-emerald-600 text-white px-1.5 py-0.2 rounded-full">
              {cibilScore}
            </span>
            <div className="w-11 h-11 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mb-2 group-hover:scale-110 group-hover:bg-rose-600 group-hover:text-white transition-all shadow-xs">
              <TrendingUp className="w-5 h-5" />
            </div>
            <span className="text-xs font-bold text-gray-900">{t('cibilLoan')}</span>
            <span className="text-[10px] text-emerald-600 font-bold mt-0.5">Pre-Approved</span>
          </Link>
        </div>
      </section>

      {/* ---------------- 2. PEOPLE ON PAYBEN (QUICK SEND AVATARS) ---------------- */}
      {summary?.contacts?.length > 0 && (
        <section className="bg-white rounded-3xl p-6 border border-gray-200/80 shadow-xs space-y-4">
          <div className="flex items-center justify-between">
            <div>
              <h2 className="text-base font-black text-gray-900">Send Money to Contacts</h2>
              <p className="text-xs text-gray-500">1-Tap instant UPI wallet transfer like PhonePe</p>
            </div>
            <button
              onClick={() => { soundService.playKeypadClick(); setSendRecipient(''); setSendOpen(true); }}
              className="text-xs font-bold text-[#0047cc] hover:underline"
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
                <div className="w-14 h-14 rounded-2xl bg-gradient-to-tr from-[#0047cc] to-[#ff6200] text-white font-black flex items-center justify-center text-lg shadow-md group-hover:scale-105 group-hover:ring-4 group-hover:ring-blue-100 transition-all">
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

      {/* ---------------- 3. CASHBACK & SCRATCH CARD REWARDS ---------------- */}
      <section
        onClick={() => { soundService.playKeypadClick(); setScratchOpen(true); }}
        className="relative overflow-hidden rounded-3xl bg-gradient-to-r from-amber-400 via-orange-500 to-amber-500 text-white p-6 shadow-md cursor-pointer group hover:shadow-xl transition-all"
      >
        <div className="relative z-10 flex items-center justify-between">
          <div className="flex items-center gap-4">
            <div className="w-14 h-14 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center text-white shrink-0 group-hover:rotate-12 transition-transform shadow-inner">
              <Gift className="w-7 h-7" />
            </div>
            <div>
              <div className="inline-flex items-center gap-1 text-[11px] font-bold uppercase tracking-wider text-amber-100">
                <Sparkles className="w-3.5 h-3.5 text-yellow-200" />
                <span>Rewards Hub • Cashback Inside</span>
              </div>
              <h3 className="text-lg font-black tracking-tight text-white mt-0.5">
                You have a Golden Scratch Card!
              </h3>
              <p className="text-xs text-amber-100">
                Scratch with your finger or mouse to reveal up to ₹500 instant wallet cashback.
              </p>
            </div>
          </div>
          <button className="hidden sm:inline-flex px-4 py-2.5 rounded-xl bg-white text-gray-900 font-black text-xs shadow-md group-hover:scale-105 transition-transform">
            Scratch & Win
          </button>
        </div>
      </section>

      {/* ---------------- 4. BILLS & UTILITIES ---------------- */}
      <section className="bg-white rounded-3xl p-6 border border-gray-200/80 shadow-xs space-y-4">
        <div className="flex items-center justify-between">
          <div>
            <h2 className="text-base font-black text-gray-900">Recharges & Bill Payments</h2>
            <p className="text-xs text-gray-500">BBPS certified instant bill settlements</p>
          </div>
        </div>

        <div className="grid grid-cols-2 sm:grid-cols-5 gap-3">
          {[
            { name: 'Mobile', desc: 'Jio, Airtel, Vi, BSNL', link: '/recharge/mobile', icon: Smartphone, color: 'text-blue-600 bg-blue-50' },
            { name: 'DTH TV', desc: 'Tata Play, Dish TV...', link: '/recharge/dth', icon: Tv, color: 'text-orange-600 bg-orange-50' },
            { name: 'Electricity', desc: 'State power boards', link: '/bills/electricity', icon: Zap, color: 'text-yellow-600 bg-yellow-50' },
            { name: 'Water Bill', desc: 'Municipal utilities', link: '/bills/water', icon: Droplet, color: 'text-cyan-600 bg-cyan-50' },
            { name: 'Gas Piped', desc: 'Adani, IGL, Gujarat Gas', link: '/bills/gas', icon: Flame, color: 'text-rose-600 bg-rose-50' },
          ].map((item, idx) => {
            const Icon = item.icon;
            return (
              <Link
                key={idx}
                to={item.link}
                className="p-4 rounded-2xl border border-gray-100 hover:border-blue-200 hover:bg-blue-50/20 transition-all flex flex-col items-center text-center group"
              >
                <div className={`w-12 h-12 rounded-2xl ${item.color} flex items-center justify-center mb-2 group-hover:scale-110 transition-transform shadow-xs`}>
                  <Icon className="w-5 h-5" />
                </div>
                <span className="text-xs font-bold text-gray-800">{item.name}</span>
                <span className="text-[10px] text-gray-400 mt-0.5 truncate max-w-[100px]">{item.desc}</span>
              </Link>
            );
          })}
        </div>
      </section>

      {/* ---------------- 5. RECENT ACTIVITY FEED ---------------- */}
      <section className="bg-white rounded-3xl p-6 border border-gray-200/80 shadow-xs space-y-4">
        <div className="flex items-center justify-between">
          <div>
            <h2 className="text-base font-black text-gray-900">Recent Transactions</h2>
            <p className="text-xs text-gray-500">Live payment statements and UPI transfers</p>
          </div>
          <Link
            to="/transactions"
            className="text-xs font-bold text-[#0047cc] hover:underline flex items-center gap-1"
          >
            <span>View Passbook</span>
            <ChevronRight className="w-3.5 h-3.5" />
          </Link>
        </div>

        {summary?.recent_transactions?.length > 0 ? (
          <div className="divide-y divide-gray-100">
            {summary.recent_transactions.map((tx) => (
              <div key={tx.id} className="py-3.5 flex items-center justify-between gap-4">
                <div className="flex items-center gap-3">
                  <div className={`w-10 h-10 rounded-xl flex items-center justify-center shrink-0 ${
                    tx.status === 'SUCCESS' ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600'
                  }`}>
                    {tx.type === 'wallet_topup' ? (
                      <ArrowDownLeft className="w-5 h-5" />
                    ) : (
                      <ArrowUpRight className="w-5 h-5" />
                    )}
                  </div>
                  <div>
                    <p className="text-sm font-bold text-gray-900">{tx.description || tx.operator_or_provider}</p>
                    <p className="text-xs text-gray-400">
                      {new Date(tx.created_at).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })} • {tx.transaction_id}
                    </p>
                  </div>
                </div>

                <div className="text-right">
                  <div className={`text-sm font-black font-mono ${
                    tx.type === 'wallet_topup' && tx.description?.includes('Added')
                      ? 'text-emerald-600'
                      : 'text-gray-900'
                  }`}>
                    {tx.type === 'wallet_topup' && tx.description?.includes('Added') ? '+' : '-'}₹{parseFloat(tx.amount).toFixed(2)}
                  </div>
                  <span className="text-[10px] font-bold text-emerald-600 uppercase">
                    {tx.status}
                  </span>
                </div>
              </div>
            ))}
          </div>
        ) : (
          <div className="text-center py-8 text-gray-400 text-xs">
            No transactions yet. Complete your first recharge or bill payment!
          </div>
        )}
      </section>

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
