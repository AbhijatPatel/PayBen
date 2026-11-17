import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { useToast } from '../../context/ToastContext';
import { User, Mail, Smartphone, Lock, Eye, EyeOff, Loader2, ArrowRight, Gift } from 'lucide-react';

export const Register = () => {
  const { register } = useAuth();
  const { addToast } = useToast();
  const navigate = useNavigate();

  const [fullName, setFullName] = useState('');
  const [email, setEmail] = useState('');
  const [mobile, setMobile] = useState('');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [errors, setErrors] = useState({});
  const [formError, setFormError] = useState('');

  const handleSubmit = async (e) => {
    e.preventDefault();
    const nextErrors = {};
    if (fullName.trim().length < 3) nextErrors.fullName = 'Enter your full name (at least 3 characters).';
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim())) nextErrors.email = 'Enter a valid email address.';
    if (!/^[6-9]\d{9}$/.test(mobile)) nextErrors.mobile = 'Enter a valid 10-digit Indian mobile number.';
    if (password.length < 6) nextErrors.password = 'Password must be at least 6 characters.';
    if (confirmPassword !== password) nextErrors.confirmPassword = 'Passwords do not match.';
    setErrors(nextErrors);
    setFormError('');
    if (Object.keys(nextErrors).length) return;

    setLoading(true);
    try {
      const res = await register(fullName.trim(), email.trim(), mobile, password, confirmPassword);
      if (res.success) {
        addToast('Registration successful! ₹500 welcome bonus added to your wallet.', 'success');
        navigate('/dashboard');
      }
    } catch (error) {
      setFormError(error.message || 'Unable to create your account. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center p-4">
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

        <div className="p-3 bg-emerald-50 border border-emerald-100 rounded-2xl flex items-center gap-2.5 text-xs text-emerald-900 font-semibold">
          <Gift className="w-5 h-5 text-emerald-600 shrink-0" />
          <span>₹500 welcome bonus loaded automatically upon sign-up!</span>
        </div>

        <form onSubmit={handleSubmit} noValidate className="space-y-4">
          <div>
            <label className="block text-xs font-semibold text-gray-600 mb-1">Full Name</label>
            <div className="relative">
              <User className="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                type="text"
                autoComplete="name"
                required
                value={fullName}
                onChange={(e) => {
                  setFullName(e.target.value);
                  setErrors((current) => ({ ...current, fullName: '' }));
                  setFormError('');
                }}
                aria-invalid={Boolean(errors.fullName)}
                placeholder="Rahul Sharma"
                className="w-full pl-10 pr-4 py-3 rounded-2xl border border-gray-200 focus:border-[#102f6f] focus:ring-2 focus:ring-blue-100 outline-none text-sm font-medium"
              />
            </div>
            {errors.fullName && <p className="mt-1 text-xs text-red-700">{errors.fullName}</p>}
          </div>

          <div>
            <label className="block text-xs font-semibold text-gray-600 mb-1">Email Address</label>
            <div className="relative">
              <Mail className="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                type="email"
                autoComplete="email"
                inputMode="email"
                required
                value={email}
                onChange={(e) => {
                  setEmail(e.target.value);
                  setErrors((current) => ({ ...current, email: '' }));
                  setFormError('');
                }}
                aria-invalid={Boolean(errors.email)}
                placeholder="rahul@example.com"
                className="w-full pl-10 pr-4 py-3 rounded-2xl border border-gray-200 focus:border-[#102f6f] focus:ring-2 focus:ring-blue-100 outline-none text-sm font-medium"
              />
            </div>
            {errors.email && <p className="mt-1 text-xs text-red-700">{errors.email}</p>}
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
                autoComplete="tel-national"
                onChange={(e) => {
                  setMobile(e.target.value.replace(/\D/g, ''));
                  setErrors((current) => ({ ...current, mobile: '' }));
                  setFormError('');
                }}
                aria-invalid={Boolean(errors.mobile)}
                placeholder="9876543210"
                className="w-full pl-10 pr-4 py-3 rounded-2xl border border-gray-200 focus:border-[#102f6f] focus:ring-2 focus:ring-blue-100 outline-none text-sm font-medium"
              />
            </div>
            {errors.mobile && <p className="mt-1 text-xs text-red-700">{errors.mobile}</p>}
          </div>

          <div>
            <label className="block text-xs font-semibold text-gray-600 mb-1">Create Password</label>
            <div className="relative">
              <Lock className="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                type={showPassword ? 'text' : 'password'}
                required
                minLength={6}
                autoComplete="new-password"
                value={password}
                onChange={(e) => {
                  setPassword(e.target.value);
                  setErrors((current) => ({ ...current, password: '', confirmPassword: '' }));
                  setFormError('');
                }}
                aria-invalid={Boolean(errors.password)}
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
            {errors.password && <p className="mt-1 text-xs text-red-700">{errors.password}</p>}
          </div>

          <div>
            <label htmlFor="register-confirm-password" className="block text-xs font-semibold text-gray-600 mb-1">Confirm Password</label>
            <input
              id="register-confirm-password"
              type={showPassword ? 'text' : 'password'}
              required
              autoComplete="new-password"
              value={confirmPassword}
              onChange={(e) => {
                setConfirmPassword(e.target.value);
                setErrors((current) => ({ ...current, confirmPassword: '' }));
                setFormError('');
              }}
              aria-invalid={Boolean(errors.confirmPassword)}
              placeholder="Re-enter your password"
              className="w-full px-4 py-3 rounded-2xl border border-gray-200 focus:border-[#102f6f] focus:ring-2 focus:ring-blue-100 outline-none text-sm font-medium"
            />
            {errors.confirmPassword && <p className="mt-1 text-xs text-red-700">{errors.confirmPassword}</p>}
          </div>

          {formError && (
            <p className="rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-800" role="alert">
              {formError}
            </p>
          )}

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
