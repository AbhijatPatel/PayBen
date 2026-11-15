import React, { useState } from 'react';
import { useFintech } from '../../context/FintechContext';
import { useToast } from '../../context/ToastContext';
import { soundService } from '../../services/soundService';
import {
  Landmark,
  Plus,
  CheckCircle2,
  Copy,
  ShieldCheck,
  Eye,
  EyeOff,
  Building,
  Key,
  Trash2,
  Sparkles,
  ArrowRight,
  Loader2
} from 'lucide-react';

export const BankAccounts = () => {
  const { bankAccounts, addBankAccount, setPrimaryBank, upiIds, addUpiId } = useFintech();
  const { addToast } = useToast();

  const [addBankModalOpen, setAddBankModalOpen] = useState(false);
  const [bankName, setBankName] = useState('State Bank of India');
  const [accountNumber, setAccountNumber] = useState('');
  const [confirmAccount, setConfirmAccount] = useState('');
  const [ifsc, setIfsc] = useState('SBIN0001234');
  const [holderName, setHolderName] = useState('Abhijat Patel');

  // Check balance modal
  const [checkBalModalOpen, setCheckBalModalOpen] = useState(false);
  const [selectedBankForBal, setSelectedBankForBal] = useState(null);
  const [balanceMpin, setBalanceMpin] = useState('');
  const [revealedBalance, setRevealedBalance] = useState(null);
  const [balLoading, setBalLoading] = useState(false);

  // Add UPI Modal
  const [addUpiModalOpen, setAddUpiModalOpen] = useState(false);
  const [newUpiHandle, setNewUpiHandle] = useState('');
  const [selectedBankForUpi, setSelectedBankForUpi] = useState('State Bank of India');

  const popularBanks = [
    'State Bank of India',
    'HDFC Bank',
    'ICICI Bank',
    'Axis Bank',
    'Punjab National Bank',
    'Kotak Mahindra Bank',
    'Bank of Baroda'
  ];

  const handleAddBank = (e) => {
    e.preventDefault();
    if (accountNumber.length < 9) {
      addToast('Please enter a valid bank account number', 'error');
      return;
    }
    if (accountNumber !== confirmAccount) {
      addToast('Account numbers do not match', 'error');
      return;
    }
    addBankAccount({
      bank_name: bankName,
      bank_code: bankName.slice(0, 4).toUpperCase(),
      account_number: accountNumber,
      ifsc: ifsc.toUpperCase(),
      account_type: 'Savings'
    });
    addToast(`${bankName} account linked successfully!`, 'success');
    setAddBankModalOpen(false);
    setAccountNumber('');
    setConfirmAccount('');
  };

  const handleVerifyBalance = (e) => {
    e.preventDefault();
    if (balanceMpin.length !== 4) {
      addToast('Enter 4-Digit UPI MPIN', 'error');
      return;
    }
    setBalLoading(true);
    setTimeout(() => {
      setBalLoading(false);
      setRevealedBalance(selectedBankForBal?.balance || 35400.00);
      soundService.playBiometricSuccess();
    }, 600);
  };

  const handleAddUpi = (e) => {
    e.preventDefault();
    if (!newUpiHandle.trim()) {
      addToast('Please enter a valid UPI handle', 'error');
      return;
    }
    addUpiId(newUpiHandle.trim(), selectedBankForUpi);
    addToast(`UPI ID ${newUpiHandle}@payben created!`, 'success');
    setAddUpiModalOpen(false);
    setNewUpiHandle('');
  };

  const handleCopy = (text) => {
    soundService.playKeypadClick();
    navigator.clipboard.writeText(text);
    addToast('Copied to clipboard!', 'success');
  };

  return (
    <div className="max-w-4xl mx-auto space-y-6 pb-12 animate-in fade-in">
      {/* Header Banner */}
      <div className="bg-gradient-to-r from-[#0047cc] to-[#ff6200] rounded-3xl p-6 text-white shadow-xl flex items-center justify-between relative overflow-hidden">
        <div className="relative z-10 space-y-1">
          <div className="flex items-center gap-2">
            <Landmark className="w-6 h-6 text-yellow-300" />
            <h2 className="text-2xl font-black tracking-tight">Bank Accounts & UPI IDs</h2>
          </div>
          <p className="text-xs text-blue-100 font-medium">
            Manage your linked bank accounts, check balances, and register personalized UPI handles
          </p>
        </div>
        <div className="hidden sm:block opacity-20">
          <Landmark className="w-32 h-32" />
        </div>
      </div>

      {/* ---------------- 1. LINKED BANK ACCOUNTS ---------------- */}
      <div className="bg-white rounded-3xl border border-gray-200/80 p-6 shadow-xs space-y-4">
        <div className="flex items-center justify-between pb-3 border-b border-gray-100">
          <div>
            <h3 className="text-base font-bold text-gray-900">Linked Bank Accounts</h3>
            <p className="text-xs text-gray-500">NPCI authorized direct bank payment sources</p>
          </div>
          <button
            onClick={() => setAddBankModalOpen(true)}
            className="px-4 py-2 rounded-xl bg-[#0047cc] hover:bg-[#003db3] text-white text-xs font-bold transition flex items-center gap-1.5 shadow-sm"
          >
            <Plus className="w-4 h-4" />
            <span>Add Bank Account</span>
          </button>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {bankAccounts.map((acc) => (
            <div
              key={acc.id}
              className={`p-5 rounded-2xl border-2 transition flex flex-col justify-between relative ${
                acc.is_primary ? 'border-[#0047cc] bg-blue-50/30 shadow-sm' : 'border-gray-200 bg-white'
              }`}
            >
              <div className="flex items-start justify-between mb-3">
                <div className="flex items-center gap-3">
                  <div className="w-11 h-11 rounded-2xl bg-blue-100 text-[#0047cc] flex items-center justify-center font-bold shadow-xs">
                    <Building className="w-5 h-5" />
                  </div>
                  <div>
                    <h4 className="font-bold text-sm text-gray-900">{acc.bank_name}</h4>
                    <span className="text-xs font-mono text-gray-500">{acc.account_number}</span>
                  </div>
                </div>
                {acc.is_primary && (
                  <span className="text-[10px] font-bold bg-[#0047cc] text-white px-2 py-0.5 rounded-full">
                    PRIMARY
                  </span>
                )}
              </div>

              <div className="pt-3 border-t border-gray-100 flex items-center justify-between">
                <button
                  onClick={() => {
                    setSelectedBankForBal(acc);
                    setRevealedBalance(null);
                    setBalanceMpin('');
                    setCheckBalModalOpen(true);
                  }}
                  className="text-xs font-bold text-[#0047cc] hover:underline flex items-center gap-1"
                >
                  <Eye className="w-3.5 h-3.5" />
                  <span>Check Balance</span>
                </button>

                {!acc.is_primary && (
                  <button
                    onClick={() => setPrimaryBank(acc.id)}
                    className="text-xs font-bold text-gray-500 hover:text-gray-900"
                  >
                    Set as Primary
                  </button>
                )}
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* ---------------- 2. MANAGE UPI IDS ---------------- */}
      <div className="bg-white rounded-3xl border border-gray-200/80 p-6 shadow-xs space-y-4">
        <div className="flex items-center justify-between pb-3 border-b border-gray-100">
          <div>
            <h3 className="text-base font-bold text-gray-900">Registered UPI IDs (VPA)</h3>
            <p className="text-xs text-gray-500">Receive money instantly from any UPI application</p>
          </div>
          <button
            onClick={() => setAddUpiModalOpen(true)}
            className="px-4 py-2 rounded-xl bg-orange-50 hover:bg-orange-100 text-[#ff6200] border border-orange-200 text-xs font-bold transition flex items-center gap-1.5"
          >
            <Plus className="w-4 h-4" />
            <span>Create UPI ID</span>
          </button>
        </div>

        <div className="space-y-2">
          {upiIds.map((item) => (
            <div
              key={item.id}
              className="p-3.5 bg-gray-50 rounded-2xl flex items-center justify-between border border-gray-200/70"
            >
              <div className="flex items-center gap-3">
                <div className="w-9 h-9 rounded-xl bg-white border border-gray-200 flex items-center justify-center font-mono font-bold text-xs text-[#0047cc]">
                  @
                </div>
                <div>
                  <h4 className="text-sm font-bold font-mono text-gray-900">{item.vpa}</h4>
                  <p className="text-[11px] text-gray-500">Linked to {item.bank}</p>
                </div>
              </div>

              <div className="flex items-center gap-2">
                <button
                  onClick={() => handleCopy(item.vpa)}
                  className="p-2 rounded-xl hover:bg-gray-200 text-gray-500 transition"
                  title="Copy UPI ID"
                >
                  <Copy className="w-4 h-4" />
                </button>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* ---------------- MODAL: ADD BANK ACCOUNT ---------------- */}
      {addBankModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-in fade-in">
          <div className="bg-white rounded-3xl p-6 max-w-md w-full space-y-4 shadow-2xl border border-gray-100">
            <h3 className="text-base font-black text-gray-900">Link Bank Account to PayBen</h3>
            <form onSubmit={handleAddBank} className="space-y-3">
              <div>
                <label className="block text-xs font-bold text-gray-700 mb-1">Select Bank</label>
                <select
                  value={bankName}
                  onChange={(e) => setBankName(e.target.value)}
                  className="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs font-bold outline-none focus:border-[#0047cc]"
                >
                  {popularBanks.map((b) => (
                    <option key={b} value={b}>{b}</option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block text-xs font-bold text-gray-700 mb-1">Account Number</label>
                <input
                  type="password"
                  required
                  placeholder="Enter full account number"
                  value={accountNumber}
                  onChange={(e) => setAccountNumber(e.target.value)}
                  className="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs font-mono outline-none focus:border-[#0047cc]"
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-gray-700 mb-1">Confirm Account Number</label>
                <input
                  type="text"
                  required
                  placeholder="Re-enter account number"
                  value={confirmAccount}
                  onChange={(e) => setConfirmAccount(e.target.value)}
                  className="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs font-mono outline-none focus:border-[#0047cc]"
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-gray-700 mb-1">IFSC Code</label>
                <input
                  type="text"
                  required
                  placeholder="SBIN0001234"
                  value={ifsc}
                  onChange={(e) => setIfsc(e.target.value)}
                  className="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs font-mono uppercase outline-none focus:border-[#0047cc]"
                />
              </div>

              <div className="flex gap-2 pt-2">
                <button
                  type="button"
                  onClick={() => setAddBankModalOpen(false)}
                  className="flex-1 py-2.5 bg-gray-100 rounded-xl text-xs font-bold text-gray-600 hover:bg-gray-200"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="flex-1 py-2.5 bg-[#0047cc] text-white rounded-xl text-xs font-bold hover:bg-[#003db3] shadow-md shadow-blue-500/20"
                >
                  Verify & Link
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* ---------------- MODAL: CHECK BALANCE WITH MPIN ---------------- */}
      {checkBalModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-in fade-in">
          <div className="bg-white rounded-3xl p-6 max-w-sm w-full space-y-4 shadow-2xl border border-gray-100 text-center">
            <h3 className="text-base font-black text-gray-900">{selectedBankForBal?.bank_name}</h3>
            <p className="text-xs text-gray-500">Account: {selectedBankForBal?.account_number}</p>

            {revealedBalance === null ? (
              <form onSubmit={handleVerifyBalance} className="space-y-4">
                <div>
                  <label className="block text-xs font-bold text-gray-700 mb-2">
                    Enter 4-Digit UPI MPIN
                  </label>
                  <input
                    type="password"
                    maxLength={4}
                    required
                    autoFocus
                    value={balanceMpin}
                    onChange={(e) => setBalanceMpin(e.target.value)}
                    placeholder="••••"
                    className="w-36 mx-auto text-center tracking-widest text-3xl font-black py-2 rounded-2xl border border-gray-300 outline-none focus:border-[#0047cc]"
                  />
                </div>

                <div className="flex gap-2">
                  <button
                    type="button"
                    onClick={() => setCheckBalModalOpen(false)}
                    className="flex-1 py-2.5 bg-gray-100 rounded-xl text-xs font-bold text-gray-600 hover:bg-gray-200"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    disabled={balLoading}
                    className="flex-1 py-2.5 bg-[#0047cc] text-white rounded-xl text-xs font-bold hover:bg-[#003db3] shadow-md shadow-blue-500/20"
                  >
                    {balLoading ? <Loader2 className="w-4 h-4 animate-spin mx-auto" /> : 'Submit'}
                  </button>
                </div>
              </form>
            ) : (
              <div className="py-4 space-y-2 animate-in zoom-in-95">
                <span className="text-xs text-gray-500 uppercase tracking-wider font-bold">
                  Available Balance
                </span>
                <div className="text-3xl font-black text-gray-900 font-mono">
                  ₹{revealedBalance.toLocaleString('en-IN', { minimumFractionDigits: 2 })}
                </div>
                <button
                  onClick={() => setCheckBalModalOpen(false)}
                  className="mt-4 w-full py-2.5 bg-gray-900 text-white rounded-xl text-xs font-bold"
                >
                  Done
                </button>
              </div>
            )}
          </div>
        </div>
      )}

      {/* ---------------- MODAL: CREATE UPI ID ---------------- */}
      {addUpiModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-in fade-in">
          <div className="bg-white rounded-3xl p-6 max-w-sm w-full space-y-4 shadow-2xl border border-gray-100">
            <h3 className="text-base font-black text-gray-900">Create Custom UPI Handle</h3>
            <form onSubmit={handleAddUpi} className="space-y-3">
              <div>
                <label className="block text-xs font-bold text-gray-700 mb-1">Enter Handle</label>
                <div className="flex items-center rounded-xl border border-gray-200 overflow-hidden px-3 py-2">
                  <input
                    type="text"
                    required
                    placeholder="yourname"
                    value={newUpiHandle}
                    onChange={(e) => setNewUpiHandle(e.target.value.toLowerCase().replace(/[^a-z0-9.]/g, ''))}
                    className="flex-1 text-xs font-mono font-bold outline-none"
                  />
                  <span className="text-xs font-bold text-[#0047cc]">@payben</span>
                </div>
              </div>

              <div>
                <label className="block text-xs font-bold text-gray-700 mb-1">Link to Bank</label>
                <select
                  value={selectedBankForUpi}
                  onChange={(e) => setSelectedBankForUpi(e.target.value)}
                  className="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs font-bold outline-none"
                >
                  {bankAccounts.map((b) => (
                    <option key={b.id} value={b.bank_name}>{b.bank_name} ({b.account_number})</option>
                  ))}
                </select>
              </div>

              <div className="flex gap-2 pt-2">
                <button
                  type="button"
                  onClick={() => setAddUpiModalOpen(false)}
                  className="flex-1 py-2.5 bg-gray-100 rounded-xl text-xs font-bold text-gray-600 hover:bg-gray-200"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="flex-1 py-2.5 bg-[#ff6200] text-white rounded-xl text-xs font-bold hover:bg-[#e85800] shadow-md shadow-orange-500/20"
                >
                  Create Handle
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
