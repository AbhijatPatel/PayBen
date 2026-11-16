import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { useToast } from '../../context/ToastContext';
import { GoogleLoginButton } from '../../components/auth/GoogleLoginButton';
import { Mail, Lock, Eye, EyeOff, Loader2, ArrowRight, Sparkles, Shield } from 'lucide-react';

export const Login = () => {
  const { login, googleLogin } = useAuth();
  const { addToast } = useToast();
  const navigate = useNavigate();

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    try {
      const res = await login(email, password);
      if (res.success) {
        addToast('Welcome back to PayBen!', 'success');
        navigate('/dashboard');
      }
    } catch (err) {
      addToast(err.message, 'error');
    } finally {
      setLoading(false);
    }
  };

  const handleGoogleSuccess = async (payload) => {
    try {
      const res = await googleLogin(payload);
      if (res.success) {
        addToast('Welcome to PayBen Sandbox via Google!', 'success');
        navigate('/dashboard');
      }
    } catch (err) {
      addToast(err.message || 'Google sign-in could not be completed.', 'error');
    }
  };

  const fillDemoCredentials = (demoEmail, demoPass) => {
    setEmail(demoEmail);
    setPassword(demoPass);
    addToast(`Demo account credentials filled (${demoEmail})`, 'info');
  };

  return (
    <div className="min-h-[85vh] flex items-center justify-center p-4">
      <div className="max-w-md w-full bg-white rounded-3xl shadow-xl border border-gray-200/80 p-8 space-y-6">
        <div className="text-center space-y-2">
          <img
            src="/payben-logo.svg"
            alt="PayBen — Payments Made Simple."
            className="w-56 h-auto mx-auto mb-4"
          />
          <h2 className="text-2xl font-black text-gray-900 tracking-tight">Welcome back</h2>
          <p className="text-xs text-gray-500">Smart UPI & Instant Bill Payments</p>
        </div>

        {/* Google Sign In Integration */}
        <div className="space-y-3">
          <GoogleLoginButton
            onSuccess={handleGoogleSuccess}
            onError={(err) => addToast(err.message || 'Google sign-in failed', 'error')}
            text="Continue with Google"
          />

          <div className="relative flex items-center justify-center pt-2">
            <div className="border-t border-gray-200 w-full" />
            <span className="bg-white px-3 text-[11px] font-semibold text-gray-400 uppercase tracking-wider absolute">
              or sign in with email
            </span>
          </div>
        </div>

        <form onSubmit={handleSubmit} className="space-y-4 pt-1">
          <div>
            <label className="block text-xs font-semibold text-gray-600 mb-1">Email Address</label>
            <div className="relative">
              <Mail className="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                type="email"
                required
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="user1@test.com"
                className="w-full pl-10 pr-4 py-3 rounded-2xl border border-gray-200 focus:border-[#102f6f] focus:ring-2 focus:ring-blue-100 outline-none text-sm font-medium"
              />
            </div>
          </div>

          <div>
            <div className="flex items-center justify-between mb-1">
              <label className="block text-xs font-semibold text-gray-600">Password</label>
              <span className="text-[11px] text-blue-600 hover:underline cursor-pointer">
                Forgot?
              </span>
            </div>
            <div className="relative">
              <Lock className="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                type={showPassword ? 'text' : 'password'}
                required
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="password123"
                className="w-full pl-10 pr-10 py-3 rounded-2xl border border-gray-200 focus:border-[#102f6f] focus:ring-2 focus:ring-blue-100 outline-none text-sm font-medium"
              />
              <button
                type="button"
                onClick={() => setShowPassword(!showPassword)}
                className="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
              >
                {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
              </button>
            </div>
          </div>

          <button
            type="submit"
            disabled={loading}
            className="w-full py-3.5 bg-[#102f6f] hover:bg-[#0b2450] disabled:bg-blue-300 text-white font-bold rounded-2xl flex items-center justify-center gap-2 transition shadow-md shadow-blue-500/20"
          >
            {loading ? (
              <Loader2 className="w-5 h-5 animate-spin" />
            ) : (
              <>
                <span>Sign In to PayBen</span>
                <ArrowRight className="w-4 h-4" />
              </>
            )}
          </button>
        </form>

        {/* 1-Click Fast Sandbox Accounts */}
        <div className="bg-slate-50 border border-slate-200/80 rounded-2xl p-3.5 space-y-2">
          <div className="flex items-center justify-between text-xs">
            <span className="font-bold text-slate-700 flex items-center gap-1.5">
              <Sparkles className="w-3.5 h-3.5 text-amber-500" />
              1-Click Sandbox Accounts:
            </span>
            <span className="text-[10px] text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full font-semibold">
              Instant Fill
            </span>
          </div>
          <div className="grid grid-cols-2 gap-2">
            <button
              type="button"
              onClick={() => fillDemoCredentials('user1@test.com', 'password123')}
              className="text-left p-2.5 bg-white hover:bg-blue-50/60 border border-gray-200 hover:border-blue-300 rounded-xl transition group shadow-2xs"
            >
              <p className="font-bold text-gray-800 text-xs group-hover:text-blue-700">Rahul S.</p>
              <p className="text-[10px] text-gray-400 font-mono truncate">user1@test.com</p>
            </button>
            <button
              type="button"
              onClick={() => fillDemoCredentials('user2@test.com', 'password123')}
              className="text-left p-2.5 bg-white hover:bg-blue-50/60 border border-gray-200 hover:border-blue-300 rounded-xl transition group shadow-2xs"
            >
              <p className="font-bold text-gray-800 text-xs group-hover:text-blue-700">Priya P.</p>
              <p className="text-[10px] text-gray-400 font-mono truncate">user2@test.com</p>
            </button>
          </div>
        </div>

        <div className="text-center pt-2 border-t border-gray-100">
          <p className="text-xs text-gray-500">
            Don't have an account?{' '}
            <Link to="/register" className="font-bold text-[#ff6500] hover:underline">
              Register Now
            </Link>
          </p>
        </div>
      </div>
    </div>
  );
};

export default Login;
