import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { useToast } from '../../context/ToastContext';
import { Mail, Lock, Eye, EyeOff, Loader2, ArrowRight } from 'lucide-react';

export const Login = () => {
  const { login } = useAuth();
  const { addToast } = useToast();
  const navigate = useNavigate();

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [errors, setErrors] = useState({});
  const [formError, setFormError] = useState('');

  const handleSubmit = async (event) => {
    event.preventDefault();
    const nextErrors = {};
    const normalizedEmail = email.trim();

    if (!normalizedEmail) {
      nextErrors.email = 'Enter your email address.';
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(normalizedEmail)) {
      nextErrors.email = 'Enter a valid email address.';
    }
    if (!password) nextErrors.password = 'Enter your password.';

    setErrors(nextErrors);
    setFormError('');
    if (Object.keys(nextErrors).length) return;

    setLoading(true);
    try {
      const res = await login(normalizedEmail, password);
      if (res.success) {
        addToast('Welcome back to PayBen!', 'success');
        navigate('/dashboard');
      }
    } catch (error) {
      setFormError(error.message || 'Unable to sign in. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <main className="flex min-h-screen items-center justify-center bg-white px-4 py-10 sm:px-6">
      <section className="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-6 shadow-[0_24px_70px_rgba(16,29,48,0.10)] sm:p-9">
        <header className="mb-7 text-center">
          <img
            src="/payben-logo.svg"
            alt="PayBen — Payments Made Simple"
            className="mx-auto mb-7 h-auto w-52"
          />
          <h1 className="text-3xl font-extrabold tracking-tight text-[#101D30]">Welcome back</h1>
          <p className="mt-2 text-sm text-slate-500">Smart UPI &amp; Instant Bill Payments</p>
          <div className="mx-auto mt-5 h-1 w-12 rounded-full bg-[#35D6B0]" />
        </header>

        <form onSubmit={handleSubmit} noValidate className="mt-0 space-y-4">
          <div>
            <label htmlFor="login-email" className="mb-1.5 block text-sm font-semibold text-[#101D30]">
              Email address
            </label>
            <div className="relative">
              <Mail className="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true" />
              <input
                id="login-email"
                name="email"
                type="email"
                autoComplete="email"
                inputMode="email"
                required
                value={email}
                onChange={(event) => {
                  setEmail(event.target.value);
                  setErrors((current) => ({ ...current, email: '' }));
                  setFormError('');
                }}
                aria-invalid={Boolean(errors.email)}
                aria-describedby={errors.email ? 'login-email-error' : undefined}
                placeholder="you@example.com"
                className="w-full rounded-xl border border-slate-300 bg-white py-3 pl-10 pr-4 text-sm text-[#101D30] outline-none transition placeholder:text-slate-400 focus:border-[#35D6B0] focus:ring-4 focus:ring-[#35D6B0]/15"
              />
            </div>
            {errors.email && <p id="login-email-error" className="mt-1.5 text-xs text-red-700">{errors.email}</p>}
          </div>

          <div>
            <div className="mb-1.5 flex items-center justify-between">
              <label htmlFor="login-password" className="block text-sm font-semibold text-[#101D30]">
                Password
              </label>
              <Link
                to="/forgot-password"
                className="text-xs font-semibold text-[#101D30] underline-offset-4 hover:text-[#11866c] hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#35D6B0]"
              >
                Forgot password?
              </Link>
            </div>
            <div className="relative">
              <Lock className="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true" />
              <input
                id="login-password"
                name="password"
                type={showPassword ? 'text' : 'password'}
                autoComplete="current-password"
                required
                value={password}
                onChange={(event) => {
                  setPassword(event.target.value);
                  setErrors((current) => ({ ...current, password: '' }));
                  setFormError('');
                }}
                aria-invalid={Boolean(errors.password)}
                aria-describedby={errors.password ? 'login-password-error' : undefined}
                placeholder="Enter your password"
                className="w-full rounded-xl border border-slate-300 bg-white py-3 pl-10 pr-12 text-sm text-[#101D30] outline-none transition placeholder:text-slate-400 focus:border-[#35D6B0] focus:ring-4 focus:ring-[#35D6B0]/15"
              />
              <button
                type="button"
                onClick={() => setShowPassword((visible) => !visible)}
                aria-label={showPassword ? 'Hide password' : 'Show password'}
                aria-pressed={showPassword}
                className="absolute right-3 top-1/2 -translate-y-1/2 rounded-md p-1 text-slate-500 transition hover:text-[#101D30] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#35D6B0]"
              >
                {showPassword
                  ? <EyeOff className="h-4 w-4" aria-hidden="true" />
                  : <Eye className="h-4 w-4" aria-hidden="true" />}
              </button>
            </div>
            {errors.password && <p id="login-password-error" className="mt-1.5 text-xs text-red-700">{errors.password}</p>}
          </div>

          {formError && (
            <p className="rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-800" role="alert">
              {formError}
            </p>
          )}

          <button
            type="submit"
            disabled={loading}
            className="flex w-full items-center justify-center gap-2 rounded-xl bg-[#101D30] py-3.5 font-bold text-white shadow-lg shadow-[#101D30]/15 transition hover:bg-[#1b304d] focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-[#35D6B0]/40 disabled:cursor-not-allowed disabled:opacity-60"
          >
            {loading ? (
              <>
                <Loader2 className="h-5 w-5 animate-spin" aria-hidden="true" />
                <span>Signing in…</span>
              </>
            ) : (
              <>
                <span>Sign In</span>
                <ArrowRight className="h-4 w-4" aria-hidden="true" />
              </>
            )}
          </button>
        </form>

        <footer className="mt-7 border-t border-slate-100 pt-5 text-center">
          <p className="text-sm text-slate-500">
            Don&apos;t have an account?{' '}
            <Link to="/register" className="font-bold text-[#11866c] underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#35D6B0]">
              Register Now
            </Link>
          </p>
        </footer>
      </section>
    </main>
  );
};

export default Login;
