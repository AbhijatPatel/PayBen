import React, { useState, useEffect, useRef } from 'react';
import { useAuth } from '../../context/AuthContext';
import { useToast } from '../../context/ToastContext';
import { useSecurity } from '../../context/SecurityContext';
import { useFintech } from '../../context/FintechContext';
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
  Camera,
  RefreshCw,
  Wallet as WalletIcon
} from 'lucide-react';

export const ScanPayModal = ({ isOpen, onClose }) => {
  const { user, wallet, refreshBalance } = useAuth();
  const { addToast } = useToast();
  const { soundBoxEnabled } = useSecurity();
  const { upiLiteEnabled, upiLiteBalance, payWithUpiLite } = useFintech();

  const [step, setStep] = useState('scan'); // 'scan' | 'pay' | 'success'
  const [flashlight, setFlashlight] = useState(false);
  const [cameraActive, setCameraActive] = useState(false);
  const [cameraError, setCameraError] = useState(null);
  const videoRef = useRef(null);
  const streamRef = useRef(null);

  const [targetUpi, setTargetUpi] = useState('starcafe@payben');
  const [targetName, setTargetName] = useState('Star Cafe & Bakery');
  const [amount, setAmount] = useState('');
  const [note, setNote] = useState('Coffee & Snacks');
  const [paymentSource, setPaymentSource] = useState('wallet'); // 'wallet' | 'upi_lite'
  const [loading, setLoading] = useState(false);
  const [txnDetails, setTxnDetails] = useState(null);

  // Start real device camera on modal open
  useEffect(() => {
    if (isOpen && step === 'scan') {
      startCamera();
    } else {
      stopCamera();
    }
    return () => stopCamera();
  }, [isOpen, step]);

  const startCamera = async () => {
    try {
      setCameraError(null);
      if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        const stream = await navigator.mediaDevices.getUserMedia({
          video: { facingMode: 'environment', width: { ideal: 640 }, height: { ideal: 480 } }
        });
        streamRef.current = stream;
        if (videoRef.current) {
          videoRef.current.srcObject = stream;
          videoRef.current.play();
          setCameraActive(true);
        }
      } else {
        setCameraActive(false);
      }
    } catch (err) {
      setCameraActive(false);
      setCameraError('Camera access unavailable. Using interactive smart scan.');
    }
  };

  const stopCamera = () => {
    if (streamRef.current) {
      streamRef.current.getTracks().forEach(track => track.stop());
      streamRef.current = null;
    }
    setCameraActive(false);
  };

  if (!isOpen) return null;

  const handleSimulateScan = (name, upi) => {
    soundService.playScanBeep();
    stopCamera();
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

    if (paymentSource === 'upi_lite') {
      if (payAmount > 500) {
        addToast('UPI Lite supports transactions up to ₹500. Use Wallet or Bank.', 'error');
        return;
      }
      if (payAmount > upiLiteBalance) {
        addToast('Insufficient UPI Lite balance. Please top up.', 'error');
        return;
      }
      setLoading(true);
      setTimeout(() => {
        payWithUpiLite(payAmount, targetName);
        setTxnDetails({ transaction_id: `UPILITE${Date.now()}` });
        setStep('success');
        confetti({ particleCount: 100, spread: 70, origin: { y: 0.6 } });
        setLoading(false);
        addToast(`Paid ₹${payAmount} instantly with UPI Lite (Zero PIN)!`, 'success');
      }, 500);
      return;
    }

    // Default: Wallet Payment
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

        confetti({ particleCount: 100, spread: 70, origin: { y: 0.6 } });

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
    stopCamera();
    setStep('scan');
    setAmount('');
    setTxnDetails(null);
    onClose();
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-md animate-in fade-in">
      <div className="bg-[#0b162c] text-white rounded-3xl shadow-2xl max-w-sm w-full overflow-hidden border border-white/10 p-6 flex flex-col items-center">
        {/* Header */}
        <div className="w-full flex justify-between items-center mb-4">
          <div className="flex items-center gap-2">
            <Scan className="w-5 h-5 text-[#ff6200]" />
            <h3 className="font-black text-white text-base">Scan & Pay Any QR</h3>
          </div>
          <button
            onClick={handleReset}
            className="p-1.5 rounded-full text-gray-400 hover:text-white hover:bg-white/10 transition"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* ---------------- STEP 1: SCANNER VIEWFINDER WITH REAL CAMERA ---------------- */}
        {step === 'scan' && (
          <div className="w-full flex flex-col items-center">
            {/* Viewfinder Camera Box */}
            <div className="relative w-64 h-64 rounded-3xl border-2 border-dashed border-blue-400/50 overflow-hidden bg-black shadow-2xl flex items-center justify-center mb-3">
              {/* Real Device Camera Feed */}
              {cameraActive ? (
                <video
                  ref={videoRef}
                  autoPlay
                  playsInline
                  muted
                  className="w-full h-full object-cover"
                />
              ) : (
                <div className="flex flex-col items-center justify-center p-4 text-center opacity-40">
                  <Camera className="w-16 h-16 stroke-[1.2] text-blue-300 mb-2" />
                  <span className="text-xs text-blue-200">Point at merchant QR code</span>
                </div>
              )}

              {/* Laser Scan Bar Animation */}
              <div className="absolute inset-x-0 h-1 bg-gradient-to-r from-transparent via-[#ff6200] to-transparent shadow-[0_0_15px_#ff6200] animate-scanline z-10 pointer-events-none" />

              {/* Corner brackets */}
              <div className="absolute top-3 left-3 w-5 h-5 border-t-2 border-l-2 border-[#ff6200] pointer-events-none" />
              <div className="absolute top-3 right-3 w-5 h-5 border-t-2 border-r-2 border-[#ff6200] pointer-events-none" />
              <div className="absolute bottom-3 left-3 w-5 h-5 border-b-2 border-l-2 border-[#ff6200] pointer-events-none" />
              <div className="absolute bottom-3 right-3 w-5 h-5 border-b-2 border-r-2 border-[#ff6200] pointer-events-none" />

              {/* Status Pill */}
              <div className="absolute bottom-3 inset-x-0 text-center pointer-events-none z-20">
                <span className="text-[10px] font-mono text-cyan-300 bg-black/80 px-2.5 py-1 rounded-full border border-blue-700/60 backdrop-blur-sm">
                  {cameraActive ? '● Camera Live' : 'Align QR code in frame'}
                </span>
              </div>
            </div>

            {/* Quick Actions Bar */}
            <div className="flex gap-3 mb-4 w-full">
              <button
                onClick={() => setFlashlight(!flashlight)}
                className={`flex-1 py-2 rounded-xl border transition flex items-center justify-center gap-1.5 text-xs font-bold ${
                  flashlight
                    ? 'bg-yellow-400 text-black border-yellow-300'
                    : 'bg-white/5 border-white/10 text-gray-300 hover:text-white'
                }`}
              >
                <Zap className="w-3.5 h-3.5" />
                <span>{flashlight ? 'Torch ON' : 'Torch'}</span>
              </button>

              <button
                onClick={() => handleSimulateScan('Reliance Fresh Mart', 'reliancefresh@payben')}
                className="flex-1 py-2 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 transition flex items-center justify-center gap-1.5 text-xs font-bold text-gray-300 hover:text-white"
              >
                <Upload className="w-3.5 h-3.5" />
                <span>Gallery QR</span>
              </button>
            </div>

            {/* Simulated Instant Merchant Scan */}
            <div className="w-full bg-white/5 p-3 rounded-2xl border border-white/10">
              <span className="text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-2">
                Or Tap to Pay Demo Merchant
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
                  onClick={() => handleSimulateScan('Reliance Fresh Mart', 'reliancefresh@payben')}
                  className="p-2 rounded-xl bg-orange-600/30 hover:bg-orange-600/50 border border-orange-400/30 text-left text-xs transition"
                >
                  <p className="font-bold text-white truncate">🛒 Reliance Fresh</p>
                  <p className="text-[10px] text-orange-200 truncate">reliance@payben</p>
                </button>
              </div>
            </div>
          </div>
        )}

        {/* ---------------- STEP 2: AMOUNT INPUT & SOURCE ---------------- */}
        {step === 'pay' && (
          <form onSubmit={handlePay} className="w-full space-y-4">
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

              <div className="flex gap-2 mt-2">
                {[50, 100, 250, 500].map((amt) => (
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

            {/* Payment Source Choice */}
            <div>
              <label className="block text-xs font-semibold text-gray-300 mb-1">
                Choose Payment Mode
              </label>
              <div className="grid grid-cols-2 gap-2">
                <button
                  type="button"
                  onClick={() => setPaymentSource('wallet')}
                  className={`p-2.5 rounded-xl border text-left text-xs font-bold transition flex items-center gap-2 ${
                    paymentSource === 'wallet'
                      ? 'border-[#0047cc] bg-blue-600/30 text-white'
                      : 'border-white/10 bg-white/5 text-gray-400'
                  }`}
                >
                  <WalletIcon className="w-4 h-4 text-[#0047cc]" />
                  <div>
                    <p className="leading-tight">PayBen Wallet</p>
                    <span className="text-[10px] font-normal text-blue-200">
                      {wallet.formatted_balance}
                    </span>
                  </div>
                </button>

                <button
                  type="button"
                  onClick={() => setPaymentSource('upi_lite')}
                  className={`p-2.5 rounded-xl border text-left text-xs font-bold transition flex items-center gap-2 ${
                    paymentSource === 'upi_lite'
                      ? 'border-[#ff6200] bg-orange-600/30 text-white'
                      : 'border-white/10 bg-white/5 text-gray-400'
                  }`}
                >
                  <Zap className="w-4 h-4 text-[#ff6200]" />
                  <div>
                    <p className="leading-tight">UPI Lite (0 PIN)</p>
                    <span className="text-[10px] font-normal text-orange-200">
                      ₹{upiLiteBalance.toFixed(2)}
                    </span>
                  </div>
                </button>
              </div>
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full py-3.5 bg-gradient-to-r from-[#0047cc] to-[#ff6200] hover:brightness-110 font-bold text-white rounded-2xl flex items-center justify-center gap-2 shadow-lg shadow-orange-600/30 transition active:scale-95 disabled:opacity-50"
            >
              {loading ? (
                <Loader2 className="w-5 h-5 animate-spin" />
              ) : (
                <>
                  <span>Pay ₹{amount || '0'} via {paymentSource === 'upi_lite' ? 'UPI Lite' : 'PayBen'}</span>
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
                <span className="text-gray-400">Method:</span>
                <span className="font-medium text-emerald-300">
                  {paymentSource === 'upi_lite' ? 'UPI Lite Instant (Zero PIN)' : 'PayBen UPI Wallet'}
                </span>
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
