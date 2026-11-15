import React, { useState } from 'react';
import { useSecurity } from '../../context/SecurityContext';
import { useToast } from '../../context/ToastContext';
import { soundService } from '../../services/soundService';
import {
  Shield,
  Lock,
  Fingerprint,
  Scan,
  Grid,
  Hash,
  Eye,
  EyeOff,
  Volume2,
  CheckCircle2,
  Smartphone,
  Key,
  Laptop,
  LogOut,
  ChevronRight,
  Sparkles
} from 'lucide-react';

export const SecuritySettings = () => {
  const {
    appLockEnabled,
    setAppLockEnabled,
    lockMethod,
    setLockMethod,
    pin,
    setPin,
    pattern,
    setPattern,
    hideBalance,
    toggleHideBalance,
    soundBoxEnabled,
    toggleSoundBox,
    privacyMaskEnabled,
    setPrivacyMaskEnabled,
    twoFactorEnabled,
    setTwoFactorEnabled
  } = useSecurity();

  const { addToast } = useToast();

  // Modals for updating PIN / Pattern
  const [showPinModal, setShowPinModal] = useState(false);
  const [newPin, setNewPin] = useState('');
  const [confirmPin, setConfirmPin] = useState('');

  const [showPatternModal, setShowPatternModal] = useState(false);
  const [drawnDots, setDrawnDots] = useState([]);

  // Handle PIN Update
  const handleSavePin = (e) => {
    e.preventDefault();
    if (newPin.length !== 4) {
      addToast('PIN must be exactly 4 digits', 'error');
      return;
    }
    if (newPin !== confirmPin) {
      addToast('PIN confirmation does not match', 'error');
      return;
    }
    setPin(newPin);
    soundService.playBiometricSuccess();
    addToast('Security MPIN updated successfully!', 'success');
    setShowPinModal(false);
    setNewPin('');
    setConfirmPin('');
  };

  // Handle Pattern Update
  const handleSavePattern = () => {
    if (drawnDots.length < 3) {
      addToast('Pattern must connect at least 3 dots', 'error');
      return;
    }
    setPattern(drawnDots);
    soundService.playBiometricSuccess();
    addToast('Unlock pattern updated successfully!', 'success');
    setShowPatternModal(false);
    setDrawnDots([]);
  };

  // Test SoundBox Voice
  const handleTestSoundBox = () => {
    soundService.speakSoundBox('PayBen Smart Voice SoundBox: Payment of rupees 500 received successfully!');
    addToast('Testing PayBen SoundBox announcement...', 'info');
  };

  return (
    <div className="max-w-4xl mx-auto space-y-6 animate-in fade-in pb-12">
      {/* Top Banner with Brand Logo Theme */}
      <div className="bg-gradient-to-r from-[#0047cc] to-[#ff6200] rounded-3xl p-6 text-white shadow-xl flex items-center justify-between relative overflow-hidden">
        <div className="relative z-10 space-y-1">
          <div className="flex items-center gap-2">
            <Shield className="w-6 h-6 text-yellow-300" />
            <h2 className="text-2xl font-black tracking-tight">Security & Privacy Center</h2>
          </div>
          <p className="text-xs text-blue-100 font-medium">
            Manage biometric locks, SoundBox voice alerts, MPIN, and privacy controls
          </p>
        </div>
        <div className="hidden sm:block opacity-20">
          <Shield className="w-32 h-32" />
        </div>
      </div>

      {/* ---------------- 1. APP LOCK & BIOMETRICS ---------------- */}
      <div className="bg-white rounded-3xl border border-gray-200/80 p-6 shadow-sm space-y-6">
        <div className="flex items-center justify-between pb-4 border-b border-gray-100">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-2xl bg-blue-50 text-[#0047cc] flex items-center justify-center font-bold">
              <Lock className="w-5 h-5" />
            </div>
            <div>
              <h3 className="text-base font-bold text-gray-900">App Lock Security</h3>
              <p className="text-xs text-gray-500">Require authentication every time PayBen is opened</p>
            </div>
          </div>
          <label className="relative inline-flex items-center cursor-pointer">
            <input
              type="checkbox"
              checked={appLockEnabled}
              onChange={(e) => {
                setAppLockEnabled(e.target.checked);
                addToast(`App Lock ${e.target.checked ? 'Enabled' : 'Disabled'}`, 'info');
              }}
              className="sr-only peer"
            />
            <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#0047cc]"></div>
          </label>
        </div>

        {appLockEnabled && (
          <div className="space-y-4">
            <label className="block text-xs font-bold text-gray-700 uppercase tracking-wider">
              Choose Preferred Unlock Method
            </label>

            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
              {/* Method 1: MPIN */}
              <div
                onClick={() => {
                  setLockMethod('pin');
                  soundService.playKeypadClick();
                }}
                className={`p-4 rounded-2xl border-2 cursor-pointer transition flex flex-col justify-between ${
                  lockMethod === 'pin'
                    ? 'border-[#0047cc] bg-blue-50/50 shadow-sm'
                    : 'border-gray-200 hover:border-gray-300 bg-white'
                }`}
              >
                <div className="flex justify-between items-start mb-3">
                  <div className="p-2 rounded-xl bg-blue-100 text-[#0047cc]">
                    <Hash className="w-5 h-5" />
                  </div>
                  {lockMethod === 'pin' && (
                    <CheckCircle2 className="w-5 h-5 text-[#0047cc]" />
                  )}
                </div>
                <div>
                  <h4 className="font-bold text-sm text-gray-900">4-Digit MPIN</h4>
                  <p className="text-[11px] text-gray-500">Numeric PIN keypad</p>
                </div>
              </div>

              {/* Method 2: Face ID */}
              <div
                onClick={() => {
                  setLockMethod('face');
                  soundService.playKeypadClick();
                }}
                className={`p-4 rounded-2xl border-2 cursor-pointer transition flex flex-col justify-between ${
                  lockMethod === 'face'
                    ? 'border-[#0047cc] bg-blue-50/50 shadow-sm'
                    : 'border-gray-200 hover:border-gray-300 bg-white'
                }`}
              >
                <div className="flex justify-between items-start mb-3">
                  <div className="p-2 rounded-xl bg-cyan-100 text-cyan-700">
                    <Scan className="w-5 h-5" />
                  </div>
                  {lockMethod === 'face' && (
                    <CheckCircle2 className="w-5 h-5 text-[#0047cc]" />
                  )}
                </div>
                <div>
                  <h4 className="font-bold text-sm text-gray-900">Face ID</h4>
                  <p className="text-[11px] text-gray-500">3D Camera Facial Scan</p>
                </div>
              </div>

              {/* Method 3: Touch ID */}
              <div
                onClick={() => {
                  setLockMethod('fingerprint');
                  soundService.playKeypadClick();
                }}
                className={`p-4 rounded-2xl border-2 cursor-pointer transition flex flex-col justify-between ${
                  lockMethod === 'fingerprint'
                    ? 'border-[#0047cc] bg-blue-50/50 shadow-sm'
                    : 'border-gray-200 hover:border-gray-300 bg-white'
                }`}
              >
                <div className="flex justify-between items-start mb-3">
                  <div className="p-2 rounded-xl bg-indigo-100 text-indigo-700">
                    <Fingerprint className="w-5 h-5" />
                  </div>
                  {lockMethod === 'fingerprint' && (
                    <CheckCircle2 className="w-5 h-5 text-[#0047cc]" />
                  )}
                </div>
                <div>
                  <h4 className="font-bold text-sm text-gray-900">Fingerprint</h4>
                  <p className="text-[11px] text-gray-500">Biometric Sensor</p>
                </div>
              </div>

              {/* Method 4: Pattern */}
              <div
                onClick={() => {
                  setLockMethod('pattern');
                  soundService.playKeypadClick();
                }}
                className={`p-4 rounded-2xl border-2 cursor-pointer transition flex flex-col justify-between ${
                  lockMethod === 'pattern'
                    ? 'border-[#0047cc] bg-blue-50/50 shadow-sm'
                    : 'border-gray-200 hover:border-gray-300 bg-white'
                }`}
              >
                <div className="flex justify-between items-start mb-3">
                  <div className="p-2 rounded-xl bg-amber-100 text-amber-700">
                    <Grid className="w-5 h-5" />
                  </div>
                  {lockMethod === 'pattern' && (
                    <CheckCircle2 className="w-5 h-5 text-[#0047cc]" />
                  )}
                </div>
                <div>
                  <h4 className="font-bold text-sm text-gray-900">Pattern Lock</h4>
                  <p className="text-[11px] text-gray-500">3x3 Connected Dots</p>
                </div>
              </div>
            </div>

            {/* Quick configuration buttons for MPIN and Pattern */}
            <div className="flex flex-wrap gap-3 pt-2">
              <button
                onClick={() => setShowPinModal(true)}
                className="px-4 py-2.5 rounded-xl bg-gray-100 hover:bg-gray-200 text-xs font-bold text-gray-800 transition flex items-center gap-2"
              >
                <Key className="w-4 h-4 text-[#0047cc]" />
                <span>Change 4-Digit MPIN</span>
              </button>

              <button
                onClick={() => setShowPatternModal(true)}
                className="px-4 py-2.5 rounded-xl bg-gray-100 hover:bg-gray-200 text-xs font-bold text-gray-800 transition flex items-center gap-2"
              >
                <Grid className="w-4 h-4 text-[#ff6200]" />
                <span>Change Pattern Lock</span>
              </button>
            </div>
          </div>
        )}
      </div>

      {/* ---------------- 2. PRIVACY & DISPLAY SETTINGS ---------------- */}
      <div className="bg-white rounded-3xl border border-gray-200/80 p-6 shadow-sm space-y-4">
        <h3 className="text-base font-bold text-gray-900 mb-2">Privacy & Display</h3>

        {/* Hide Balance Toggle */}
        <div className="flex items-center justify-between py-2 border-b border-gray-100">
          <div className="flex items-center gap-3">
            <div className="p-2.5 rounded-2xl bg-orange-50 text-[#ff6200]">
              {hideBalance ? <EyeOff className="w-5 h-5" /> : <Eye className="w-5 h-5" />}
            </div>
            <div>
              <h4 className="text-sm font-bold text-gray-900">Hide Wallet Balance</h4>
              <p className="text-xs text-gray-500">Mask balance across dashboard as ₹ •••••• by default</p>
            </div>
          </div>
          <button
            onClick={toggleHideBalance}
            className={`px-4 py-1.5 rounded-full text-xs font-bold transition ${
              hideBalance ? 'bg-orange-100 text-[#ff6200]' : 'bg-gray-100 text-gray-700'
            }`}
          >
            {hideBalance ? 'Hidden' : 'Visible'}
          </button>
        </div>

        {/* Privacy Mask Toggle */}
        <div className="flex items-center justify-between py-2 border-b border-gray-100">
          <div className="flex items-center gap-3">
            <div className="p-2.5 rounded-2xl bg-blue-50 text-[#0047cc]">
              <Shield className="w-5 h-5" />
            </div>
            <div>
              <h4 className="text-sm font-bold text-gray-900">Mask Transaction Phone Numbers</h4>
              <p className="text-xs text-gray-500">Show recipient numbers as 98••••3210 in statements</p>
            </div>
          </div>
          <label className="relative inline-flex items-center cursor-pointer">
            <input
              type="checkbox"
              checked={privacyMaskEnabled}
              onChange={(e) => setPrivacyMaskEnabled(e.target.checked)}
              className="sr-only peer"
            />
            <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#0047cc]"></div>
          </label>
        </div>

        {/* 2FA Toggle */}
        <div className="flex items-center justify-between py-2">
          <div className="flex items-center gap-3">
            <div className="p-2.5 rounded-2xl bg-emerald-50 text-emerald-600">
              <Key className="w-5 h-5" />
            </div>
            <div>
              <h4 className="text-sm font-bold text-gray-900">Two-Factor Authentication (2FA)</h4>
              <p className="text-xs text-gray-500">Extra security confirmation on transactions above ₹5,000</p>
            </div>
          </div>
          <label className="relative inline-flex items-center cursor-pointer">
            <input
              type="checkbox"
              checked={twoFactorEnabled}
              onChange={(e) => setTwoFactorEnabled(e.target.checked)}
              className="sr-only peer"
            />
            <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#0047cc]"></div>
          </label>
        </div>
      </div>

      {/* ---------------- 3. PAYBEN SOUNDBOX ANNOUNCEMENTS ---------------- */}
      <div className="bg-white rounded-3xl border border-gray-200/80 p-6 shadow-sm space-y-4">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="p-2.5 rounded-2xl bg-blue-50 text-[#0047cc]">
              <Volume2 className="w-5 h-5" />
            </div>
            <div>
              <h3 className="text-base font-bold text-gray-900">PayBen Smart Voice SoundBox</h3>
              <p className="text-xs text-gray-500">Audio voice confirmation like Paytm & PhonePe SoundBox</p>
            </div>
          </div>
          <button
            onClick={toggleSoundBox}
            className={`px-4 py-1.5 rounded-full text-xs font-bold transition ${
              soundBoxEnabled ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-700'
            }`}
          >
            {soundBoxEnabled ? 'Enabled' : 'Muted'}
          </button>
        </div>

        {soundBoxEnabled && (
          <div className="pt-2 flex items-center justify-between bg-blue-50/50 p-4 rounded-2xl border border-blue-100">
            <span className="text-xs text-gray-700 font-medium">
              Voice speaks: "PayBen: Rupees [amount] received successfully."
            </span>
            <button
              onClick={handleTestSoundBox}
              className="px-3.5 py-1.5 bg-[#0047cc] hover:bg-[#003db3] text-white text-xs font-bold rounded-xl transition shadow-sm"
            >
              Test Voice
            </button>
          </div>
        )}
      </div>

      {/* ---------------- 4. ACTIVE SESSIONS ---------------- */}
      <div className="bg-white rounded-3xl border border-gray-200/80 p-6 shadow-sm space-y-4">
        <h3 className="text-base font-bold text-gray-900">Active Devices & Sessions</h3>

        <div className="p-3 bg-gray-50 rounded-2xl flex items-center justify-between border border-gray-200/70">
          <div className="flex items-center gap-3">
            <div className="p-2.5 rounded-xl bg-white text-gray-700 shadow-sm border border-gray-100">
              <Laptop className="w-5 h-5 text-[#0047cc]" />
            </div>
            <div>
              <h4 className="text-xs font-bold text-gray-900">Current Web Session (Active)</h4>
              <p className="text-[11px] text-gray-500">Chrome / Edge Browser • Verified UPI Token</p>
            </div>
          </div>
          <span className="text-[10px] bg-emerald-100 text-emerald-700 font-bold px-2.5 py-1 rounded-full">
            This Device
          </span>
        </div>
      </div>

      {/* ---------------- MODAL: CHANGE MPIN ---------------- */}
      {showPinModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-in fade-in">
          <div className="bg-white rounded-3xl p-6 max-w-sm w-full space-y-4 shadow-2xl border border-gray-100">
            <h3 className="text-base font-bold text-gray-900">Set New 4-Digit MPIN</h3>
            <form onSubmit={handleSavePin} className="space-y-4">
              <div>
                <label className="block text-xs font-bold text-gray-700 mb-1">New 4-Digit PIN</label>
                <input
                  type="password"
                  maxLength={4}
                  required
                  value={newPin}
                  onChange={(e) => setNewPin(e.target.value.replace(/\D/g, ''))}
                  placeholder="••••"
                  className="w-full text-center tracking-widest text-2xl font-bold py-2.5 rounded-2xl border border-gray-200 focus:border-[#0047cc] outline-none"
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-gray-700 mb-1">Confirm PIN</label>
                <input
                  type="password"
                  maxLength={4}
                  required
                  value={confirmPin}
                  onChange={(e) => setConfirmPin(e.target.value.replace(/\D/g, ''))}
                  placeholder="••••"
                  className="w-full text-center tracking-widest text-2xl font-bold py-2.5 rounded-2xl border border-gray-200 focus:border-[#0047cc] outline-none"
                />
              </div>

              <div className="flex gap-2 pt-2">
                <button
                  type="button"
                  onClick={() => setShowPinModal(false)}
                  className="flex-1 py-2.5 bg-gray-100 rounded-xl text-xs font-bold text-gray-600 hover:bg-gray-200"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="flex-1 py-2.5 bg-[#0047cc] text-white rounded-xl text-xs font-bold hover:bg-[#003db3] shadow-md shadow-blue-500/20"
                >
                  Save MPIN
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* ---------------- MODAL: CHANGE PATTERN ---------------- */}
      {showPatternModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-in fade-in">
          <div className="bg-white rounded-3xl p-6 max-w-sm w-full space-y-4 shadow-2xl border border-gray-100 text-center">
            <h3 className="text-base font-bold text-gray-900">Draw New Pattern</h3>
            <p className="text-xs text-gray-500">Tap dots in order to define your pattern ({drawnDots.length} dots selected)</p>

            <div className="grid grid-cols-3 gap-6 w-52 h-52 mx-auto p-4 bg-blue-50 rounded-2xl border border-blue-100 items-center justify-center">
              {[0, 1, 2, 3, 4, 5, 6, 7, 8].map((dotIdx) => (
                <button
                  key={dotIdx}
                  type="button"
                  onClick={() => {
                    if (!drawnDots.includes(dotIdx)) {
                      setDrawnDots(prev => [...prev, dotIdx]);
                      soundService.playKeypadClick();
                    }
                  }}
                  className={`w-7 h-7 rounded-full mx-auto transition ${
                    drawnDots.includes(dotIdx)
                      ? 'bg-[#ff6200] scale-125 shadow-md shadow-orange-500/40'
                      : 'bg-gray-300 hover:bg-gray-400'
                  }`}
                />
              ))}
            </div>

            <div className="flex gap-2 pt-2">
              <button
                type="button"
                onClick={() => setDrawnDots([])}
                className="flex-1 py-2.5 bg-gray-100 rounded-xl text-xs font-bold text-gray-600 hover:bg-gray-200"
              >
                Reset
              </button>
              <button
                type="button"
                onClick={handleSavePattern}
                className="flex-1 py-2.5 bg-[#ff6200] text-white rounded-xl text-xs font-bold hover:bg-[#e85800] shadow-md shadow-orange-500/20"
              >
                Save Pattern
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

