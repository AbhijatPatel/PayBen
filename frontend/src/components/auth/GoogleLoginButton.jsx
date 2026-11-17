import React, { useEffect, useRef, useState } from 'react';
import { Loader2 } from 'lucide-react';

let googleScriptPromise;

const loadGoogleIdentityServices = () => {
  if (window.google?.accounts?.id) return Promise.resolve();
  if (googleScriptPromise) return googleScriptPromise;

  googleScriptPromise = new Promise((resolve, reject) => {
    const script = document.createElement('script');
    script.src = 'https://accounts.google.com/gsi/client';
    script.async = true;
    script.defer = true;
    script.onload = () => {
      if (window.google?.accounts?.id) {
        resolve();
      } else {
        reject(new Error('Google sign-in could not be initialized. Please try again.'));
      }
    };
    script.onerror = () => reject(new Error('Unable to load Google sign-in. Check your connection and try again.'));
    document.head.appendChild(script);
  });
  googleScriptPromise = googleScriptPromise.catch((error) => {
    googleScriptPromise = undefined;
    throw error;
  });

  return googleScriptPromise;
};

export const GoogleLoginButton = ({ onSuccess, onError, text = 'Continue with Google', className = '' }) => {
  const buttonRef = useRef(null);
  const callbacksRef = useRef({ onSuccess, onError });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const googleClientId = import.meta.env.VITE_GOOGLE_CLIENT_ID;

  useEffect(() => {
    callbacksRef.current = { onSuccess, onError };
  }, [onSuccess, onError]);

  useEffect(() => {
    if (!googleClientId || !buttonRef.current) return undefined;
    let active = true;

    loadGoogleIdentityServices()
      .then(() => {
        if (!active || !buttonRef.current) return;

        window.google.accounts.id.initialize({
          client_id: googleClientId,
          callback: async ({ credential }) => {
            if (!credential) {
              const authError = new Error('Google did not return an identity token. Please try again.');
              setError(authError.message);
              callbacksRef.current.onError?.(authError);
              return;
            }

            setLoading(true);
            setError('');
            try {
              await callbacksRef.current.onSuccess({ credential });
            } catch (authError) {
              setError(authError.message || 'Google sign-in could not be completed.');
              callbacksRef.current.onError?.(authError);
            } finally {
              setLoading(false);
            }
          },
          auto_select: false
        });

        window.google.accounts.id.renderButton(buttonRef.current, {
          type: 'standard',
          theme: 'outline',
          size: 'large',
          text: text.toLowerCase().includes('sign up') ? 'signup_with' : 'continue_with',
          shape: 'rectangular',
          logo_alignment: 'left',
          width: Math.min(buttonRef.current.clientWidth, 400)
        });
      })
      .catch((authError) => {
        if (!active) return;
        setError(authError.message);
        callbacksRef.current.onError?.(authError);
      });

    return () => {
      active = false;
    };
  }, [googleClientId, text]);

  const handleUnconfiguredClick = () => {
    const message = import.meta.env.DEV
      ? 'Google sign-in is not configured. Set VITE_GOOGLE_CLIENT_ID in the frontend and GOOGLE_CLIENT_ID in the backend.'
      : 'Google sign-in is unavailable. Please sign in with your email and password.';
    setError(message);
    onError?.(new Error(message));
  };

  return (
    <div className={className}>
      {googleClientId ? (
        <div className="relative min-h-11">
          <div
            ref={buttonRef}
            className={loading ? 'pointer-events-none opacity-60' : ''}
            aria-label={text}
            aria-busy={loading}
          />
          {loading && (
            <div className="absolute inset-0 flex items-center justify-center bg-white/70" aria-hidden="true">
              <Loader2 className="h-5 w-5 animate-spin text-[#101D30]" />
            </div>
          )}
        </div>
      ) : (
        <button
          type="button"
          onClick={handleUnconfiguredClick}
          className="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-[#101D30] transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#35D6B0] focus-visible:ring-offset-2"
        >
          {text}
        </button>
      )}
      {error && (
        <p className="mt-2 text-sm text-red-700" role="alert">
          {error}
        </p>
      )}
      {googleClientId && (
        <p className="sr-only" aria-live="polite">
          {loading ? 'Connecting to Google.' : ''}
        </p>
      )}
    </div>
  );
};
