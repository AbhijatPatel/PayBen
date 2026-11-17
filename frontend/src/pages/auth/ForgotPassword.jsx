import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { Loader2, Mail } from 'lucide-react';
import { authService } from '../../services/authService';

export const ForgotPassword = () => {
  const [email, setEmail] = useState('');
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');
  const [developmentResetUrl, setDevelopmentResetUrl] = useState('');

  const handleSubmit = async (event) => {
    event.preventDefault();
    const normalizedEmail = email.trim();
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(normalizedEmail)) {
      setError('Enter a valid email address.');
      setMessage('');
      return;
    }

    setLoading(true);
    setError('');
    setMessage('');
    setDevelopmentResetUrl('');
    try {
      const response = await authService.requestPasswordReset(normalizedEmail);
      setMessage(response.message);
      setDevelopmentResetUrl(response.data?.development_reset_url || '');
    } catch (requestError) {
      setError(requestError.message || 'Unable to request a password reset right now.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <main className="flex min-h-screen items-center justify-center bg-white px-4 py-10">
      <section className="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-6 shadow-[0_24px_70px_rgba(16,29,48,0.10)] sm:p-8">
        <img src="/payben-logo.svg" alt="PayBen" className="mx-auto mb-7 h-auto w-48" />
        <h1 className="text-center text-2xl font-extrabold text-[#101D30]">Reset your password</h1>
        <p className="mt-3 text-center text-sm leading-6 text-slate-600">
          Enter your account email. If an active account matches, we&apos;ll provide password reset instructions.
        </p>

        <form onSubmit={handleSubmit} noValidate className="mt-6 space-y-4">
          <div>
            <label htmlFor="reset-request-email" className="mb-1.5 block text-sm font-semibold text-[#101D30]">
              Email address
            </label>
            <div className="relative">
              <Mail className="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true" />
              <input
                id="reset-request-email"
                type="email"
                autoComplete="email"
                required
                value={email}
                onChange={(event) => {
                  setEmail(event.target.value);
                  setError('');
                  setMessage('');
                  setDevelopmentResetUrl('');
                }}
                aria-invalid={Boolean(error)}
                placeholder="you@example.com"
                className="w-full rounded-xl border border-slate-300 py-3 pl-10 pr-4 text-sm text-[#101D30] outline-none transition placeholder:text-slate-400 focus:border-[#35D6B0] focus:ring-4 focus:ring-[#35D6B0]/15"
              />
            </div>
          </div>

          {error && <p className="text-sm text-red-700" role="alert">{error}</p>}
          {message && <p className="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2.5 text-sm text-emerald-900" role="status">{message}</p>}
          {developmentResetUrl && (
            <a
              href={developmentResetUrl}
              className="block rounded-lg border border-amber-300 bg-amber-50 px-3 py-2.5 text-sm font-semibold text-amber-950 underline underline-offset-2"
            >
              Open development-only reset link
            </a>
          )}

          <button
            type="submit"
            disabled={loading}
            className="flex w-full items-center justify-center gap-2 rounded-xl bg-[#101D30] py-3.5 font-bold text-white transition hover:bg-[#1b304d] focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-[#35D6B0]/40 disabled:cursor-not-allowed disabled:opacity-60"
          >
            {loading ? <Loader2 className="h-5 w-5 animate-spin" aria-hidden="true" /> : 'Send reset instructions'}
          </button>
        </form>

        <p className="mt-6 text-center text-sm">
          <Link to="/login" className="font-semibold text-[#11866c] underline-offset-4 hover:underline">
            Return to sign in
          </Link>
        </p>
      </section>
    </main>
  );
};

export default ForgotPassword;
