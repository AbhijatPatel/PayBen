import React, { useState, useEffect } from 'react';
import { Link, useOutletContext } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { transactionService } from '../../services/transactionService';
import { SendMoneyModal } from '../../components/modals/SendMoneyModal';
import { QrModal } from '../../components/modals/QrModal';
import { ScratchCardModal } from '../../components/modals/ScratchCardModal';
import {
  Send,
  QrCode,
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
  Wallet as WalletIcon
} from 'lucide-react';

export const Dashboard = () => {
  const { user, wallet, refreshBalance } = useAuth();
  const { openAddMoney } = useOutletContext() || {};

  const [summary, setSummary] = useState(null);
  const [loading, setLoading] = useState(true);

  // Modals state
  const [sendOpen, setSendOpen] = useState(false);
  const [sendRecipient, setSendRecipient] = useState('');
  const [qrOpen, setQrOpen] = useState(false);
  const [scratchOpen, setScratchOpen] = useState(false);

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
    setSendRecipient(contact.mobile || contact.email);
    setSendOpen(true);
  };

  return (
    <div className="space-y-6 max-w-5xl mx-auto pb-10">
      {/* 1. Header Card: UPI & Wallet Surface */}
      <div className="relative overflow-hidden rounded-3xl bg-gradient-to-tr from-[#1a73e8] via-[#1557b0] to-[#0d47a1] text-white p-6 sm:p-8 shadow-xl">
        <div className="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div className="space-y-1">
            <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md text-xs font-semibold text-blue-200 border border-white/15">
              <span>PayBen Smart UPI</span>
              <span className="text-[10px] bg-emerald-400 text-emerald-950 px-1.5 py-0.2 rounded-full font-bold">ACTIVE</span>
            </div>
            <h1 className="text-2xl sm:text-3xl font-black tracking-tight">
              Hello, {user?.full_name || 'User'}
            </h1>
            <p className="text-xs sm:text-sm font-mono text-blue-100/90 flex items-center gap-1.5 pt-1">
              <span>UPI ID:</span>
              <strong className="text-white bg-white/10 px-2 py-0.5 rounded-md">{user?.upi_id}</strong>
            </p>
          </div>

          <div className="flex flex-col sm:flex-row items-start sm:items-center gap-3">
            <div className="bg-white/10 backdrop-blur-md border border-white/15 rounded-2xl p-4 min-w-[180px]">
              <span className="text-xs text-blue-200 font-medium block">Wallet Balance</span>
              <div className="text-2xl sm:text-3xl font-black mt-0.5 tracking-tight">
                {wallet?.formatted_balance || '₹0.00'}
              </div>
            </div>

            <div className="flex gap-2">
              <button
                onClick={openAddMoney}
                className="px-4 py-3 rounded-2xl bg-white text-gray-900 font-bold text-xs flex items-center gap-2 hover:bg-gray-100 transition shadow-md"
              >
                <Plus className="w-4 h-4 text-[#1a73e8]" />
                <span>Add Money</span>
              </button>
              <button
                onClick={() => setQrOpen(true)}
                className="p-3 rounded-2xl bg-white/15 hover:bg-white/25 text-white transition backdrop-blur-md border border-white/20"
                title="Show QR Code"
              >
                <QrCode className="w-5 h-5" />
              </button>
            </div>
          </div>
        </div>
      </div>

      {/* 2. Quick Actions */}
      <div className="grid grid-cols-3 sm:grid-cols-6 gap-3">
        {[
          { label: 'Send Money', icon: Send, action: () => { setSendRecipient(''); setSendOpen(true); }, color: 'bg-blue-50 text-[#1a73e8]' },
          { label: 'Scan / QR', icon: QrCode, action: () => setQrOpen(true), color: 'bg-purple-50 text-purple-600' },
          { label: 'Add Cash', icon: Plus, action: openAddMoney, color: 'bg-emerald-50 text-emerald-600' },
          { label: 'Mobile', icon: Smartphone, link: '/recharge/mobile', color: 'bg-amber-50 text-amber-600' },
          { label: 'Electricity', icon: Zap, link: '/bills/electricity', color: 'bg-yellow-50 text-yellow-600' },
          { label: 'All Bills', icon: Droplet, link: '/bills/water', color: 'bg-cyan-50 text-cyan-600' },
        ].map((act, i) => (
          act.link ? (
            <Link
              key={i}
              to={act.link}
              className="flex flex-col items-center justify-center p-3.5 bg-white rounded-2xl border border-gray-100 shadow-xs hover:shadow-md hover:border-blue-200 transition-all group"
            >
              <div className={`w-12 h-12 rounded-2xl ${act.color} flex items-center justify-center mb-2 group-hover:scale-110 transition-transform`}>
                <act.icon className="w-5 h-5" />
              </div>
              <span className="text-xs font-bold text-gray-700 text-center">{act.label}</span>
            </Link>
          ) : (
            <button
              key={i}
              onClick={act.action}
              className="flex flex-col items-center justify-center p-3.5 bg-white rounded-2xl border border-gray-100 shadow-xs hover:shadow-md hover:border-blue-200 transition-all group"
            >
              <div className={`w-12 h-12 rounded-2xl ${act.color} flex items-center justify-center mb-2 group-hover:scale-110 transition-transform`}>
                <act.icon className="w-5 h-5" />
              </div>
              <span className="text-xs font-bold text-gray-700 text-center">{act.label}</span>
            </button>
          )
        ))}
      </div>

      {/* 3. People / Send to Friends Carousel */}
      {summary?.contacts?.length > 0 && (
        <section className="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs space-y-4">
          <div className="flex items-center justify-between">
            <div>
              <h2 className="text-base font-bold text-gray-900">People on PayBen</h2>
              <p className="text-xs text-gray-500">Tap to send money instantly</p>
            </div>
            <button
              onClick={() => { setSendRecipient(''); setSendOpen(true); }}
              className="text-xs font-bold text-[#1a73e8] hover:underline"
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
                <div className="w-14 h-14 rounded-full bg-gradient-to-tr from-blue-500 to-indigo-600 text-white font-bold flex items-center justify-center text-base shadow-sm group-hover:scale-105 group-hover:ring-4 group-hover:ring-blue-100 transition-all">
                  {c.full_name?.charAt(0).toUpperCase()}
                </div>
                <span className="text-xs font-semibold text-gray-800 mt-2 max-w-[70px] truncate text-center">
                  {c.full_name.split(' ')[0]}
                </span>
                <span className="text-[10px] text-gray-400">Pay</span>
              </div>
            ))}
          </div>
        </section>
      )}

      {/* 4. Scratch Card Reward Banner */}
      <section
        onClick={() => setScratchOpen(true)}
        className="relative overflow-hidden rounded-3xl bg-gradient-to-r from-amber-400 via-amber-500 to-yellow-500 text-white p-6 shadow-md cursor-pointer group hover:shadow-lg transition-all"
      >
        <div className="relative z-10 flex items-center justify-between">
          <div className="flex items-center gap-4">
            <div className="w-14 h-14 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center text-white shrink-0 group-hover:rotate-12 transition-transform">
              <Gift className="w-7 h-7" />
            </div>
            <div>
              <div className="inline-flex items-center gap-1 text-[11px] font-bold uppercase tracking-wider text-amber-100">
                <Sparkles className="w-3.5 h-3.5" />
                <span>Cashback Reward Available</span>
              </div>
              <h3 className="text-lg font-black tracking-tight text-white mt-0.5">
                You have a mystery scratch card!
              </h3>
              <p className="text-xs text-amber-100">Tap to reveal and win up to ₹50 cashback directly into your wallet.</p>
            </div>
          </div>
          <button className="hidden sm:inline-flex px-4 py-2 rounded-xl bg-white text-gray-900 font-bold text-xs shadow-sm group-hover:scale-105 transition-transform">
            Scratch Now
          </button>
        </div>
      </section>

      {/* 5. Bills & Utilities Grid */}
      <section className="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs space-y-4">
        <h2 className="text-base font-bold text-gray-900">Bills & Recharges</h2>
        <div className="grid grid-cols-2 sm:grid-cols-5 gap-3">
          {[
            { name: 'Mobile', desc: 'Prepaid & Postpaid', link: '/recharge/mobile', icon: Smartphone, color: 'text-blue-600 bg-blue-50' },
            { name: 'DTH TV', desc: 'Tata Play, Airtel...', link: '/recharge/dth', icon: Tv, color: 'text-purple-600 bg-purple-50' },
            { name: 'Electricity', desc: 'State power boards', link: '/bills/electricity', icon: Zap, color: 'text-yellow-600 bg-yellow-50' },
            { name: 'Water', desc: 'Municipal boards', link: '/bills/water', icon: Droplet, color: 'text-cyan-600 bg-cyan-50' },
            { name: 'Gas', desc: 'Piped & Cylinders', link: '/bills/gas', icon: Flame, color: 'text-red-600 bg-red-50' },
          ].map((item, idx) => {
            const Icon = item.icon;
            return (
              <Link
                key={idx}
                to={item.link}
                className="p-4 rounded-2xl border border-gray-100 hover:border-blue-200 hover:bg-blue-50/20 transition-all flex flex-col items-center text-center group"
              >
                <div className={`w-12 h-12 rounded-2xl ${item.color} flex items-center justify-center mb-2 group-hover:scale-110 transition-transform`}>
                  <Icon className="w-5 h-5" />
                </div>
                <span className="text-sm font-bold text-gray-800">{item.name}</span>
                <span className="text-[10px] text-gray-400 mt-0.5">{item.desc}</span>
              </Link>
            );
          })}
        </div>
      </section>

      {/* 6. Recent Transactions Feed */}
      <section className="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs space-y-4">
        <div className="flex items-center justify-between">
          <div>
            <h2 className="text-base font-bold text-gray-900">Recent Activity</h2>
            <p className="text-xs text-gray-500">Your latest transactions and recharges</p>
          </div>
          <Link
            to="/transactions"
            className="text-xs font-bold text-[#1a73e8] hover:underline flex items-center gap-1"
          >
            <span>View All</span>
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
                  <div className={`text-sm font-black ${
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
