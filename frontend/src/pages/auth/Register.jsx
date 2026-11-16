import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { useToast } from '../../context/ToastContext';
import { GoogleLoginButton } from '../../components/auth/GoogleLoginButton';
import { User, Mail, Smartphone, Lock, Eye, EyeOff, Loader2, ArrowRight, Gift } from 'lucide-react';

export const Register = () => {
  const { register, googleLogin } = useAuth();
  const { addToast } = useToast();
  const navigate = useNavigate();

  const [fullName, setFullName] = useState('');
  const [email, setEmail] = useState('');
  const [mobile, setMobile] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (mobile.length !== 10) {
      addToast('Please enter a 10-digit Indian mobile number', 'error');
      return;
    }
    setLoading(true);
    try {
      const res = await register(fullName, email, mobile, password);
      if (res.success) {
        addToast('Registration successful! ₹500 welcome bonus added to your wallet.', 'success');
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
        addToast('Welcome to PayBen! ₹500 welcome bonus loaded.', 'success');
        navigate('/dashboard');
      }
    } catch (err) {
      addToast(err.message || 'Google sign-up failed', 'error');
    }
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
          <h2 className="text-2xl font-black text-gray-900 tracking-tight">Create your account</h2>
          <p className="text-xs text-gray-500">Sign up and get ₹500 instant wallet credit</p>
        </div>

        {/* Google Quick Sign-Up */}
        <div className="space-y-3">
          <GoogleLoginButton
            onSuccess={handleGoogleSuccess}
            onError={(err) => addToast(err.message || 'Google sign-up failed', 'error')}
            text="Sign up with Google"
          />

          <div className="relative flex items-center justify-center pt-2">
            <div className="border-t border-gray-200 w-full" />
            <span className="bg-white px-3 text-[11px] font-semibold text-gray-400 uppercase tracking-wider absolute">
              or register with details
            </span>
          </div>
        </div>

        <div className="p-3 bg-emerald-50 border border-emerald-100 rounded-2xl flex items-center gap-2.5 text-xs text-emerald-900 font-semibold">
          <Gift className="w-5 h-5 text-emerald-600 shrink-0" />
          <span>₹500 welcome bonus loaded automatically upon sign-up!</span>
        </div>

        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="block text-xs font-semibold text-gray-600 mb-1">Full Name</label>
            <div className="relative">
              <User className="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                type="text"
                required
                value={fullName}
                onChange={(e) => setFullName(e.target.value)}
                placeholder="Rahul Sharma"
                className="w-full pl-10 pr-4 py-3 rounded-2xl border border-gray-200 focus:border-[#102f6f] focus:ring-2 focus:ring-blue-100 outline-none text-sm font-medium"
              />
            </div>
          </div>

          <div>
            <label className="block text-xs font-semibold text-gray-600 mb-1">Email Address</label>
            <div className="relative">
              <Mail className="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                type="email"
                required
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="rahul@example.com"
                className="w-full pl-10 pr-4 py-3 rounded-2xl border border-gray-200 focus:border-[#102f6f] focus:ring-2 focus:ring-blue-100 outline-none text-sm font-medium"
              />
            </div>
          </div>

          <div>
            <label className="block text-xs font-semibold text-gray-600 mb-1">Mobile Number</label>
            <div className="relative">
              <Smartphone className="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                type="tel"
                required
                maxLength={10}
                value={mobile}
                onChange={(e) => setMobile(e.target.value.replace(/\D/g, ''))}
                placeholder="9876543210"
                className="w-full pl-10 pr-4 py-3 rounded-2xl border border-gray-200 focus:border-[#102f6f] focus:ring-2 focus:ring-blue-100 outline-none text-sm font-medium"
              />
            </div>
          </div>

          <div>
            <label className="block text-xs font-semibold text-gray-600 mb-1">Create Password</label>
            <div className="relative">
              <Lock className="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                type={showPassword ? 'text' : 'password'}
                required
                minLength={6}
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="Minimum 6 characters"
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
                <span>Complete Registration</span>
                <ArrowRight className="w-4 h-4" />
              </>
            )}
          </button>
        </form>

        <div className="text-center pt-2 border-t border-gray-100">
          <p className="text-xs text-gray-500">
            Already have an account?{' '}
            <Link to="/login" className="font-bold text-[#ff6500] hover:underline">
              Sign In
            </Link>
          </p>
        </div>
      </div>
    </div>
  );
};
