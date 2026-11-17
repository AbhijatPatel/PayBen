import React, { useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { Loader2, Lock } from 'lucide-react';
import { authService } from '../../services/authService';

const getResetDetails = (hash) => {
  const params = new URLSearchParams(hash.replace(/^#/, ''));
  return {
    token: params.get('token') || '',
    email: params.get('email') || ''
  };
};

export const ResetPassword = () => {
  const location = useLocation();
  const navigate = useNavigate();
  const { token, email: initialEmail } = getResetDetails(location.hash);
  const [email, setEmail] = useState(initialEmail);
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState(false);

  const handleSubmit = async (event) => {
    event.preventDefault();
    if (!token || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim())) {
      setError('This password reset link is invalid or has expired.');
      return;
    }
    if (password.length < 6) {
      setError('Password must be at least 6 characters.');
      return;
    }
    if (password !== confirmPassword) {
      setError('Passwords do not match.');
      return;
    }

    setLoading(true);
    setError('');
    try {
      await authService.resetPassword(email.trim(), token, password, confirmPassword);
      setSuccess(true);
      setPassword('');
      setConfirmPassword('');
      navigate('/reset-password', { replace: true });
    } catch (requestError) {
      setError(requestError.message || 'Unable to update your password right now.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <main className="flex min-h-screen items-center justify-center bg-white px-4 py-10">
      <section className="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-6 shadow-[0_24px_70px_rgba(16,29,48,0.10)] sm:p-8">
        <img src="/payben-logo.svg" alt="PayBen" className="mx-auto mb-7 h-auto w-48" />
        <h1 className="text-center text-2xl font-extrabold text-[#101D30]">Choose a new password</h1>
        <p className="mt-3 text-center text-sm text-slate-600">
          Reset links expire after 30 minutes and can only be used once.
        </p>

        {success ? (
          <div className="mt-6 space-y-4 text-center">
            <p className="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2.5 text-sm text-emerald-900" role="status">
              Your password has been updated. Sign in with your new password.
            </p>
            <Link to="/login" className="inline-flex rounded-xl bg-[#101D30] px-5 py-3 text-sm font-bold text-white hover:bg-[#1b304d]">
              Return to sign in
            </Link>
          </div>
        ) : !token ? (
          <div className="mt-6 space-y-4 text-center">
            <p className="text-sm text-red-700" role="alert">This password reset link is invalid or has expired.</p>
            <Link to="/forgot-password" className="font-semibold text-[#11866c] underline-offset-4 hover:underline">
              Request another link
            </Link>
          </div>
        ) : (
          <form onSubmit={handleSubmit} noValidate className="mt-6 space-y-4">
            <div>
              <label htmlFor="reset-email" className="mb-1.5 block text-sm font-semibold text-[#101D30]">
                Email address
              </label>
              <input
                id="reset-email"
                type="email"
                autoComplete="email"
                required
                value={email}
                onChange={(event) => setEmail(event.target.value)}
                className="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-[#101D30] outline-none focus:border-[#35D6B0] focus:ring-4 focus:ring-[#35D6B0]/15"
              />
            </div>
            <div>
              <label htmlFor="reset-new-password" className="mb-1.5 block text-sm font-semibold text-[#101D30]">
                New password
              </label>
              <div className="relative">
                <Lock className="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true" />
                <input
                  id="reset-new-password"
                  type={showPassword ? 'text' : 'password'}
                  autoComplete="new-password"
                  required
                  minLength={6}
                  value={password}
                  onChange={(event) => setPassword(event.target.value)}
                  placeholder="At least 6 characters"
                  className="w-full rounded-xl border border-slate-300 py-3 pl-10 pr-20 text-sm text-[#101D30] outline-none focus:border-[#35D6B0] focus:ring-4 focus:ring-[#35D6B0]/15"
                />
                <button
                  type="button"
                  onClick={() => setShowPassword((visible) => !visible)}
                  aria-label={showPassword ? 'Hide passwords' : 'Show passwords'}
                  className="absolute right-2 top-1/2 -translate-y-1/2 rounded-md px-2 py-1 text-xs font-semibold text-slate-600 hover:text-[#101D30]"
                >
                  {showPassword ? 'Hide' : 'Show'}
                </button>
              </div>
            </div>
            <div>
              <label htmlFor="reset-confirm-password" className="mb-1.5 block text-sm font-semibold text-[#101D30]">
                Confirm new password
              </label>
              <input
                id="reset-confirm-password"
                type={showPassword ? 'text' : 'password'}
                autoComplete="new-password"
                required
                minLength={6}
                value={confirmPassword}
                onChange={(event) => setConfirmPassword(event.target.value)}
                className="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-[#101D30] outline-none focus:border-[#35D6B0] focus:ring-4 focus:ring-[#35D6B0]/15"
              />
            </div>

            {error && <p className="text-sm text-red-700" role="alert">{error}</p>}

            <button
              type="submit"
              disabled={loading}
              className="flex w-full items-center justify-center gap-2 rounded-xl bg-[#101D30] py-3.5 font-bold text-white transition hover:bg-[#1b304d] disabled:cursor-not-allowed disabled:opacity-60"
            >
              {loading ? <Loader2 className="h-5 w-5 animate-spin" aria-hidden="true" /> : 'Update password'}
            </button>
          </form>
        )}
      </section>
    </main>
  );
};

export default ResetPassword;
