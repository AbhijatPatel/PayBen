import React, { createContext, useContext, useState, useEffect } from 'react';

const LanguageContext = createContext(null);

export const translations = {
  en: {
    dashboard: 'Dashboard',
    wallet: 'Wallet & UPI',
    scan_pay: 'Scan & Pay',
    my_qr: 'My QR',
    add_money: 'Add Money',
    send_money: 'Send Money',
    recharge: 'Recharge',
    bills: 'Bills',
    transactions: 'Transactions',
    profile: 'Profile',
    security: 'Security & Privacy',
    balance: 'Wallet Balance',
    upi_lite: 'UPI Lite',
    upiLite: 'UPI Lite',
    bank_accounts: 'Bank Accounts',
    bankAccounts: 'Bank Accounts',
    autopay: 'AutoPay Mandates',
    pocket_money: 'Pocket Money',
    pocketMoney: 'Pocket Money',
    cards: 'Credit & Debit Cards',
    cardsPay: 'Cards Vault',
    cibil_score: 'CIBIL Score',
    cibilLoan: 'CIBIL & Loans',
    fintechHub: 'Fintech Hub & Services',
    loans: 'Instant Loans',
    people_pay: 'Send to Contacts',
    rewards: 'Rewards Hub',
    soundbox: 'Smart SoundBox',
    switch_lang: 'हिन्दी',
    recent_activity: 'Recent Activity',
    view_all: 'View All',
    search_placeholder: 'Search contacts, bills, recharges, or UPI...'
  },
  hi: {
    dashboard: 'डैशबोर्ड',
    wallet: 'वॉलेट और यूपीआई',
    scan_pay: 'स्कैन और भुगतान',
    my_qr: 'मेरा क्यूआर',
    add_money: 'पैसे जोड़ें',
    send_money: 'पैसे भेजें',
    recharge: 'मोबाइल रिचार्ज',
    bills: 'बिल भुगतान',
    transactions: 'लेनदेन इतिहास',
    profile: 'मेरी प्रोफाइल',
    security: 'सुरक्षा और प्राइवेसी',
    balance: 'वॉलेट बैलेंस',
    upi_lite: 'यूपीआई लाइट',
    upiLite: 'यूपीआई लाइट',
    bank_accounts: 'बैंक खाते',
    bankAccounts: 'बैंक खाते',
    autopay: 'ऑटोपे मैंडेट',
    pocket_money: 'पॉकेट मनी',
    pocketMoney: 'पॉकेट मनी',
    cards: 'क्रेडिट और डेबिट कार्ड',
    cardsPay: 'कार्ड्स वॉल्ट',
    cibil_score: 'सिबिल स्कोर',
    cibilLoan: 'सिबिल और लोन',
    fintechHub: 'फिनटेक हब और सेवाएं',
    loans: 'त्वरित लोन',
    people_pay: 'संपर्कों को भेजें',
    rewards: 'रिवॉर्ड्स हब',
    soundbox: 'स्मार्ट साउंडबॉक्स',
    switch_lang: 'English',
    recent_activity: 'हालिया गतिविधियां',
    view_all: 'सभी देखें',
    search_placeholder: 'संपर्क, बिल, रिचार्ज या यूपीआई खोजें...'
  }
};

export const LanguageProvider = ({ children }) => {
  const [lang, setLang] = useState(() => {
    return localStorage.getItem('payben_lang') || 'en';
  });

  useEffect(() => {
    localStorage.setItem('payben_lang', lang);
  }, [lang]);

  const toggleLanguage = () => {
    setLang(prev => (prev === 'en' ? 'hi' : 'en'));
  };

  const t = (key) => {
    return translations[lang]?.[key] || translations['en']?.[key] || key;
  };

  return (
    <LanguageContext.Provider value={{ lang, setLang, toggleLanguage, t }}>
      {children}
    </LanguageContext.Provider>
  );
};

export const useLanguage = () => {
  const context = useContext(LanguageContext);
  if (!context) {
    throw new Error('useLanguage must be used within a LanguageProvider');
  }
  return context;
};
