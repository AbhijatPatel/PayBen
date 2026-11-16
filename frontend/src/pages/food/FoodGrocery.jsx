import React from 'react';
import { merchantService } from '../../services/merchantService';
import { useToast } from '../../context/ToastContext';
import {
  Utensils,
  ShoppingBag,
  ExternalLink,
  ShieldCheck,
  Clock,
  Sparkles,
  CheckCircle2,
  Bike
} from 'lucide-react';

export const FoodGrocery = () => {
  const { addToast } = useToast();

  const handleOpenSwiggy = async () => {
    try {
      const res = await merchantService.redirect('swiggy');
      if (res && res.success && res.official_url) {
        window.open(res.official_url, '_blank', 'noopener,noreferrer');
      } else {
        window.open('https://www.swiggy.com', '_blank', 'noopener,noreferrer');
      }
    } catch {
      window.open('https://www.swiggy.com', '_blank', 'noopener,noreferrer');
    }
  };

  return (
    <div className="space-y-6 max-w-5xl mx-auto pb-12 animate-in fade-in">
      {/* Banner */}
      <div className="p-6 sm:p-8 bg-[#101D30] text-white rounded-3xl border border-slate-800 shadow-md flex items-center justify-between">
        <div className="space-y-1">
          <span className="text-[10px] font-mono font-bold text-[#35D6B0] uppercase tracking-wider block">
            Instant Cravings & Pantry Essentials
          </span>
          <h1 className="text-2xl sm:text-3xl font-black">Food & Grocery Services</h1>
          <p className="text-xs text-slate-300">Order from top restaurants and grocery stores with verified official redirection.</p>
        </div>
        <div className="w-12 h-12 rounded-2xl bg-white/10 text-[#35D6B0] flex items-center justify-center shrink-0">
          <Utensils className="w-6 h-6" />
        </div>
      </div>

      {/* Disclaimers Box */}
      <div className="p-4 bg-orange-50/70 rounded-2xl border border-orange-100 flex items-start gap-3 text-xs text-orange-950">
        <ShieldCheck className="w-5 h-5 text-orange-600 shrink-0 mt-0.5" />
        <div>
          <span className="font-bold">Official Application Access:</span> In accordance with PayBen zero-fabrication standards, we do not invent restaurant menus or simulated delivery drivers. Clicking below directly opens the official Swiggy application for secure ordering.
        </div>
      </div>

      {/* Swiggy Featured Service Card */}
      <div className="p-6 sm:p-8 bg-white rounded-3xl border border-gray-200/80 shadow-xs space-y-6">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-gray-100">
          <div className="flex items-center gap-4">
            <div className="w-16 h-16 rounded-3xl bg-orange-500 text-white flex items-center justify-center font-black text-2xl shadow-md shadow-orange-500/20">
              S
            </div>
            <div>
              <div className="flex items-center gap-2">
                <h2 className="text-xl font-extrabold text-[#101D30]">Swiggy</h2>
                <span className="text-[10px] font-bold px-2.5 py-0.5 rounded-full bg-orange-50 text-orange-700 border border-orange-200">
                  Food & Instamart
                </span>
              </div>
              <p className="text-xs text-gray-500 mt-0.5">
                India’s leading on-demand convenience platform
              </p>
            </div>
          </div>

          <button
            onClick={handleOpenSwiggy}
            className="px-6 py-3 rounded-2xl bg-[#101D30] text-[#35D6B0] hover:bg-[#1a2f4d] font-extrabold text-xs sm:text-sm flex items-center justify-center gap-2 transition active:scale-95 shadow-sm"
          >
            <span>Open Swiggy</span>
            <ExternalLink className="w-4 h-4" />
          </button>
        </div>

        {/* Feature Pills */}
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div className="p-4 rounded-2xl bg-[#F5F7FA] border border-gray-100 space-y-1">
            <div className="flex items-center gap-2 text-[#101D30] font-bold text-xs">
              <Utensils className="w-4 h-4 text-orange-500" />
              <span>Food Delivery</span>
            </div>
            <p className="text-[11px] text-gray-500 leading-relaxed">
              Explore thousands of top-rated restaurants, cafes, bakeries & cloud kitchens in your city.
            </p>
          </div>

          <div className="p-4 rounded-2xl bg-[#F5F7FA] border border-gray-100 space-y-1">
            <div className="flex items-center gap-2 text-[#101D30] font-bold text-xs">
              <ShoppingBag className="w-4 h-4 text-emerald-600" />
              <span>Swiggy Instamart</span>
            </div>
            <p className="text-[11px] text-gray-500 leading-relaxed">
              Fresh produce, dairy, snacks, beverages and daily household essentials delivered in 10-15 mins.
            </p>
          </div>

          <div className="p-4 rounded-2xl bg-[#F5F7FA] border border-gray-100 space-y-1">
            <div className="flex items-center gap-2 text-[#101D30] font-bold text-xs">
              <Bike className="w-4 h-4 text-blue-600" />
              <span>Swiggy Genie</span>
            </div>
            <p className="text-[11px] text-gray-500 leading-relaxed">
              Pick up and drop anything across town — documents, lunch boxes, laundry, or forgotten items.
            </p>
          </div>
        </div>

        <div className="pt-2 flex items-center justify-between text-xs text-gray-400">
          <span>Official Domain: https://www.swiggy.com</span>
          <span className="font-mono text-emerald-600 font-bold">Integration: EXTERNAL_REDIRECT</span>
        </div>
      </div>
    </div>
  );
};

export default FoodGrocery;
