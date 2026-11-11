import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { adminService } from '../../services/adminService';
import { useToast } from '../../context/ToastContext';
import { ShieldCheck, Lock, User, Loader2, ArrowRight } from 'lucide-react';

export const AdminLogin = () => {
  const [username, setUsername] = useState('admin@payben.com');
  const [password, setPassword] = useState('admin123');
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
        <div className="text-center space-y-2">
          <div className="w-14 h-14 rounded-2xl bg-purple-600 text-white flex items-center justify-center mx-auto shadow-lg shadow-purple-600/30">
            <ShieldCheck className="w-8 h-8" />
          </div>
          <h2 className="text-2xl font-black tracking-tight">PayBen Admin</h2>
          <p className="text-xs text-slate-400">Restricted staff oversight access</p>
        </div>

        {/* Credentials Box */}
        <div className="p-3 bg-purple-950/40 border border-purple-800/40 rounded-2xl text-xs space-y-1">
          <span className="font-bold text-purple-400">Default Admin Credentials</span>
          <p className="text-slate-400 font-mono text-[11px]">
            User: <strong className="text-white">admin@payben.com</strong> • Pass: <strong className="text-white">admin123</strong>
          </p>
        </div>

        <form onSubmit={handleLogin} className="space-y-4">
          <div>
            <label className="block text-xs font-semibold text-slate-400 mb-1">Username / Email</label>
            <div className="relative">
              <User className="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                type="text"
                required
                value={username}
                onChange={(e) => setUsername(e.target.value)}
                className="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-800 border border-slate-700 text-slate-100 text-sm outline-none focus:border-purple-500 transition"
              />
            </div>
          </div>

          <div>
            <label className="block text-xs font-semibold text-slate-400 mb-1">Password</label>
            <div className="relative">
              <Lock className="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                type="password"
                required
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                className="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-800 border border-slate-700 text-slate-100 text-sm outline-none focus:border-purple-500 transition"
              />
            </div>
          </div>

          <button
            type="submit"
            disabled={loading}
            className="w-full py-3 bg-purple-600 hover:bg-purple-700 disabled:bg-purple-800 text-white font-bold rounded-xl flex items-center justify-center gap-2 transition"
          >
            {loading ? <Loader2 className="w-5 h-5 animate-spin" /> : <span>Access Control Panel</span>}
          </button>
        </form>
      </div>
    </div>
  );
};

