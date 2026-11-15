import React, { createContext, useContext, useState, useEffect } from 'react';
import { soundService } from '../services/soundService';

const SecurityContext = createContext(null);

export const SecurityProvider = ({ children }) => {
  // App Lock State
  const [appLockEnabled, setAppLockEnabled] = useState(() => {
    return localStorage.getItem('payben_app_lock_enabled') !== 'false';
  });

  const [lockMethod, setLockMethod] = useState(() => {
    return localStorage.getItem('payben_lock_method') || 'pin'; // 'pin' | 'pattern' | 'fingerprint' | 'face'
  });

  const [pin, setPin] = useState(() => {
    return localStorage.getItem('payben_mpin') || '1234';
  });

  const [pattern, setPattern] = useState(() => {
    try {
      const saved = localStorage.getItem('payben_pattern');
      return saved ? JSON.parse(saved) : [0, 1, 2, 4, 6, 7, 8];
    } catch (e) {
      return [0, 1, 2, 4, 6, 7, 8];
    }
  });

  // Start locked if appLockEnabled is true
  const [isLocked, setIsLocked] = useState(() => {
    const hasUser = !!localStorage.getItem('payben_user');
    const enabled = localStorage.getItem('payben_app_lock_enabled') !== 'false';
    return hasUser && enabled;
  });

  // Privacy: Hide / reveal balance
  const [hideBalance, setHideBalance] = useState(() => {
    return localStorage.getItem('payben_hide_balance') === 'true';
  });

  // Voice SoundBox toggle
  const [soundBoxEnabled, setSoundBoxEnabled] = useState(() => {
    return localStorage.getItem('payben_soundbox_enabled') !== 'false';
  });

  // Privacy mask for phone/account numbers
  const [privacyMaskEnabled, setPrivacyMaskEnabled] = useState(() => {
    return localStorage.getItem('payben_privacy_mask') === 'true';
  });

  // Two-Factor Auth toggle
  const [twoFactorEnabled, setTwoFactorEnabled] = useState(() => {
    return localStorage.getItem('payben_2fa_enabled') === 'true';
  });

  // Save changes to localStorage
  useEffect(() => {
    localStorage.setItem('payben_app_lock_enabled', appLockEnabled ? 'true' : 'false');
  }, [appLockEnabled]);

  useEffect(() => {
    localStorage.setItem('payben_lock_method', lockMethod);
  }, [lockMethod]);

  useEffect(() => {
    localStorage.setItem('payben_mpin', pin);
  }, [pin]);

  useEffect(() => {
    localStorage.setItem('payben_pattern', JSON.stringify(pattern));
  }, [pattern]);

  useEffect(() => {
    localStorage.setItem('payben_hide_balance', hideBalance ? 'true' : 'false');
  }, [hideBalance]);

  useEffect(() => {
    localStorage.setItem('payben_soundbox_enabled', soundBoxEnabled ? 'true' : 'false');
  }, [soundBoxEnabled]);

  useEffect(() => {
    localStorage.setItem('payben_privacy_mask', privacyMaskEnabled ? 'true' : 'false');
  }, [privacyMaskEnabled]);

  useEffect(() => {
    localStorage.setItem('payben_2fa_enabled', twoFactorEnabled ? 'true' : 'false');
  }, [twoFactorEnabled]);

  // Unlock / Lock actions
  const unlockApp = () => {
    soundService.playBiometricSuccess();
    setIsLocked(false);
  };

  const lockApp = () => {
    if (appLockEnabled) {
      setIsLocked(true);
    }
  };

  const verifyPin = (inputPin) => {
    if (inputPin === pin) {
      unlockApp();
      return true;
    }
    soundService.playErrorBuzz();
    return false;
  };

  const verifyPattern = (inputPattern) => {
    if (JSON.stringify(inputPattern) === JSON.stringify(pattern)) {
      unlockApp();
      return true;
    }
    soundService.playErrorBuzz();
    return false;
  };

  const verifyBiometric = (type) => {
    // Biometric verified
    unlockApp();
    return true;
  };

  const toggleHideBalance = () => {
    setHideBalance(prev => !prev);
    soundService.playKeypadClick();
  };

  const toggleSoundBox = () => {
    const next = !soundBoxEnabled;
    setSoundBoxEnabled(next);
    if (next) {
      soundService.speakSoundBox('PayBen Smart Voice SoundBox enabled.');
    }
  };

  const maskValue = (val) => {
    if (!val) return '';
    if (!privacyMaskEnabled) return val;
    const str = String(val);
    if (str.length <= 4) return str;
    const first2 = str.slice(0, 2);
    const last2 = str.slice(-2);
    return `${first2}••••${last2}`;
  };

  return (
    <SecurityContext.Provider
      value={{
        isLocked,
        lockApp,
        unlockApp,
        appLockEnabled,
        setAppLockEnabled,
        lockMethod,
        setLockMethod,
        pin,
        setPin,
        pattern,
        setPattern,
        verifyPin,
        verifyPattern,
        verifyBiometric,
        hideBalance,
        toggleHideBalance,
        soundBoxEnabled,
        toggleSoundBox,
        privacyMaskEnabled,
        setPrivacyMaskEnabled,
        twoFactorEnabled,
        setTwoFactorEnabled,
        maskValue
      }}
    >
      {children}
    </SecurityContext.Provider>
  );
};

export const useSecurity = () => {
  const context = useContext(SecurityContext);
  if (!context) {
    throw new Error('useSecurity must be used within a SecurityProvider');
  }
  return context;
};
