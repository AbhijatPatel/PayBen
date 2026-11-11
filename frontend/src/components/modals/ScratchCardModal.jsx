import React, { useState } from 'react';
import confetti from 'canvas-confetti';
import { walletService } from '../../services/walletService';
import { useAuth } from '../../context/AuthContext';
import { useToast } from '../../context/ToastContext';
import { X, Gift, Sparkles, CheckCircle2, Loader2 } from 'lucide-react';

export const ScratchCardModal = ({ isOpen, onClose }) => {
  const { refreshBalance } = useAuth();
  const { addToast } = useToast();

  const [scratched, setScratched] = useState(false);
  const [loading, setLoading] = useState(false);
  const [cashback, setCashback] = useState(null);

  if (!isOpen) return null;

  const handleReveal = async () => {
    if (scratched || loading) return;
    setLoading(true);

    try {
      const res = await walletService.scratchCard();
      if (res.success && res.data) {
        setCashback(res.data.cashback_amount);
        setScratched(true);
        await refreshBalance();

        // Confetti effect
        confetti({
          particleCount: 80,
          spread: 70,
          origin: { y: 0.6 }
        });

        addToast(`₹${res.data.cashback_amount} added to your wallet!`, 'success');
      }
    } catch (err) {
      addToast(err.message, 'error');
    } finally {
      setLoading(false);
    }
  };

  const handleClose = () => {
    setScratched(false);
    setCashback(null);
    onClose();
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm animate-in fade-in">
      <div className="bg-white rounded-3xl shadow-2xl max-w-sm w-full overflow-hidden border border-gray-100 p-6 text-center">
        <div className="flex justify-between items-center mb-4">
          <div className="flex items-center gap-2">
            <Gift className="w-5 h-5 text-amber-500" />
            <h3 className="font-bold text-gray-900">PayBen Rewards</h3>
          </div>
          <button
            onClick={handleClose}
            className="p-1 rounded-full text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Scratch Card Box */}
        <div className="relative w-64 h-64 mx-auto rounded-3xl overflow-hidden shadow-lg border border-amber-200 cursor-pointer group">
          {/* Revealed Reward Layer */}
          <div className="absolute inset-0 bg-gradient-to-tr from-amber-50 via-yellow-100 to-amber-200 flex flex-col items-center justify-center p-4">
            <div className="w-16 h-16 rounded-full bg-white/90 shadow-md flex items-center justify-center text-amber-500 mb-2">
              <Sparkles className="w-8 h-8" />
            </div>
            <span className="text-xs font-bold text-amber-800 uppercase tracking-wider">You Won</span>
            <div className="text-4xl font-black text-gray-900 mt-1">
              ₹{cashback || 0}
            </div>
            <span className="text-[11px] text-amber-700 font-semibold mt-1">
              Credited directly to wallet!
            </span>
          </div>

          {/* Golden Scratch Overlay Layer (clicks to scratch) */}
          {!scratched && (
            <div
              onClick={handleReveal}
              className="absolute inset-0 bg-gradient-to-tr from-amber-400 via-yellow-500 to-amber-600 flex flex-col items-center justify-center text-white transition-opacity duration-500 group-hover:scale-105"
            >
              {loading ? (
                <Loader2 className="w-8 h-8 animate-spin" />
              ) : (
                <>
                  <Gift className="w-12 h-12 mb-2 animate-bounce" />
                  <span className="text-sm font-extrabold uppercase tracking-wider">Tap to Scratch</span>
                  <span className="text-[11px] text-amber-100 font-medium mt-1">Win up to ₹50 Cashback</span>
                </>
              )}
            </div>
          )}
        </div>

        <p className="text-xs text-gray-500 mt-4">
          Earn more scratch cards with every recharge and utility bill payment!
        </p>

        {scratched && (
          <button
            onClick={handleClose}
            className="mt-4 w-full py-2.5 bg-[#1a73e8] hover:bg-[#1557b0] text-white font-bold rounded-2xl transition"
          >
            Claim & Close
          </button>
        )}
      </div>
    </div>
  );
};

