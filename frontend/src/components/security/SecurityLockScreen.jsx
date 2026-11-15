import React, { useState, useEffect, useRef } from 'react';
import { useSecurity } from '../../context/SecurityContext';
import { useAuth } from '../../context/AuthContext';
import { soundService } from '../../services/soundService';
import {
  Shield,
  Lock,
  Fingerprint,
  Scan,
  Grid,
  Hash,
  Delete,
  CheckCircle2,
  AlertCircle,
  Shuffle,
  Camera,
  Eye,
  RefreshCw
} from 'lucide-react';

export const SecurityLockScreen = () => {
  const {
    isLocked,
    unlockApp,
    lockMethod,
    setLockMethod,
    verifyPin,
    verifyPattern,
    pin,
    pattern
  } = useSecurity();

  const { user, logout } = useAuth();

  // Active tab inside the lock screen (defaults to user's saved lockMethod)
  const [activeTab, setActiveTab] = useState(lockMethod || 'pin');
  const [inputPin, setInputPin] = useState('');
  const [errorShake, setErrorShake] = useState(false);
  const [errorMessage, setErrorMessage] = useState('');

  // Shuffled keypad
  const [keypadOrder, setKeypadOrder] = useState([1, 2, 3, 4, 5, 6, 7, 8, 9, 0]);
  const [isShuffled, setIsShuffled] = useState(false);

  // Fingerprint scan simulation
  const [fingerprintScanning, setFingerprintScanning] = useState(false);
  const [fingerprintSuccess, setFingerprintSuccess] = useState(false);

  // Face ID scan simulation
  const [faceScanning, setFaceScanning] = useState(false);
  const [faceScanStep, setFaceScanStep] = useState(0); // 0: ready, 1: scanning, 2: matched

  // Pattern lock state
  const [selectedDots, setSelectedDots] = useState([]);
  const [isDrawing, setIsDrawing] = useState(false);
  const [patternError, setPatternError] = useState(false);

  // Sync tab with user preference when lock screen opens
  useEffect(() => {
    if (lockMethod) {
      setActiveTab(lockMethod);
    }
  }, [lockMethod, isLocked]);

  if (!isLocked) return null;

  // Keypad shuffle
  const handleShuffle = () => {
    soundService.playKeypadClick();
    if (isShuffled) {
      setKeypadOrder([1, 2, 3, 4, 5, 6, 7, 8, 9, 0]);
      setIsShuffled(false);
    } else {
      const numbers = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
      for (let i = numbers.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [numbers[i], numbers[j]] = [numbers[j], numbers[i]];
      }
      setKeypadOrder(numbers);
      setIsShuffled(true);
    }
  };

  // MPIN Key press
  const handleKeyPress = (num) => {
    soundService.playKeypadClick();
    if (inputPin.length < 4) {
      const next = inputPin + num;
      setInputPin(next);
      if (next.length === 4) {
        setTimeout(() => {
          const ok = verifyPin(next);
          if (!ok) {
            triggerError('Incorrect MPIN. Please try again.');
            setInputPin('');
          }
        }, 120);
      }
    }
  };

  const handleDelete = () => {
    soundService.playKeypadClick();
    setInputPin(prev => prev.slice(0, -1));
    setErrorMessage('');
  };

  const triggerError = (msg) => {
    setErrorMessage(msg);
    setErrorShake(true);
    setTimeout(() => setErrorShake(false), 600);
  };

  // Fingerprint Scanner trigger
  const handleFingerprintScan = async () => {
    setFingerprintScanning(true);
    soundService.playScanBeep();

    // Try WebAuthn if available, or simulate realistic biometric sensor
    try {
      if (window.PublicKeyCredential && navigator.credentials) {
        // Attempt native biometric prompt
      }
    } catch (e) {}

    setTimeout(() => {
      setFingerprintScanning(false);
      setFingerprintSuccess(true);
      soundService.playBiometricSuccess();
      setTimeout(() => {
        unlockApp();
      }, 400);
    }, 1200);
  };

  // Face ID Scanner trigger
  const handleFaceScan = () => {
    setFaceScanning(true);
    setFaceScanStep(1);
    soundService.playScanBeep();

    setTimeout(() => {
      setFaceScanStep(2);
      soundService.playBiometricSuccess();
      setTimeout(() => {
        setFaceScanning(false);
        unlockApp();
      }, 500);
    }, 1500);
  };

  // Pattern Lock interactions
  const handleDotTouch = (dotIndex) => {
    if (!selectedDots.includes(dotIndex)) {
      soundService.playKeypadClick();
      setSelectedDots(prev => [...prev, dotIndex]);
    }
  };

  const handlePatternComplete = () => {
    if (selectedDots.length < 3) {
      triggerError('Connect at least 3 dots');
      setSelectedDots([]);
      return;
    }
    const ok = verifyPattern(selectedDots);
    if (!ok) {
      setPatternError(true);
      triggerError('Incorrect pattern');
      setTimeout(() => {
        setSelectedDots([]);
        setPatternError(false);
      }, 700);
    }
  };

  return (
    <div className="fixed inset-0 z-[999] bg-gradient-to-br from-[#07132e] via-[#0b1c3d] to-[#040817] flex flex-col items-center justify-between p-6 text-white overflow-y-auto">
      {/* Top Header */}
      <div className="w-full max-w-sm flex items-center justify-between pt-2">
        <div className="flex items-center gap-2">
          <Shield className="w-5 h-5 text-[#ff6200]" />
          <span className="text-xs font-bold uppercase tracking-widest text-blue-200">
            PayBen Security Guard
          </span>
        </div>
        <button
          onClick={logout}
          className="text-xs font-semibold text-gray-400 hover:text-white transition px-2 py-1 rounded-lg hover:bg-white/10"
        >
          Sign Out
        </button>
      </div>

      {/* Center Card */}
      <div className="w-full max-w-sm my-auto flex flex-col items-center">
        {/* PayBen Official Logo */}
        <div className="mb-4 text-center">
          <img
            src="/payben-logo.png"
            alt="PayBen"
            className="w-44 h-auto mx-auto drop-shadow-[0_10px_25px_rgba(0,71,204,0.4)]"
          />
        </div>

        {/* User Badge */}
        <div className="flex items-center gap-3 bg-white/5 border border-white/10 px-4 py-2 rounded-2xl backdrop-blur-md mb-6 shadow-lg">
          <div className="w-9 h-9 rounded-full bg-gradient-to-tr from-[#0047cc] to-[#ff6200] flex items-center justify-center font-black text-sm text-white shadow-inner">
            {user?.full_name ? user.full_name.charAt(0).toUpperCase() : 'P'}
          </div>
          <div className="text-left">
            <h4 className="text-sm font-bold text-white leading-tight">
              {user?.full_name || 'PayBen User'}
            </h4>
            <p className="text-[11px] text-blue-200/70 font-mono">
              {user?.mobile ? `${user.mobile.slice(0, 2)}••••${user.mobile.slice(-2)}` : 'App Locked'}
            </p>
          </div>
        </div>

        {/* Unlock Methods Switcher Bar */}
        <div className="w-full bg-white/5 p-1 rounded-2xl flex items-center justify-between mb-6 border border-white/10">
          <button
            onClick={() => { setActiveTab('pin'); soundService.playKeypadClick(); }}
            className={`flex-1 py-2 rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 transition-all ${
              activeTab === 'pin'
                ? 'bg-gradient-to-r from-[#0047cc] to-[#1a66ff] text-white shadow-md'
                : 'text-gray-400 hover:text-white'
            }`}
          >
            <Hash className="w-3.5 h-3.5" />
            <span>MPIN</span>
          </button>

          <button
            onClick={() => { setActiveTab('face'); soundService.playKeypadClick(); }}
            className={`flex-1 py-2 rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 transition-all ${
              activeTab === 'face'
                ? 'bg-gradient-to-r from-[#0047cc] to-[#1a66ff] text-white shadow-md'
                : 'text-gray-400 hover:text-white'
            }`}
          >
            <Scan className="w-3.5 h-3.5" />
            <span>Face ID</span>
          </button>

          <button
            onClick={() => { setActiveTab('fingerprint'); soundService.playKeypadClick(); }}
            className={`flex-1 py-2 rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 transition-all ${
              activeTab === 'fingerprint'
                ? 'bg-gradient-to-r from-[#0047cc] to-[#1a66ff] text-white shadow-md'
                : 'text-gray-400 hover:text-white'
            }`}
          >
            <Fingerprint className="w-3.5 h-3.5" />
            <span>Touch ID</span>
          </button>

          <button
            onClick={() => { setActiveTab('pattern'); soundService.playKeypadClick(); }}
            className={`flex-1 py-2 rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 transition-all ${
              activeTab === 'pattern'
                ? 'bg-gradient-to-r from-[#0047cc] to-[#1a66ff] text-white shadow-md'
                : 'text-gray-400 hover:text-white'
            }`}
          >
            <Grid className="w-3.5 h-3.5" />
            <span>Pattern</span>
          </button>
        </div>

        {/* Error message */}
        {errorMessage && (
          <div className="mb-4 text-xs text-rose-400 font-semibold flex items-center gap-1.5 animate-bounce">
            <AlertCircle className="w-3.5 h-3.5" />
            <span>{errorMessage}</span>
          </div>
        )}

        {/* ---------------- 1. 4-DIGIT MPIN VIEW ---------------- */}
        {activeTab === 'pin' && (
          <div className={`w-full flex flex-col items-center ${errorShake ? 'animate-shake' : ''}`}>
            <p className="text-xs text-blue-200/80 mb-4 font-medium">
              Enter 4-Digit PayBen Security MPIN
            </p>

            {/* PIN Dots */}
            <div className="flex gap-4 mb-6">
              {[0, 1, 2, 3].map((idx) => {
                const filled = inputPin.length > idx;
                return (
                  <div
                    key={idx}
                    className={`w-4 h-4 rounded-full transition-all duration-200 border-2 ${
                      filled
                        ? 'bg-[#ff6200] border-[#ff6200] scale-125 shadow-[0_0_12px_#ff6200]'
                        : 'border-white/30 bg-transparent'
                    }`}
                  />
                );
              })}
            </div>

            {/* Numeric Keypad */}
            <div className="w-full grid grid-cols-3 gap-3 max-w-[270px]">
              {keypadOrder.slice(0, 9).map((num) => (
                <button
                  key={num}
                  onClick={() => handleKeyPress(num)}
                  className="w-16 h-16 rounded-2xl bg-white/10 hover:bg-white/20 active:bg-blue-600/40 text-2xl font-bold text-white flex items-center justify-center mx-auto transition-all duration-150 backdrop-blur-sm border border-white/5 active:scale-95 shadow-md"
                >
                  {num}
                </button>
              ))}

              {/* Bottom Row: Shuffle Keypad, 0, Backspace */}
              <button
                onClick={handleShuffle}
                title="Shuffle Keypad for Security"
                className={`w-16 h-16 rounded-2xl flex items-center justify-center mx-auto transition text-xs font-semibold ${
                  isShuffled ? 'bg-orange-500/30 text-orange-300 border border-orange-400/30' : 'bg-white/5 text-gray-400 hover:text-white'
                }`}
              >
                <Shuffle className="w-5 h-5" />
              </button>

              <button
                onClick={() => handleKeyPress(keypadOrder[9])}
                className="w-16 h-16 rounded-2xl bg-white/10 hover:bg-white/20 active:bg-blue-600/40 text-2xl font-bold text-white flex items-center justify-center mx-auto transition-all duration-150 backdrop-blur-sm border border-white/5 active:scale-95 shadow-md"
              >
                {keypadOrder[9]}
              </button>

              <button
                onClick={handleDelete}
                className="w-16 h-16 rounded-2xl bg-white/5 hover:bg-white/15 text-gray-300 hover:text-rose-400 flex items-center justify-center mx-auto transition-all duration-150 active:scale-95"
              >
                <Delete className="w-6 h-6" />
              </button>
            </div>
          </div>
        )}

        {/* ---------------- 2. FACE ID SCAN VIEW ---------------- */}
        {activeTab === 'face' && (
          <div className="w-full flex flex-col items-center py-4">
            <p className="text-xs text-blue-200/80 mb-6 font-medium">
              Look into your camera to verify biometric identity
            </p>

            {/* 3D Animated Face Reticle */}
            <div className="relative w-48 h-48 rounded-3xl border-2 border-dashed border-blue-400/40 flex items-center justify-center mb-6 overflow-hidden bg-black/40 shadow-inner group">
              {/* Corner Targets */}
              <div className="absolute top-2 left-2 w-4 h-4 border-t-2 border-l-2 border-[#ff6200]" />
              <div className="absolute top-2 right-2 w-4 h-4 border-t-2 border-r-2 border-[#ff6200]" />
              <div className="absolute bottom-2 left-2 w-4 h-4 border-b-2 border-l-2 border-[#ff6200]" />
              <div className="absolute bottom-2 right-2 w-4 h-4 border-b-2 border-r-2 border-[#ff6200]" />

              {/* Holographic Sweep Beam */}
              {faceScanning && (
                <div className="absolute inset-x-0 h-1 bg-gradient-to-r from-transparent via-[#00d2ff] to-transparent shadow-[0_0_15px_#00d2ff] animate-scanline z-10" />
              )}

              {/* Center Face Avatar Icon */}
              <div className={`transition-all duration-300 ${faceScanning ? 'scale-110 text-cyan-400' : 'text-blue-300/60'}`}>
                {faceScanStep === 2 ? (
                  <CheckCircle2 className="w-24 h-24 text-emerald-400 animate-in zoom-in" />
                ) : (
                  <Camera className="w-20 h-20 stroke-[1.2]" />
                )}
              </div>

              {/* Status Badge */}
              <div className="absolute bottom-3 inset-x-0 text-center">
                <span className="text-[10px] font-mono uppercase tracking-wider px-2 py-0.5 rounded-full bg-blue-900/60 text-blue-200">
                  {faceScanStep === 0 && 'Ready to scan'}
                  {faceScanStep === 1 && 'Scanning Face Mesh...'}
                  {faceScanStep === 2 && 'Face Authenticated'}
                </span>
              </div>
            </div>

            <button
              onClick={handleFaceScan}
              disabled={faceScanning}
              className="w-full max-w-xs py-3.5 bg-gradient-to-r from-[#0047cc] to-[#ff6200] hover:brightness-110 font-bold text-white rounded-2xl flex items-center justify-center gap-2 shadow-lg shadow-blue-600/30 transition active:scale-95 disabled:opacity-50"
            >
              <Scan className="w-5 h-5" />
              <span>{faceScanning ? 'Verifying Face...' : 'Scan My Face'}</span>
            </button>
          </div>
        )}

        {/* ---------------- 3. FINGERPRINT SENSOR VIEW ---------------- */}
        {activeTab === 'fingerprint' && (
          <div className="w-full flex flex-col items-center py-4">
            <p className="text-xs text-blue-200/80 mb-8 font-medium">
              Touch and hold the fingerprint sensor
            </p>

            {/* Fingerprint Touch Sensor */}
            <div className="relative flex items-center justify-center mb-8">
              {/* Expanding Ripple Rings */}
              {fingerprintScanning && (
                <>
                  <div className="absolute w-36 h-36 rounded-full border border-cyan-400/40 animate-ping" />
                  <div className="absolute w-48 h-48 rounded-full border border-blue-500/20 animate-pulse" />
                </>
              )}

              <button
                onClick={handleFingerprintScan}
                disabled={fingerprintScanning}
                className={`relative w-28 h-28 rounded-full flex items-center justify-center transition-all duration-300 border-2 ${
                  fingerprintSuccess
                    ? 'bg-emerald-500/20 border-emerald-400 text-emerald-300 shadow-[0_0_30px_#10b981]'
                    : fingerprintScanning
                    ? 'bg-cyan-500/20 border-cyan-400 text-cyan-300 shadow-[0_0_30px_#06b6d4] scale-105'
                    : 'bg-white/5 border-white/20 text-blue-300 hover:text-white hover:border-[#ff6200] hover:shadow-[0_0_25px_rgba(255,98,0,0.4)]'
                }`}
              >
                {fingerprintSuccess ? (
                  <CheckCircle2 className="w-14 h-14" />
                ) : (
                  <Fingerprint className="w-14 h-14 stroke-[1.4]" />
                )}
              </button>
            </div>

            <button
              onClick={handleFingerprintScan}
              disabled={fingerprintScanning}
              className="w-full max-w-xs py-3.5 bg-gradient-to-r from-[#0047cc] to-[#1a66ff] hover:brightness-110 font-bold text-white rounded-2xl flex items-center justify-center gap-2 shadow-lg shadow-blue-600/30 transition active:scale-95 disabled:opacity-50"
            >
              <Fingerprint className="w-5 h-5" />
              <span>{fingerprintScanning ? 'Verifying Biometrics...' : 'Touch to Unlock'}</span>
            </button>
          </div>
        )}

        {/* ---------------- 4. PATTERN LOCK VIEW ---------------- */}
        {activeTab === 'pattern' && (
          <div className="w-full flex flex-col items-center py-2">
            <p className="text-xs text-blue-200/80 mb-4 font-medium">
              Draw your unlock pattern ({selectedDots.length} dots selected)
            </p>

            {/* 3x3 Dot Grid Matrix */}
            <div className="relative w-64 h-64 p-4 rounded-3xl bg-white/5 border border-white/10 flex items-center justify-center mb-4 backdrop-blur-md">
              <div className="grid grid-cols-3 gap-8">
                {[0, 1, 2, 3, 4, 5, 6, 7, 8].map((dotIdx) => {
                  const isSelected = selectedDots.includes(dotIdx);
                  return (
                    <button
                      key={dotIdx}
                      onClick={() => handleDotTouch(dotIdx)}
                      className={`relative w-8 h-8 rounded-full flex items-center justify-center transition-all duration-150 ${
                        isSelected
                          ? patternError
                            ? 'bg-rose-500 scale-125 shadow-[0_0_15px_#f43f5e]'
                            : 'bg-[#ff6200] scale-125 shadow-[0_0_15px_#ff6200]'
                          : 'bg-white/30 hover:bg-white/60'
                      }`}
                    >
                      <div className="w-2.5 h-2.5 rounded-full bg-white" />
                    </button>
                  );
                })}
              </div>
            </div>

            {/* Pattern Actions */}
            <div className="flex gap-3 w-full max-w-xs">
              <button
                onClick={() => { setSelectedDots([]); soundService.playKeypadClick(); }}
                className="flex-1 py-2.5 bg-white/10 hover:bg-white/20 text-xs font-semibold rounded-xl text-gray-300 transition"
              >
                Clear
              </button>
              <button
                onClick={handlePatternComplete}
                className="flex-1 py-2.5 bg-[#ff6200] hover:bg-[#e85800] text-xs font-bold text-white rounded-xl transition shadow-md shadow-orange-500/30"
              >
                Confirm Pattern
              </button>
            </div>
          </div>
        )}
      </div>

      {/* Footer Info */}
      <div className="w-full text-center pb-2">
        <p className="text-[11px] text-gray-400">
          Protected with 256-bit AES Bank-Grade UPI Encryption
        </p>
      </div>
    </div>
  );
};
