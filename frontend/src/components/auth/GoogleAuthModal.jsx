import React, { useState } from 'react';
import { X, User, Plus, CheckCircle, ShieldCheck } from 'lucide-react';

const PRESET_GOOGLE_ACCOUNTS = [
  {
    name: 'Rahul Sharma',
    email: 'rahul.sharma@gmail.com',
    avatar: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=100&auto=format&fit=crop&q=80',
    google_id: 'g_104829103948291029381'
  },
  {
    name: 'Priya Patel',
    email: 'priya.patel@gmail.com',
    avatar: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=100&auto=format&fit=crop&q=80',
    google_id: 'g_104829103948291029382'
  },
  {
    name: 'Abhijat Patel',
    email: 'abhijat.payben@gmail.com',
    avatar: 'https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?w=100&auto=format&fit=crop&q=80',
    google_id: 'g_104829103948291029383'
  }
];

export const GoogleAuthModal = ({ isOpen, onClose, onSelectAccount }) => {
  const [showCustomInput, setShowCustomInput] = useState(false);
  const [customName, setCustomName] = useState('');
  const [customEmail, setCustomEmail] = useState('');
  const [submitting, setSubmitting] = useState(false);

  if (!isOpen) return null;

  const handleChoose = async (account) => {
    setSubmitting(true);
    try {
      await onSelectAccount(account);
    } finally {
      setSubmitting(false);
    }
  };

  const handleCustomSubmit = (e) => {
    e.preventDefault();
    if (!customEmail) return;
    const name = customName.trim() || customEmail.split('@')[0];
    handleChoose({
      name,
      email: customEmail.trim().toLowerCase(),
      avatar: `https://api.dicebear.com/7.x/initials/svg?seed=${encodeURIComponent(name)}`,
      google_id: `g_${Date.now()}_${Math.floor(Math.random() * 10000)}`
    });
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-fade-in">
      <div className="bg-white w-full max-w-sm rounded-3xl shadow-2xl border border-gray-100 overflow-hidden relative">
        {/* Google Header */}
        <div className="px-6 pt-6 pb-4 border-b border-gray-100 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <svg className="w-6 h-6" viewBox="0 0 24 24">
              <path
                fill="#4285F4"
                d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
              />
              <path
                fill="#34A853"
                d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
              />
              <path
                fill="#FBBC05"
                d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"
              />
              <path
                fill="#EA4335"
                d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"
              />
            </svg>
            <div>
              <h3 className="text-base font-bold text-gray-900 leading-tight">Choose an account</h3>
              <p className="text-xs text-gray-500">to continue to PayBen Sandbox</p>
            </div>
          </div>
          <button
            onClick={onClose}
            className="p-1 rounded-full text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Account List */}
        <div className="p-4 space-y-2 max-h-80 overflow-y-auto">
          {PRESET_GOOGLE_ACCOUNTS.map((acc) => (
            <button
              key={acc.email}
              type="button"
              disabled={submitting}
              onClick={() => handleChoose(acc)}
              className="w-full flex items-center gap-3.5 p-3 rounded-2xl hover:bg-blue-50/70 border border-transparent hover:border-blue-100 transition text-left group"
            >
              <img
                src={acc.avatar}
                alt={acc.name}
                className="w-10 h-10 rounded-full object-cover border border-gray-200"
              />
              <div className="flex-1 min-w-0">
                <p className="text-sm font-semibold text-gray-900 group-hover:text-blue-700 truncate">
                  {acc.name}
                </p>
                <p className="text-xs text-gray-500 truncate">{acc.email}</p>
              </div>
              <CheckCircle className="w-4 h-4 text-blue-600 opacity-0 group-hover:opacity-100 transition" />
            </button>
          ))}

          {/* Use another account option */}
          {!showCustomInput ? (
            <button
              type="button"
              disabled={submitting}
              onClick={() => setShowCustomInput(true)}
              className="w-full flex items-center gap-3.5 p-3 rounded-2xl hover:bg-gray-50 border border-dashed border-gray-200 transition text-left mt-2 text-gray-700 hover:text-gray-900"
            >
              <div className="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-500">
                <Plus className="w-5 h-5" />
              </div>
              <span className="text-xs font-semibold">Use another Google account</span>
            </button>
          ) : (
            <form onSubmit={handleCustomSubmit} className="p-3 bg-gray-50 rounded-2xl space-y-3 mt-2 border border-gray-200">
              <p className="text-xs font-bold text-gray-700">Enter Google Account Details</p>
              <div>
                <input
                  type="text"
                  placeholder="Your Name (e.g. John Doe)"
                  value={customName}
                  onChange={(e) => setCustomName(e.target.value)}
                  className="w-full px-3 py-2 text-xs rounded-xl border border-gray-200 outline-none focus:border-blue-500"
                />
              </div>
              <div>
                <input
                  type="email"
                  required
                  placeholder="name@gmail.com"
                  value={customEmail}
                  onChange={(e) => setCustomEmail(e.target.value)}
                  className="w-full px-3 py-2 text-xs rounded-xl border border-gray-200 outline-none focus:border-blue-500"
                />
              </div>
              <div className="flex gap-2">
                <button
                  type="submit"
                  disabled={submitting}
                  className="flex-1 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition shadow-sm"
                >
                  {submitting ? 'Connecting...' : 'Sign In with this Account'}
                </button>
                <button
                  type="button"
                  onClick={() => setShowCustomInput(false)}
                  className="px-3 py-2 text-xs text-gray-500 hover:bg-gray-200 rounded-xl"
                >
                  Cancel
                </button>
              </div>
            </form>
          )}
        </div>

        {/* Footer */}
        <div className="px-6 py-3 bg-gray-50/70 border-t border-gray-100 flex items-center justify-between text-[11px] text-gray-400">
          <div className="flex items-center gap-1.5 text-emerald-700 font-medium">
            <ShieldCheck className="w-3.5 h-3.5" />
            <span>Zero Paid APIs • Open-Source Sandbox</span>
          </div>
          <span className="font-mono">OAuth 2.0</span>
        </div>
      </div>
    </div>
  );
};
