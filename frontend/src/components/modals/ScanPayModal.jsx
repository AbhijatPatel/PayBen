import React, { useState } from 'react';
import { useAuth } from '../../context/AuthContext';
import { useToast } from '../../context/ToastContext';
import { useSecurity } from '../../context/SecurityContext';
import { soundService } from '../../services/soundService';
import { walletService } from '../../services/walletService';
import confetti from 'canvas-confetti';
import {
  X,
  Scan,
  Zap,
  Upload,
  User,
  ArrowRight,
  ShieldCheck,
  CheckCircle2,
  Loader2,
  Volume2
} from 'lucide-react';

export const ScanPayModal = ({ isOpen, onClose }) => {
  const { user, wallet, refreshBalance } = useAuth();
  const { addToast } = useToast();
  const { soundBoxEnabled } = useSecurity();

  const [step, setStep] = useState('scan'); // 'scan' | 'pay' | 'success'
  const [flashlight, setFlashlight] = useState(false);
  const [targetUpi, setTargetUpi] = useState('starcafe@payben');
  const [targetName, setTargetName] = useState('Star Cafe & Bakery');
  const [amount, setAmount] = useState('');
  const [note, setNote] = useState('Coffee & Snacks');
  const [loading, setLoading] = useState(false);
  const [txnDetails, setTxnDetails] = useState(null);

  if (!isOpen) return null;

  const handleSimulateScan = (name, upi) => {
    soundService.playScanBeep();
    setTargetName(name);
    setTargetUpi(upi);
    setStep('pay');
  };

  const handlePay = async (e) => {
    e.preventDefault();
    const payAmount = parseFloat(amount);
    if (!payAmount || payAmount <= 0) {
      addToast('Please enter a valid payment amount', 'error');
      return;
    }
    if (wallet.balance < payAmount) {
      addToast('Insufficient wallet balance. Please add money.', 'error');
      return;
    }

    setLoading(true);
    try {
      const res = await walletService.transfer(targetUpi, payAmount, note);
      if (res.success) {
        setTxnDetails(res.data);
        setStep('success');
        await refreshBalance();

        // Celebration Confetti
        confetti({
          particleCount: 100,
          spread: 70,
          origin: { y: 0.6 }
        });

        // Voice SoundBox
        if (soundBoxEnabled) {
          soundService.announcePayment(payAmount, targetName);
        } else {
          soundService.playPaymentSuccess();
        }

        addToast(`Payment of ₹${payAmount} to ${targetName} successful!`, 'success');
      }
    } catch (err) {
      addToast(err.message || 'Payment failed', 'error');
      soundService.playErrorBuzz();
    } finally {
      setLoading(false);
    }
  };

  const handleReset = () => {
    setStep('scan');
    setAmount('');
    setTxnDetails(null);
    onClose();
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md animate-in fade-in">
      <div className="bg-[#0b162c] text-white rounded-3xl shadow-2xl max-w-sm w-full overflow-hidden border border-white/10 p-6 flex flex-col items-center">
        {/* Header */}
        <div className="w-full flex justify-between items-center mb-4">
          <div className="flex items-center gap-2">
            <Scan className="w-5 h-5 text-[#ff6200]" />
            <h3 className="font-bold text-white text-base">Scan & Pay Any QR</h3>
          </div>
          <button
            onClick={handleReset}
            className="p-1.5 rounded-full text-gray-400 hover:text-white hover:bg-white/10 transition"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* ---------------- STEP 1: SCANNER VIEWFINDER ---------------- */}
        {step === 'scan' && (
          <div className="w-full flex flex-col items-center">
            {/* Viewfinder Camera Box */}
            <div className="relative w-64 h-64 rounded-3xl border-2 border-dashed border-blue-400/50 overflow-hidden bg-black/60 shadow-inner flex items-center justify-center mb-4">
              {/* Corner brackets */}
              <div className="absolute top-3 left-3 w-5 h-5 border-t-2 border-l-2 border-[#ff6200]" />
              <div className="absolute top-3 right-3 w-5 h-5 border-t-2 border-r-2 border-[#ff6200]" />
              <div className="absolute bottom-3 left-3 w-5 h-5 border-b-2 border-l-2 border-[#ff6200]" />
              <div className="absolute bottom-3 right-3 w-5 h-5 border-b-2 border-r-2 border-[#ff6200]" />

              {/* Laser Scan Bar Animation */}
              <div className="absolute inset-x-0 h-1 bg-gradient-to-r from-transparent via-[#ff6200] to-transparent shadow-[0_0_15px_#ff6200] animate-scanline z-10" />

              {/* Flashlight beam simulation */}
              {flashlight && (
                <div className="absolute inset-0 bg-white/10 pointer-events-none" />
              )}

              {/* Center QR watermark */}
              <div className="opacity-20 text-white">
                <Scan className="w-24 h-24 stroke-[1]" />
              </div>

              <div className="absolute bottom-3 inset-x-0 text-center">
                <span className="text-[11px] font-mono text-cyan-300 bg-blue-950/80 px-2.5 py-1 rounded-full border border-blue-800">
                  Align QR Code inside frame
                </span>
              </div>
            </div>

            {/* Quick Actions Bar */}
            <div className="flex gap-4 mb-4">
              <button
                onClick={() => setFlashlight(!flashlight)}
                className={`p-3 rounded-2xl border transition flex items-center gap-2 text-xs font-semibold ${
                  flashlight
                    ? 'bg-yellow-400 text-black border-yellow-300'
                    : 'bg-white/5 border-white/10 text-gray-300 hover:text-white'
                }`}
              >
                <Zap className="w-4 h-4" />
                <span>{flashlight ? 'Torch ON' : 'Torch'}</span>
              </button>

              <button
                onClick={() => handleSimulateScan('Reliance Fresh Mart', 'reliancefresh@payben')}
                className="p-3 rounded-2xl bg-white/5 border border-white/10 hover:bg-white/10 transition flex items-center gap-2 text-xs font-semibold text-gray-300 hover:text-white"
              >
                <Upload className="w-4 h-4" />
                <span>Upload QR</span>
              </button>
            </div>

            {/* Quick Demo Scan Buttons */}
            <div className="w-full bg-white/5 p-3 rounded-2xl border border-white/10">
              <span className="text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-2">
                Simulate Instant Merchant Scan
              </span>
              <div className="grid grid-cols-2 gap-2">
                <button
                  onClick={() => handleSimulateScan('Star Cafe & Bakery', 'starcafe@payben')}
                  className="p-2 rounded-xl bg-blue-600/30 hover:bg-blue-600/50 border border-blue-400/30 text-left text-xs transition"
                >
                  <p className="font-bold text-white truncate">☕ Star Cafe</p>
                  <p className="text-[10px] text-blue-200 truncate">starcafe@payben</p>
                </button>

                <button
                  onClick={() => handleSimulateScan('Reliance Fresh', 'reliancefresh@payben')}
                  className="p-2 rounded-xl bg-orange-600/30 hover:bg-orange-600/50 border border-orange-400/30 text-left text-xs transition"
                >
                  <p className="font-bold text-white truncate">🛒 Reliance Fresh</p>
                  <p className="text-[10px] text-orange-200 truncate">reliance@payben</p>
                </button>
              </div>
            </div>
          </div>
        )}

        {/* ---------------- STEP 2: AMOUNT INPUT & PAYMENT ---------------- */}
        {step === 'pay' && (
          <form onSubmit={handlePay} className="w-full space-y-4">
            {/* Merchant Details Pill */}
            <div className="p-3 bg-white/5 border border-white/10 rounded-2xl flex items-center gap-3">
              <div className="w-10 h-10 rounded-full bg-gradient-to-tr from-[#0047cc] to-[#ff6200] flex items-center justify-center font-bold text-white shadow-md">
                {targetName.charAt(0)}
              </div>
              <div className="flex-1 text-left">
                <h4 className="text-sm font-bold text-white leading-tight">{targetName}</h4>
                <p className="text-xs text-blue-300 font-mono">{targetUpi}</p>
              </div>
              <span className="text-[10px] bg-emerald-500/20 text-emerald-300 px-2 py-0.5 rounded-full border border-emerald-500/30 font-bold">
                Verified
              </span>
            </div>

            {/* Amount Input */}
            <div>
              <label className="block text-xs font-semibold text-gray-300 mb-1">
                Enter Amount (₹)
              </label>
              <div className="relative">
                <span className="absolute left-4 top-1/2 -translate-y-1/2 text-2xl font-bold text-gray-400">
                  ₹
                </span>
                <input
                  type="number"
                  required
                  min="1"
                  step="any"
                  autoFocus
                  value={amount}
                  onChange={(e) => setAmount(e.target.value)}
                  placeholder="0.00"
                  className="w-full pl-10 pr-4 py-3 bg-white/10 border border-white/20 rounded-2xl text-2xl font-black text-white placeholder-gray-500 outline-none focus:border-[#ff6200] transition"
                />
              </div>

              {/* Quick Amount Pills */}
              <div className="flex gap-2 mt-2">
                {[100, 250, 500, 1000].map((amt) => (
                  <button
                    key={amt}
                    type="button"
                    onClick={() => setAmount(String(amt))}
                    className="flex-1 py-1 text-xs font-bold rounded-lg bg-white/5 hover:bg-white/15 border border-white/10 text-gray-300 transition"
                  >
                    +₹{amt}
                  </button>
                ))}
              </div>
            </div>

            {/* Note Input */}
            <div>
              <label className="block text-xs font-semibold text-gray-300 mb-1">
                Add a Note (Optional)
              </label>
              <input
                type="text"
                value={note}
                onChange={(e) => setNote(e.target.value)}
                placeholder="e.g. Dinner with friends"
                className="w-full px-4 py-2.5 bg-white/10 border border-white/20 rounded-xl text-xs text-white placeholder-gray-500 outline-none focus:border-blue-400"
              />
            </div>

            {/* Wallet Balance Badge */}
            <div className="flex justify-between items-center text-xs px-1 text-gray-400">
              <span>Payment Source:</span>
              <span className="font-bold text-white flex items-center gap-1">
                PayBen Wallet ({wallet.formatted_balance})
              </span>
            </div>

            {/* Submit Button */}
            <button
              type="submit"
              disabled={loading}
              className="w-full py-3.5 bg-gradient-to-r from-[#0047cc] to-[#ff6200] hover:brightness-110 font-bold text-white rounded-2xl flex items-center justify-center gap-2 shadow-lg shadow-orange-600/30 transition active:scale-95 disabled:opacity-50"
            >
              {loading ? (
                <Loader2 className="w-5 h-5 animate-spin" />
              ) : (
                <>
                  <span>Pay ₹{amount || '0'} via PayBen</span>
                  <ArrowRight className="w-4 h-4" />
                </>
              )}
            </button>
          </form>
        )}

        {/* ---------------- STEP 3: PAYMENT SUCCESS RECEIPT ---------------- */}
        {step === 'success' && (
          <div className="w-full flex flex-col items-center py-4 text-center animate-in zoom-in-95">
            <div className="w-20 h-20 rounded-full bg-emerald-500/20 border-2 border-emerald-400 flex items-center justify-center mb-4 text-emerald-400 shadow-[0_0_30px_#10b981]">
              <CheckCircle2 className="w-12 h-12" />
            </div>

            <h4 className="text-xl font-black text-white mb-1">Payment Successful!</h4>
            <p className="text-xs text-gray-400 mb-4">Paid to {targetName}</p>

            <div className="text-4xl font-black text-[#ff6200] mb-6">
              ₹{amount}
            </div>

            <div className="w-full bg-white/5 border border-white/10 rounded-2xl p-4 text-xs space-y-2 mb-6 text-left">
              <div className="flex justify-between">
                <span className="text-gray-400">Transaction ID:</span>
                <span className="font-mono font-bold text-blue-300">
                  {txnDetails?.transaction_id || `PAYBEN${Date.now()}`}
                </span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-400">Paid To:</span>
                <span className="font-medium text-white">{targetUpi}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-400">Payment Mode:</span>
                <span className="font-medium text-white">Instant UPI Wallet</span>
              </div>
            </div>

            <button
              onClick={handleReset}
              className="w-full py-3 bg-[#0047cc] hover:bg-[#003db3] font-bold text-white rounded-2xl transition"
            >
              Done
            </button>
          </div>
        )}
      </div>
    </div>
  );
};
