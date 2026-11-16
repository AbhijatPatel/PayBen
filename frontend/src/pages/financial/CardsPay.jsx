import React, { useState } from 'react';
import { useFintech } from '../../context/FintechContext';
import { useToast } from '../../context/ToastContext';
import { soundService } from '../../services/soundService';
import {
  CreditCard,
  Plus,
  ShieldCheck,
  Lock,
  Wifi,
  CheckCircle2,
  Calendar,
  Key,
  ArrowRight
} from 'lucide-react';

export const CardsPay = () => {
  const { cards, addCard } = useFintech();
  const { addToast } = useToast();

  const [addModalOpen, setAddModalOpen] = useState(false);
  const [holderName, setHolderName] = useState('Abhijat Patel');
  const [cardNumber, setCardNumber] = useState('');
  const [expiry, setExpiry] = useState('');
  const [cvv, setCvv] = useState('');
  const [bankLabel, setBankLabel] = useState('HDFC Bank');
  const [cardType, setCardType] = useState('Credit Card');

  // Detect card network
  const getNetwork = (num) => {
    const clean = num.replace(/\s/g, '');
    if (clean.startsWith('4')) return 'VISA';
    if (clean.startsWith('5')) return 'Mastercard';
    if (clean.startsWith('6')) return 'RuPay';
    if (clean.startsWith('3')) return 'Amex';
    return 'VISA';
  };

  const handleCardNumberChange = (e) => {
    const val = e.target.value.replace(/\D/g, '').slice(0, 16);
    // Format in groups of 4
    const formatted = val.replace(/(.{4})/g, '$1 ').trim();
    setCardNumber(formatted);
  };

  const handleExpiryChange = (e) => {
    let val = e.target.value.replace(/\D/g, '').slice(0, 4);
    if (val.length >= 2) {
      val = `${val.slice(0, 2)}/${val.slice(2)}`;
    }
    setExpiry(val);
  };

  const handleSaveCard = (e) => {
    e.preventDefault();
    if (cardNumber.replace(/\s/g, '').length !== 16) {
      addToast('Please enter a valid 16-digit card number', 'error');
      return;
    }
    if (expiry.length < 5) {
      addToast('Please enter MM/YY expiry date', 'error');
      return;
    }
    if (cvv.length < 3) {
      addToast('Please enter a valid 3-digit CVV', 'error');
      return;
    }

    addCard({
      card_holder: holderName,
      raw_number: cardNumber.replace(/\s/g, ''),
      expiry,
      bank: bankLabel,
      type: cardType
    });

    addToast('Card encrypted & saved to PayBen vault!', 'success');
    setAddModalOpen(false);
    setCardNumber('');
    setExpiry('');
    setCvv('');
  };

  return (
    <div className="max-w-4xl mx-auto space-y-6 pb-12 animate-in fade-in">
      {/* Banner */}
      <div className="bg-gradient-to-r from-[#0047cc] to-[#ff6200] rounded-3xl p-6 text-white shadow-xl flex items-center justify-between relative overflow-hidden">
        <div className="relative z-10 space-y-1">
          <div className="flex items-center gap-2">
            <CreditCard className="w-6 h-6 text-yellow-300" />
            <h2 className="text-2xl font-black tracking-tight">Credit & Debit Cards Vault</h2>
          </div>
          <p className="text-xs text-blue-100 font-medium">
            Tokenized PCI-DSS compliant cards for fast checkout and international payments
          </p>
        </div>
        <div className="hidden sm:block opacity-20">
          <CreditCard className="w-32 h-32" />
        </div>
      </div>

      {/* Cards Grid */}
      <div className="bg-white rounded-3xl border border-gray-200/80 p-6 shadow-xs space-y-4">
        <div className="flex items-center justify-between pb-3 border-b border-gray-100">
          <div>
            <h3 className="text-base font-bold text-gray-900">Saved Payment Cards</h3>
            <p className="text-xs text-gray-500">Encrypted tokenization with RBI compliance</p>
          </div>
          <button
            onClick={() => setAddModalOpen(true)}
            className="px-4 py-2 rounded-xl bg-[#0047cc] hover:bg-[#003db3] text-white text-xs font-bold transition flex items-center gap-1.5 shadow-sm"
          >
            <Plus className="w-4 h-4" />
            <span>Add New Card</span>
          </button>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
          {(cards || []).map((card) => (
            <div
              key={card.id}
              className={`relative h-56 rounded-3xl p-6 text-white shadow-xl flex flex-col justify-between overflow-hidden bg-gradient-to-tr ${card.gradient} border border-white/20 select-none group transition-transform hover:scale-[1.02]`}
            >
              <div className="flex items-center justify-between">
                <span className="font-bold text-xs uppercase tracking-wider">{card.bank}</span>
                <span className="font-black text-sm tracking-widest">{card.network}</span>
              </div>

              <div className="flex items-center gap-3">
                <div className="w-10 h-7 rounded bg-amber-300 border border-yellow-100 shadow-inner" />
                <Wifi className="w-4 h-4 rotate-90 text-white/80" />
                <span className="text-sm font-mono tracking-widest font-black">
                  {card.card_number}
                </span>
              </div>

              <div className="flex items-end justify-between">
                <div>
                  <span className="text-[10px] text-white/70 uppercase tracking-wider block">Cardholder</span>
                  <span className="text-xs font-bold uppercase truncate max-w-[140px] block">
                    {card.card_holder}
                  </span>
                </div>
                <div className="text-right">
                  <span className="text-[10px] text-white/70 uppercase tracking-wider block">Expires</span>
                  <span className="text-xs font-mono font-bold block">{card.expiry}</span>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Add Card Modal */}
      {addModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-in fade-in">
          <div className="bg-white rounded-3xl p-6 max-w-md w-full space-y-4 shadow-2xl border border-gray-100">
            <h3 className="text-base font-black text-gray-900">Add Credit or Debit Card</h3>
            <form onSubmit={handleSaveCard} className="space-y-3">
              <div>
                <label className="block text-xs font-bold text-gray-700 mb-1">Cardholder Name</label>
                <input
                  type="text"
                  required
                  placeholder="Name on card"
                  value={holderName}
                  onChange={(e) => setHolderName(e.target.value)}
                  className="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs font-bold outline-none focus:border-[#0047cc]"
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-gray-700 mb-1">Card Number</label>
                <div className="relative">
                  <input
                    type="text"
                    required
                    placeholder="4123 4567 8901 2345"
                    value={cardNumber}
                    onChange={handleCardNumberChange}
                    className="w-full pl-3 pr-14 py-2.5 rounded-xl border border-gray-200 text-xs font-mono font-bold outline-none focus:border-[#0047cc]"
                  />
                  <span className="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] font-black text-[#0047cc] bg-blue-50 px-1.5 py-0.5 rounded">
                    {getNetwork(cardNumber)}
                  </span>
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-bold text-gray-700 mb-1">Expiry (MM/YY)</label>
                  <input
                    type="text"
                    required
                    placeholder="MM/YY"
                    value={expiry}
                    onChange={handleExpiryChange}
                    className="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs font-mono font-bold outline-none focus:border-[#0047cc]"
                  />
                </div>

                <div>
                  <label className="block text-xs font-bold text-gray-700 mb-1">CVV</label>
                  <input
                    type="password"
                    maxLength={4}
                    required
                    placeholder="•••"
                    value={cvv}
                    onChange={(e) => setCvv(e.target.value.replace(/\D/g, ''))}
                    className="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs font-mono font-bold outline-none focus:border-[#0047cc]"
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-bold text-gray-700 mb-1">Bank Name</label>
                  <input
                    type="text"
                    placeholder="HDFC / SBI / ICICI"
                    value={bankLabel}
                    onChange={(e) => setBankLabel(e.target.value)}
                    className="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs font-bold outline-none focus:border-[#0047cc]"
                  />
                </div>

                <div>
                  <label className="block text-xs font-bold text-gray-700 mb-1">Card Type</label>
                  <select
                    value={cardType}
                    onChange={(e) => setCardType(e.target.value)}
                    className="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs font-bold outline-none"
                  >
                    <option value="Credit Card">Credit Card</option>
                    <option value="Debit Card">Debit Card</option>
                  </select>
                </div>
              </div>

              <div className="flex gap-2 pt-2">
                <button
                  type="button"
                  onClick={() => setAddModalOpen(false)}
                  className="flex-1 py-2.5 bg-gray-100 rounded-xl text-xs font-bold text-gray-600 hover:bg-gray-200"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="flex-1 py-2.5 bg-[#0047cc] text-white rounded-xl text-xs font-bold hover:bg-[#003db3] shadow-md shadow-blue-500/20"
                >
                  Save Card
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
