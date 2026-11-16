import React, { useState, useEffect, useRef, useCallback } from 'react';
import './PayBenIntro.css';

const SESSION_STORAGE_KEY = 'payben_intro_seen';

/**
 * PayBenIntro — Premium Motion UI Logo Intro Screen
 *
 * Sequence:
 * 0ms - 400ms: Midnight Navy background (#101D30) & ambient mint glow
 * 400ms - 1300ms: Custom geometric 'P' monogram drawn via SVG stroke path animation
 * 1100ms - 1700ms: Brand name "PayBen" reveals with upward fade
 * 1600ms - 2100ms: Tagline "PAYMENTS MADE SIMPLE." reveals with tracked typography
 * 2100ms - 2500ms: Smooth fade-out transition into the active view (Dashboard/Login)
 */
export const PayBenIntro = ({ onComplete, forceShow = false, duration = 2500 }) => {
  // Check if intro has already run during this browser session
  const [shouldRender, setShouldRender] = useState(() => {
    if (forceShow) return true;
    try {
      const alreadySeen = sessionStorage.getItem(SESSION_STORAGE_KEY);
      return !alreadySeen;
    } catch {
      return true;
    }
  });

  const [isExiting, setIsExiting] = useState(false);
  const exitTimerRef = useRef(null);
  const finishTimerRef = useRef(null);

  const handleFinish = useCallback(() => {
    try {
      sessionStorage.setItem(SESSION_STORAGE_KEY, 'true');
    } catch {
      // ignore storage errors
    }
    setShouldRender(false);
    if (typeof onComplete === 'function') {
      onComplete();
    }
  }, [onComplete]);

  const handleDismiss = useCallback(() => {
    if (isExiting) return;
    setIsExiting(true);
    // Smooth fast exit
    setTimeout(handleFinish, 300);
  }, [isExiting, handleFinish]);

  useEffect(() => {
    if (!shouldRender) return;

    // Check for reduced motion preference
    const prefersReducedMotion =
      typeof window !== 'undefined' &&
      window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (prefersReducedMotion) {
      const quickTimer = setTimeout(handleFinish, 200);
      return () => clearTimeout(quickTimer);
    }

    // Stage 5: Begin exit transition at duration - 450ms
    const exitDelay = Math.max(1200, duration - 450);
    exitTimerRef.current = setTimeout(() => {
      setIsExiting(true);
    }, exitDelay);

    // Final completion and unmount at full duration
    finishTimerRef.current = setTimeout(() => {
      handleFinish();
    }, duration);

    // Global keyboard listener to dismiss on Escape
    const handleKeyDown = (e) => {
      if (e.key === 'Escape' || e.key === ' ') {
        handleDismiss();
      }
    };
    window.addEventListener('keydown', handleKeyDown);

    // Expose manual replay method for development & showcase
    window.__replayPayBenIntro = () => {
      sessionStorage.removeItem(SESSION_STORAGE_KEY);
      window.location.reload();
    };

    return () => {
      if (exitTimerRef.current) clearTimeout(exitTimerRef.current);
      if (finishTimerRef.current) clearTimeout(finishTimerRef.current);
      window.removeEventListener('keydown', handleKeyDown);
    };
  }, [shouldRender, duration, handleFinish, handleDismiss]);

  if (!shouldRender) {
    return null;
  }

  return (
    <aside
      className={`payben-intro-overlay ${isExiting ? 'is-exiting' : ''}`}
      aria-label="PayBen Application Loading"
      role="status"
    >
      {/* Background radial glow */}
      <div className="payben-intro-backdrop" />
      <div className="payben-intro-grid" />

      {/* Skip button for rapid navigation */}
      <button
        type="button"
        onClick={handleDismiss}
        className="payben-intro-skip"
        title="Skip Intro (Esc)"
        aria-label="Skip Intro Animation"
      >
        Skip ✕
      </button>

      {/* Main Animated Stage */}
      <div className="payben-intro-stage">
        {/* Monogram Box with Drawing SVG */}
        <div className="payben-intro-logo-box">
          {/* Initial glowing mint ignition seed */}
          <div className="payben-intro-seed" />

          <svg
            className="payben-intro-svg"
            viewBox="0 0 120 120"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
          >
            {/* Ambient Shadow/Glow Path */}
            <path
              d="M 36 94 L 36 32 C 36 21 45 16 58 16 L 68 16 C 82 16 92 26 92 42 C 92 57 82 66 66 66 L 36 66"
              stroke="#35D6B0"
              strokeWidth="14"
              strokeLinecap="round"
              strokeLinejoin="round"
              opacity="0.2"
              className="payben-path-outer"
            />

            {/* Primary Outer P Stroke (Pure White) */}
            <path
              d="M 36 94 L 36 32 C 36 21 45 16 58 16 L 68 16 C 82 16 92 26 92 42 C 92 57 82 66 66 66 L 36 66"
              stroke="#FFFFFF"
              strokeWidth="10"
              strokeLinecap="round"
              strokeLinejoin="round"
              className="payben-path-outer"
            />

            {/* Interlocking Fluid Mint Flow Ribbon */}
            <path
              d="M 40 76 C 40 54 52 44 72 44 L 92 44"
              stroke="#35D6B0"
              strokeWidth="10"
              strokeLinecap="round"
              strokeLinejoin="round"
              className="payben-path-mint"
            />

            {/* Mint Terminal Settlement Node */}
            <g className="payben-node-pop">
              <circle cx="92" cy="44" r="5" fill="#35D6B0" />
              <circle cx="92" cy="44" r="2.5" fill="#101D30" />
            </g>
          </svg>
        </div>

        {/* Stage 3: Wordmark Typography */}
        <div className="payben-intro-brand">
          <h1 className="payben-intro-title">
            Pay<span>Ben</span>
            <span className="brand-accent">.</span>
          </h1>
        </div>

        {/* Stage 4: Tagline */}
        <div className="payben-intro-tagline">
          <p>PAYMENTS MADE SIMPLE.</p>
        </div>

        {/* Subtle Security / Status Indicator */}
        <div className="payben-intro-status">
          <span className="status-dot" />
          <span>Encrypted Sandbox Ecosystem</span>
        </div>
      </div>
    </aside>
  );
};

export default PayBenIntro;
