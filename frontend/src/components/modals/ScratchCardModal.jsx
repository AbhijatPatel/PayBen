import React, { useState, useRef, useEffect } from 'react';
import confetti from 'canvas-confetti';
import { walletService } from '../../services/walletService';
import { useAuth } from '../../context/AuthContext';
import { useToast } from '../../context/ToastContext';
import { useSecurity } from '../../context/SecurityContext';
import { soundService } from '../../services/soundService';
import { X, Gift, Sparkles, CheckCircle2, Loader2, Coins } from 'lucide-react';

export const ScratchCardModal = ({ isOpen, onClose }) => {
  const { refreshBalance } = useAuth();
  const { addToast } = useToast();
  const { soundBoxEnabled } = useSecurity();

  const [scratched, setScratched] = useState(false);
  const [loading, setLoading] = useState(false);
  const [cashback, setCashback] = useState(null);
  const canvasRef = useRef(null);

  useEffect(() => {
    if (isOpen && !scratched && canvasRef.current) {
      initCanvas();
    }
  }, [isOpen, scratched]);

  const initCanvas = () => {
    const canvas = canvasRef.current;
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    canvas.width = 256;
    canvas.height = 256;

    // Draw Golden Metallic Foil
    const grad = ctx.createLinearGradient(0, 0, 256, 256);
    grad.addColorStop(0, '#f59e0b');
    grad.addColorStop(0.3, '#fbbf24');
    grad.addColorStop(0.5, '#d97706');
    grad.addColorStop(0.7, '#fef08a');
    grad.addColorStop(1, '#b45309');

    ctx.fillStyle = grad;
    ctx.fillRect(0, 0, 256, 256);

    // Decorative Foil Pattern
    ctx.strokeStyle = 'rgba(255, 255, 255, 0.25)';
    ctx.lineWidth = 2;
    for (let i = 0; i < 256; i += 24) {
      ctx.beginPath();
      ctx.moveTo(i, 0);
      ctx.lineTo(i + 256, 256);
      ctx.stroke();
    }

    // Centered Badge
    ctx.fillStyle = 'rgba(0, 0, 0, 0.15)';
    ctx.beginPath();
    ctx.arc(128, 128, 48, 0, Math.PI * 2);
    ctx.fill();

    ctx.font = 'bold 15px sans-serif';
    ctx.fillStyle = '#ffffff';
    ctx.textAlign = 'center';
    ctx.fillText('SCRATCH HERE', 128, 125);
    ctx.font = '11px sans-serif';
    ctx.fillStyle = '#fef3c7';
    ctx.fillText('WIN UP TO ₹500', 128, 142);
  };

  const handleScratchMove = (e) => {
    if (scratched || loading) return;
    const canvas = canvasRef.current;
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const rect = canvas.getBoundingClientRect();
    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
    const clientY = e.touches ? e.touches[0].clientY : e.clientY;
    const x = clientX - rect.left;
    const y = clientY - rect.top;

    ctx.globalCompositeOperation = 'destination-out';
    ctx.beginPath();
    ctx.arc(x, y, 26, 0, Math.PI * 2);
    ctx.fill();

    // Trigger reveal once user scratches
    triggerReveal();
  };

  const triggerReveal = async () => {
    if (scratched || loading) return;
    setLoading(true);

    try {
      const res = await walletService.scratchCard();
      if (res.success && res.data) {
        const wonAmount = res.data.cashback_amount;
        setCashback(wonAmount);
        setScratched(true);
        await refreshBalance();

        // Confetti Fireworks
        confetti({
          particleCount: 120,
          spread: 80,
          origin: { y: 0.6 }
        });

        // Voice SoundBox
        if (soundBoxEnabled) {
          soundService.announcePayment(wonAmount, 'Cashback Reward');
        } else {
          soundService.playPaymentSuccess();
        }

        addToast(`🎉 ₹${wonAmount} Cashback added to your wallet!`, 'success');
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

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-md animate-in fade-in">
      <div className="bg-white rounded-3xl shadow-2xl max-w-sm w-full overflow-hidden border border-gray-100 p-6 text-center">
        {/* Header */}
        <div className="flex justify-between items-center mb-4">
          <div className="flex items-center gap-2">
            <Coins className="w-5 h-5 text-[#ff6200]" />
            <h3 className="font-black text-gray-900 text-base">PayBen Cashback Rewards</h3>
          </div>
          <button
            onClick={handleClose}
            className="p-1 rounded-full text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* 3D Scratch Card Canvas Box */}
        <div
          onClick={triggerReveal}
          className="relative w-64 h-64 mx-auto rounded-3xl overflow-hidden shadow-xl border-2 border-amber-300 cursor-pointer transition-transform duration-300 hover:scale-[1.02]"
        >
          {/* Revealed Reward Background */}
          <div className="absolute inset-0 bg-gradient-to-tr from-amber-50 via-orange-100 to-amber-200 flex flex-col items-center justify-center p-4">
            <div className="w-16 h-16 rounded-full bg-white shadow-md flex items-center justify-center text-[#ff6200] mb-2 animate-bounce">
              <Sparkles className="w-8 h-8" />
            </div>
            <span className="text-xs font-black text-amber-900 uppercase tracking-wider">
              Congratulations! You Won
            </span>
            <div className="text-4xl font-black text-gray-900 my-1">
              ₹{cashback || 0}
            </div>
            <span className="text-[11px] text-amber-800 font-bold bg-amber-200/60 px-3 py-1 rounded-full">
              Credited directly to PayBen Wallet!
            </span>
          </div>

          {/* Interactive Scratch Canvas Overlay */}
          {!scratched && (
            <canvas
              ref={canvasRef}
              onMouseMove={handleScratchMove}
              onTouchMove={handleScratchMove}
              className="absolute inset-0 z-10 w-full h-full cursor-crosshair"
            />
          )}
        </div>

        <p className="text-xs text-gray-500 mt-4">
          Scratch to reveal your cashback bonus. Earn scratch cards on every bill and recharge!
        </p>

        {scratched && (
          <button
            onClick={handleClose}
            className="mt-4 w-full py-3 bg-gradient-to-r from-[#0047cc] to-[#ff6200] text-white font-bold rounded-2xl transition shadow-md shadow-orange-500/30"
          >
            Claim Cashback
          </button>
        )}
      </div>
    </div>
  );
};
