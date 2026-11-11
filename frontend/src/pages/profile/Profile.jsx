import React from 'react';
import { useAuth } from '../../context/AuthContext';
import { useToast } from '../../context/ToastContext';
import { User, Mail, Smartphone, ShieldCheck, Wallet, Copy, LogOut } from 'lucide-react';
import { useNavigate } from 'react-router-dom';

export const Profile = () => {
  const { user, wallet, logout } = useAuth();
  const { addToast } = useToast();
  const navigate = useNavigate();

  const handleCopyUpi = () => {
    if (user?.upi_id) {
      navigator.clipboard.writeText(user.upi_id);
      addToast('UPI ID copied to clipboard!', 'success');
    }
  };

  const handleLogout = async () => {
    await logout();
    addToast('Signed out successfully.', 'info');
    navigate('/login');
  };

  if (!user) return null;

  return (
    <div className="max-w-2xl mx-auto space-y-6 pb-12">
      {/* Profile Header Card */}
      <div className="bg-white rounded-3xl p-6 sm:p-8 border border-gray-100 shadow-sm flex flex-col sm:flex-row items-center gap-6 text-center sm:text-left">
        <div className="w-20 h-20 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-black text-3xl flex items-center justify-center shadow-lg shadow-blue-500/20 shrink-0">
          {user.full_name?.charAt(0).toUpperCase()}
        </div>

        <div className="space-y-1 flex-1">
          <div className="flex flex-col sm:flex-row sm:items-center gap-2">
            <h1 className="text-2xl font-black text-gray-900">{user.full_name}</h1>
            <span className="inline-block text-[10px] font-bold uppercase tracking-wider text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 rounded-full w-fit mx-auto sm:mx-0">
              {user.status || 'Active'}
            </span>
          </div>
          <p className="text-xs text-gray-500">{user.email}</p>
          <div className="pt-2 flex flex-wrap items-center justify-center sm:justify-start gap-2">
            <div className="bg-blue-50 border border-blue-200 text-[#1a73e8] px-3 py-1 rounded-xl text-xs font-mono font-bold flex items-center gap-1.5">
              <span>{user.upi_id}</span>
              <button onClick={handleCopyUpi} title="Copy UPI ID">
                <Copy className="w-3.5 h-3.5" />
              </button>
            </div>
          </div>
        </div>
      </div>

      {/* Account Info Details */}
      <div className="bg-white rounded-3xl p-6 sm:p-8 border border-gray-100 shadow-sm space-y-4">
        <h2 className="text-base font-bold text-gray-900">Personal Information</h2>

        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
          <div className="p-4 rounded-2xl bg-gray-50 border border-gray-100 space-y-1">
            <span className="text-xs text-gray-400 flex items-center gap-1.5">
              <User className="w-3.5 h-3.5" />
              Full Name
            </span>
            <p className="font-bold text-gray-900">{user.full_name}</p>
          </div>

          <div className="p-4 rounded-2xl bg-gray-50 border border-gray-100 space-y-1">
            <span className="text-xs text-gray-400 flex items-center gap-1.5">
              <Mail className="w-3.5 h-3.5" />
              Registered Email
            </span>
            <p className="font-bold text-gray-900">{user.email}</p>
          </div>

          <div className="p-4 rounded-2xl bg-gray-50 border border-gray-100 space-y-1">
            <span className="text-xs text-gray-400 flex items-center gap-1.5">
              <Smartphone className="w-3.5 h-3.5" />
              Mobile Number
            </span>
            <p className="font-bold text-gray-900">+91 {user.mobile}</p>
          </div>

          <div className="p-4 rounded-2xl bg-gray-50 border border-gray-100 space-y-1">
            <span className="text-xs text-gray-400 flex items-center gap-1.5">
              <Wallet className="w-3.5 h-3.5" />
              Digital Wallet
            </span>
            <p className="font-bold text-emerald-600">{wallet?.formatted_balance}</p>
          </div>
        </div>

        <div className="pt-4 border-t border-gray-100 flex justify-between items-center">
          <div className="flex items-center gap-2 text-xs text-gray-400">
            <ShieldCheck className="w-4 h-4 text-emerald-500" />
            <span>Encrypted Session • Verified Account</span>
          </div>

          <button
            onClick={handleLogout}
            className="flex items-center gap-1.5 text-xs font-bold text-red-600 bg-red-50 hover:bg-red-100 px-4 py-2 rounded-xl transition"
          >
            <LogOut className="w-4 h-4" />
            <span>Sign Out</span>
          </button>
        </div>
      </div>
    </div>
  );
};
