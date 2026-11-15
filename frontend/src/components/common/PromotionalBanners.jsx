import React, { useState, useEffect } from 'react';
import { Tag, Sparkles, ChevronRight, Gift, Percent } from 'lucide-react';
import { useToast } from '../../context/ToastContext';

export const PromotionalBanners = () => {
  const { addToast } = useToast();
  const [currentIdx, setCurrentIdx] = useState(0);

  const offers = [
    {
      id: 'promo_1',
      brand: 'Swiggy Food',
      title: 'Flat ₹100 Cashback on Food Delivery',
      desc: 'Pay using PayBen UPI on Swiggy on orders above ₹299.',
      code: 'PAYBENSWIGGY',
      gradient: 'from-orange-500 via-amber-500 to-yellow-500',
      tag: 'SPONSORED'
    },
    {
      id: 'promo_2',
      brand: 'Amazon Great Indian Deals',
      title: '10% Instant Discount on Shopping',
      desc: 'Use PayBen Virtual RuPay card at checkout for max ₹500 off.',
      code: 'AMZPAYBEN',
      gradient: 'from-blue-600 via-indigo-600 to-violet-700',
      tag: 'POPULAR'
    },
    {
      id: 'promo_3',
      brand: 'Zomato Dining & Gold',
      title: 'Buy 1 Get 1 Free on Dining & Cinema',
      desc: 'Instant cashback credited directly into PayBen Wallet.',
      code: 'ZOMATOPAY',
      gradient: 'from-rose-600 via-red-500 to-orange-500',
      tag: 'LIMITED TIME'
    },
    {
      id: 'promo_4',
      brand: 'Tata Neu & BigBasket',
      title: '₹150 Grocery Cashback on Bill Pay',
      desc: 'Get flat cashback on your next electricity or gas bill payment.',
      code: 'GROCERY150',
      gradient: 'from-emerald-600 via-teal-600 to-cyan-700',
      tag: 'HOT DEAL'
    }
  ];

  useEffect(() => {
    const timer = setInterval(() => {
      setCurrentIdx((prev) => (prev + 1) % offers.length);
    }, 4500);
    return () => clearInterval(timer);
  }, [offers.length]);

  const copyCode = (code) => {
    navigator.clipboard.writeText(code);
    addToast(`Promo code ${code} copied!`, 'success');
  };

  const curr = offers[currentIdx];

  return (
    <div className="relative overflow-hidden rounded-3xl bg-gradient-to-r p-5 text-white shadow-lg transition-all duration-700 select-none border border-white/20">
      <div className={`absolute inset-0 bg-gradient-to-r ${curr.gradient} transition-all duration-700`} />
      <div className="absolute -right-8 -bottom-8 w-44 h-44 rounded-full bg-white/10 blur-2xl pointer-events-none" />

      <div className="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div className="space-y-1">
          <div className="flex items-center gap-2">
            <span className="text-[10px] font-black tracking-widest uppercase bg-black/25 px-2 py-0.5 rounded-full border border-white/20">
              {curr.tag}
            </span>
            <span className="text-xs font-bold text-white/90">{curr.brand}</span>
          </div>
          <h3 className="text-base sm:text-lg font-black tracking-tight">{curr.title}</h3>
          <p className="text-xs text-white/80 max-w-md">{curr.desc}</p>
        </div>

        <div className="flex items-center gap-3 shrink-0">
          <button
            onClick={() => copyCode(curr.code)}
            className="px-4 py-2 bg-white/20 hover:bg-white/30 backdrop-blur-md rounded-xl text-xs font-mono font-bold text-white border border-white/30 transition flex items-center gap-1.5 active:scale-95 shadow-sm"
          >
            <Tag className="w-3.5 h-3.5" />
            <span>Code: {curr.code}</span>
          </button>
        </div>
      </div>

      {/* Dots Indicator */}
      <div className="relative z-10 flex gap-1.5 justify-center sm:justify-start mt-3">
        {offers.map((_, i) => (
          <button
            key={i}
            onClick={() => setCurrentIdx(i)}
            className={`h-1.5 rounded-full transition-all duration-300 ${
              i === currentIdx ? 'w-6 bg-white' : 'w-2 bg-white/40'
            }`}
          />
        ))}
      </div>
    </div>
  );
};
