import React, { useState, useEffect } from 'react';
import { merchantService } from '../../services/merchantService';
import { useToast } from '../../context/ToastContext';
import {
  ShoppingBag,
  ExternalLink,
  Search,
  Sparkles,
  ShieldCheck,
  AlertCircle,
  Tag,
  Star,
  ArrowUpRight
} from 'lucide-react';

export const ShoppingMarketplace = () => {
  const { addToast } = useToast();
  const [merchants, setMerchants] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchQuery, setSearchQuery] = useState('');
  const [activeFilter, setActiveFilter] = useState('all');

  const fetchMerchants = async () => {
    setLoading(true);
    try {
      // Pass all=true so users can see active and inactive merchants (Jabong disabled)
      const res = await merchantService.list('shopping', true);
      if (res && res.success) {
        setMerchants(res.merchants || []);
      }
    } catch (err) {
      addToast('Error loading merchants', 'error');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchMerchants();
  }, []);

  const handleOpenStore = async (m) => {
    if (!m.is_enabled) {
      addToast(`${m.name} is currently inactive and disabled.`, 'warning');
      return;
    }

    try {
      const res = await merchantService.redirect(m.slug);
      if (res && res.success && res.official_url) {
        window.open(res.official_url, '_blank', 'noopener,noreferrer');
      } else {
        window.open(m.official_url, '_blank', 'noopener,noreferrer');
      }
    } catch {
      window.open(m.official_url, '_blank', 'noopener,noreferrer');
    }
  };

  const filtered = merchants.filter((m) => {
    const matchesSearch =
      m.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
      m.description.toLowerCase().includes(searchQuery.toLowerCase());
    if (activeFilter === 'active') return matchesSearch && m.is_enabled;
    if (activeFilter === 'inactive') return matchesSearch && !m.is_enabled;
    return matchesSearch;
  });

  return (
    <div className="space-y-6 max-w-5xl mx-auto pb-12 animate-in fade-in">
      {/* Banner */}
      <div className="p-6 sm:p-8 bg-[#101D30] text-white rounded-3xl border border-slate-800 shadow-md flex items-center justify-between">
        <div className="space-y-1">
          <span className="text-[10px] font-mono font-bold text-[#35D6B0] uppercase tracking-wider block">
            E-Commerce & Brand Discovery Hub
          </span>
          <h1 className="text-2xl sm:text-3xl font-black">Shopping Directory</h1>
          <p className="text-xs text-slate-300">Explore trusted online platforms with verified official partner redirection.</p>
        </div>
        <div className="w-12 h-12 rounded-2xl bg-white/10 text-[#35D6B0] flex items-center justify-center shrink-0">
          <ShoppingBag className="w-6 h-6" />
        </div>
      </div>

      {/* Disclaimers Box */}
      <div className="p-4 bg-blue-50/70 rounded-2xl border border-blue-100 flex items-start gap-3 text-xs text-blue-900">
        <ShieldCheck className="w-5 h-5 text-blue-700 shrink-0 mt-0.5" />
        <div>
          <span className="font-bold">Verified External Redirection Policy:</span> PayBen does not capture passwords or fake orders. Clicking "Open Store" securely launches the verified official website or mobile application of each partner merchant.
        </div>
      </div>

      {/* Search and Filters */}
      <div className="flex flex-col sm:flex-row items-center justify-between gap-3">
        <div className="relative w-full sm:w-72">
          <Search className="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
          <input
            type="text"
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            placeholder="Search stores or deals..."
            className="w-full pl-9 pr-4 py-2.5 rounded-2xl border border-gray-200 text-xs font-semibold text-gray-800 bg-white outline-none focus:border-[#35D6B0]"
          />
        </div>

        <div className="flex gap-2 self-start sm:self-auto">
          {[
            { id: 'all', label: 'All Merchants' },
            { id: 'active', label: 'Active Stores' },
            { id: 'inactive', label: 'Legacy / Inactive' }
          ].map((f) => (
            <button
              key={f.id}
              onClick={() => setActiveFilter(f.id)}
              className={`px-3 py-1.5 rounded-xl text-xs font-bold transition ${
                activeFilter === f.id
                  ? 'bg-[#101D30] text-[#35D6B0]'
                  : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200/80'
              }`}
            >
              {f.label}
            </button>
          ))}
        </div>
      </div>

      {/* Merchant Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        {filtered.map((m) => (
          <div
            key={m.slug}
            className={`p-6 bg-white rounded-3xl border transition-all flex flex-col justify-between ${
              m.is_enabled
                ? 'border-gray-200/80 shadow-xs hover:border-[#35D6B0] hover:shadow-md'
                : 'border-gray-200 bg-gray-50/60 opacity-70'
            }`}
          >
            <div>
              <div className="flex items-center justify-between mb-3">
                <div className="flex items-center gap-3">
                  <div className="w-12 h-12 rounded-2xl bg-[#101D30] text-[#35D6B0] flex items-center justify-center font-black text-lg shadow-sm">
                    {m.name.charAt(0)}
                  </div>
                  <div>
                    <h3 className="text-base font-extrabold text-[#101D30] flex items-center gap-2">
                      <span>{m.name}</span>
                      {m.badge_text && (
                        <span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                          {m.badge_text}
                        </span>
                      )}
                    </h3>
                    <span className="text-[10px] text-gray-400 font-mono">
                      Integration: {m.integration_type}
                    </span>
                  </div>
                </div>

                {m.is_enabled ? (
                  <span className="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">
                    Active
                  </span>
                ) : (
                  <span className="text-[10px] font-bold text-rose-600 bg-rose-50 px-2.5 py-0.5 rounded-full border border-rose-200">
                    Disabled
                  </span>
                )}
              </div>

              <p className="text-xs text-gray-600 leading-relaxed">
                {m.description}
              </p>
            </div>

            <div className="mt-5 pt-4 border-t border-gray-100 flex items-center justify-between">
              <span className="text-[11px] text-gray-400 font-mono truncate max-w-[200px]">
                {m.official_url}
              </span>

              <button
                onClick={() => handleOpenStore(m)}
                disabled={!m.is_enabled}
                className={`px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 transition active:scale-95 ${
                  m.is_enabled
                    ? 'bg-[#101D30] text-[#35D6B0] hover:bg-[#1a2f4d] shadow-xs'
                    : 'bg-gray-200 text-gray-400 cursor-not-allowed'
                }`}
              >
                <span>{m.is_enabled ? 'Open Store' : 'Unavailable'}</span>
                {m.is_enabled && <ExternalLink className="w-3.5 h-3.5" />}
              </button>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

export default ShoppingMarketplace;
