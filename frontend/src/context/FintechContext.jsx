import React, { createContext, useContext, useState, useEffect } from 'react';
import { soundService } from '../services/soundService';

const FintechContext = createContext(null);

export const FintechProvider = ({ children }) => {
  // 1. Bank Accounts
  const [bankAccounts, setBankAccounts] = useState(() => {
    const saved = localStorage.getItem('payben_bank_accounts');
    return saved ? JSON.parse(saved) : [
      {
        id: 'bank_1',
        bank_name: 'State Bank of India',
        bank_code: 'SBI',
        account_number: '••••••••4829',
        ifsc: 'SBIN0001234',
        account_type: 'Savings',
        balance: 42850.50,
        is_primary: true,
        logo: 'https://images.unsplash.com/photo-1628157582853-a796fa650a6a?w=120&auto=format&fit=crop&q=80'
      },
      {
        id: 'bank_2',
        bank_name: 'HDFC Bank',
        bank_code: 'HDFC',
        account_number: '••••••••9102',
        ifsc: 'HDFC0000456',
        account_type: 'Current',
        balance: 118400.00,
        is_primary: false,
        logo: 'https://images.unsplash.com/photo-1541354329998-f4d9a9f9297f?w=120&auto=format&fit=crop&q=80'
      }
    ];
  });

  // 2. UPI IDs
  const [upiIds, setUpiIds] = useState(() => {
    const saved = localStorage.getItem('payben_upi_ids');
    return saved ? JSON.parse(saved) : [
      { id: 'upi_1', vpa: 'abhijat@payben', bank: 'State Bank of India', is_primary: true },
      { id: 'upi_2', vpa: 'abhijat@okhdfcbank', bank: 'HDFC Bank', is_primary: false },
      { id: 'upi_3', vpa: '9876543299@payben', bank: 'State Bank of India', is_primary: false }
    ];
  });

  // 3. UPI Lite
  const [upiLiteEnabled, setUpiLiteEnabled] = useState(() => {
    return localStorage.getItem('payben_upi_lite_enabled') === 'true';
  });

  const [upiLiteBalance, setUpiLiteBalance] = useState(() => {
    const saved = localStorage.getItem('payben_upi_lite_balance');
    return saved !== null ? parseFloat(saved) : 1500.00;
  });

  // 4. AutoPay Mandates
  const [autopayList, setAutopayList] = useState(() => {
    const saved = localStorage.getItem('payben_autopay_list');
    return saved ? JSON.parse(saved) : [
      {
        id: 'ap_1',
        name: 'Tata Power Electricity Bill',
        category: 'Utility Bill',
        amount_limit: '₹3,000.00',
        frequency: 'Monthly',
        next_date: '02 Dec 2026',
        bank: 'State Bank of India (4829)',
        status: 'Active'
      },
      {
        id: 'ap_2',
        name: 'Netflix Premium 4K',
        category: 'Entertainment',
        amount_limit: '₹649.00',
        frequency: 'Monthly',
        next_date: '28 Nov 2026',
        bank: 'HDFC Bank (9102)',
        status: 'Active'
      },
      {
        id: 'ap_3',
        name: 'JioFiber 100Mbps Broadband',
        category: 'Internet',
        amount_limit: '₹824.00',
        frequency: 'Monthly',
        next_date: '15 Dec 2026',
        bank: 'State Bank of India (4829)',
        status: 'Paused'
      }
    ];
  });

  // 5. Pocket Money (Sub-wallet)
  const [pocketMoney, setPocketMoney] = useState(() => {
    const saved = localStorage.getItem('payben_pocket_money');
    return saved ? JSON.parse(saved) : {
      account_name: "Aryan's Student Allowance",
      balance: 1450.00,
      monthly_budget: 3000.00,
      spent_this_month: 1550.00,
      card_number: '•••• •••• •••• 7712',
      transactions: [
        { id: 'pm_1', title: 'College Cafeteria', amount: 120, date: 'Today, 1:15 PM', type: 'debit' },
        { id: 'pm_2', title: 'Bookstore Stationers', amount: 450, date: 'Yesterday', type: 'debit' },
        { id: 'pm_3', title: 'Parent Allowance Top-Up', amount: 1000, date: '10 Nov 2026', type: 'credit' }
      ]
    };
  });

  // 6. Credit & Debit Cards
  const [cards, setCards] = useState(() => {
    const saved = localStorage.getItem('payben_cards');
    return saved ? JSON.parse(saved) : [
      {
        id: 'card_1',
        card_holder: 'Abhijat Patel',
        card_number: '•••• •••• •••• 4242',
        raw_number: '4242424242424242',
        expiry: '09/29',
        network: 'VISA',
        type: 'Credit Card',
        bank: 'HDFC Millennia',
        gradient: 'from-blue-700 via-indigo-800 to-slate-900'
      },
      {
        id: 'card_2',
        card_holder: 'Abhijat Patel',
        card_number: '•••• •••• •••• 8819',
        raw_number: '5123456789018819',
        expiry: '12/28',
        network: 'Mastercard',
        type: 'Debit Card',
        bank: 'SBI Global International',
        gradient: 'from-amber-600 via-orange-700 to-rose-900'
      }
    ];
  });

  // 7. CIBIL Score
  const [cibilScore, setCibilScore] = useState(785); // 300 - 900

  // 8. Instant Loan Offers
  const [loanOffers] = useState([
    {
      id: 'loan_1',
      title: 'Pre-Approved Instant Personal Loan',
      max_amount: 500000,
      interest_rate: '10.5% p.a.',
      min_tenure: 6,
      max_tenure: 36,
      features: ['Zero Paperwork', 'Instant Disbursal in 2 Mins', 'Flexible EMI']
    },
    {
      id: 'loan_2',
      title: 'PayBen Merchant Express Credit',
      max_amount: 250000,
      interest_rate: '9.9% p.a.',
      min_tenure: 3,
      max_tenure: 24,
      features: ['Daily Repayment Option', 'No Collateral Required', 'Pre-qualified']
    }
  ]);

  // Sync to localStorage
  useEffect(() => {
    localStorage.setItem('payben_bank_accounts', JSON.stringify(bankAccounts));
  }, [bankAccounts]);

  useEffect(() => {
    localStorage.setItem('payben_upi_ids', JSON.stringify(upiIds));
  }, [upiIds]);

  useEffect(() => {
    localStorage.setItem('payben_upi_lite_enabled', upiLiteEnabled ? 'true' : 'false');
  }, [upiLiteEnabled]);

  useEffect(() => {
    localStorage.setItem('payben_upi_lite_balance', upiLiteBalance.toString());
  }, [upiLiteBalance]);

  useEffect(() => {
    localStorage.setItem('payben_autopay_list', JSON.stringify(autopayList));
  }, [autopayList]);

  useEffect(() => {
    localStorage.setItem('payben_pocket_money', JSON.stringify(pocketMoney));
  }, [pocketMoney]);

  useEffect(() => {
    localStorage.setItem('payben_cards', JSON.stringify(cards));
  }, [cards]);

  // Actions
  const addBankAccount = (account) => {
    const newAcc = {
      id: `bank_${Date.now()}`,
      ...account,
      account_number: `••••••••${account.account_number.slice(-4)}`,
      balance: Math.floor(Math.random() * 80000) + 15000,
      is_primary: bankAccounts.length === 0
    };
    setBankAccounts(prev => [...prev, newAcc]);
    soundService.playBiometricSuccess();
    return newAcc;
  };

  const setPrimaryBank = (id) => {
    setBankAccounts(prev =>
      prev.map(acc => ({ ...acc, is_primary: acc.id === id }))
    );
    soundService.playKeypadClick();
  };

  const addUpiId = (vpa, bankName) => {
    const newUpi = {
      id: `upi_${Date.now()}`,
      vpa: vpa.includes('@') ? vpa : `${vpa}@payben`,
      bank: bankName || 'State Bank of India',
      is_primary: false
    };
    setUpiIds(prev => [...prev, newUpi]);
    soundService.playBiometricSuccess();
    return newUpi;
  };

  const toggleUpiLite = () => {
    setUpiLiteEnabled(prev => !prev);
    soundService.playKeypadClick();
  };

  const topUpUpiLite = (amount) => {
    const amt = parseFloat(amount);
    if (!amt || amt <= 0) return false;
    setUpiLiteBalance(prev => Math.min(2000, prev + amt));
    soundService.playPaymentSuccess();
    return true;
  };

  const payWithUpiLite = (amount, receiver) => {
    const amt = parseFloat(amount);
    if (amt > upiLiteBalance) return false;
    setUpiLiteBalance(prev => prev - amt);
    soundService.announcePayment(amt, receiver);
    return true;
  };

  const addAutoPayMandate = (mandate) => {
    const newMandate = {
      id: `ap_${Date.now()}`,
      ...mandate,
      status: 'Active'
    };
    setAutopayList(prev => [newMandate, ...prev]);
    soundService.playBiometricSuccess();
  };

  const toggleAutoPayStatus = (id) => {
    setAutopayList(prev =>
      prev.map(item =>
        item.id === id
          ? { ...item, status: item.status === 'Active' ? 'Paused' : 'Active' }
          : item
      )
    );
    soundService.playKeypadClick();
  };

  const topUpPocketMoney = (amount) => {
    const amt = parseFloat(amount);
    if (!amt || amt <= 0) return false;
    setPocketMoney(prev => ({
      ...prev,
      balance: prev.balance + amt,
      transactions: [
        {
          id: `pm_${Date.now()}`,
          title: 'Parent Allowance Top-Up',
          amount: amt,
          date: 'Just now',
          type: 'credit'
        },
        ...prev.transactions
      ]
    }));
    soundService.announcePayment(amt, "Aryan's Pocket Money");
    return true;
  };

  const addCard = (card) => {
    const num = card.raw_number.replace(/\s/g, '');
    let network = 'VISA';
    if (num.startsWith('5')) network = 'Mastercard';
    if (num.startsWith('6')) network = 'RuPay';
    if (num.startsWith('3')) network = 'Amex';

    const newCard = {
      id: `card_${Date.now()}`,
      ...card,
      card_number: `•••• •••• •••• ${num.slice(-4)}`,
      network,
      gradient: network === 'VISA' 
        ? 'from-blue-700 via-indigo-800 to-slate-900' 
        : network === 'Mastercard'
        ? 'from-amber-600 via-orange-700 to-rose-900'
        : 'from-emerald-700 via-teal-800 to-slate-900'
    };
    setCards(prev => [...prev, newCard]);
    soundService.playBiometricSuccess();
    return newCard;
  };

  return (
    <FintechContext.Provider
      value={{
        bankAccounts,
        addBankAccount,
        setPrimaryBank,
        upiIds,
        addUpiId,
        upiLiteEnabled,
        toggleUpiLite,
        upiLiteBalance,
        topUpUpiLite,
        payWithUpiLite,
        autopayList,
        addAutoPayMandate,
        toggleAutoPayStatus,
        pocketMoney,
        topUpPocketMoney,
        cards,
        addCard,
        cibilScore,
        loanOffers
      }}
    >
      {children}
    </FintechContext.Provider>
  );
};

export const useFintech = () => {
  const context = useContext(FintechContext);
  if (!context) {
    throw new Error('useFintech must be used within a FintechProvider');
  }
  return context;
};
