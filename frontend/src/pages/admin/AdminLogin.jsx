import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { adminService } from '../../services/adminService';
import { useToast } from '../../context/ToastContext';
import { Lock, User, Loader2, ArrowRight, ShieldCheck } from 'lucide-react';

export const AdminLogin = () => {
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const { addToast } = useToast();
  const navigate = useNavigate();

  const handleLogin = async (e) => {
    e.preventDefault();
    setLoading(true);
    try {
      const res = await adminService.login(username, password);
      if (res.success && res.data) {
        localStorage.setItem('payben_admin', JSON.stringify(res.data.admin));
        addToast('Admin authorization granted.', 'success');
        navigate('/admin/dashboard');
      }
    } catch (err) {
      addToast(err.message, 'error');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-slate-950 flex items-center justify-center p-4">
      <div className="max-w-md w-full bg-slate-900 border border-slate-800 rounded-3xl p-8 space-y-6 text-slate-100 shadow-2xl">
        <div className="text-center space-y-3">
          <div className="p-3 bg-white/5 rounded-2xl inline-block border border-white/10 shadow-md">
            <img
              src="/payben-logo.png"
              alt="PayBen"
              className="h-10 w-auto mx-auto object-contain"
            />
          </div>
          <h2 className="text-2xl font-black tracking-tight text-white">PayBen Administration</h2>
          <p className="text-xs text-slate-400">Encrypted Enterprise Back-Office Portal</p>
        </div>

        <form onSubmit={handleLogin} className="space-y-4">
          <div>
            <label className="block text-xs font-semibold text-slate-400 mb-1">Staff Username / Email</label>
            <div className="relative">
              <User className="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                type="text"
                required
                value={username}
                onChange={(e) => setUsername(e.target.value)}
                placeholder="admin@payben.com"
                className="w-full pl-10 pr-4 py-3 rounded-2xl bg-slate-800 border border-slate-700 text-slate-100 text-sm outline-none focus:border-[#ff6200] transition"
              />
            </div>
          </div>

          <div>
            <label className="block text-xs font-semibold text-slate-400 mb-1">Staff Password</label>
            <div className="relative">
              <Lock className="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                type="password"
                required
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="••••••••"
                className="w-full pl-10 pr-4 py-3 rounded-2xl bg-slate-800 border border-slate-700 text-slate-100 text-sm outline-none focus:border-[#ff6200] transition"
              />
            </div>
          </div>

          <button
            type="submit"
            disabled={loading}
            className="w-full py-3.5 bg-gradient-to-r from-[#0047cc] to-[#ff6200] hover:brightness-110 disabled:opacity-50 text-white font-bold rounded-2xl flex items-center justify-center gap-2 transition shadow-lg shadow-orange-500/20"
          >
            {loading ? <Loader2 className="w-5 h-5 animate-spin" /> : <span>Access Administration Console</span>}
          </button>
        </form>

        <div className="text-center pt-2">
          <p className="text-[11px] text-slate-500 flex items-center justify-center gap-1.5">
            <ShieldCheck className="w-4 h-4 text-emerald-500" />
            <span>Role-Based Access Control • 256-Bit SSL Secured</span>
          </p>
        </div>
      </div>
    </div>
  );
};
