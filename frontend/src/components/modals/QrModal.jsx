import React, { useState } from 'react';
import { useAuth } from '../../context/AuthContext';
import { useToast } from '../../context/ToastContext';
import { soundService } from '../../services/soundService';
import {
  X,
  QrCode,
  Download,
  ShieldCheck,
  Copy,
  Share2,
  Sparkles
} from 'lucide-react';

export const QrModal = ({ isOpen, onClose }) => {
  const { user } = useAuth();
  const { addToast } = useToast();
  const [requestAmount, setRequestAmount] = useState('');
  const [showAmountInput, setShowAmountInput] = useState(false);

  if (!isOpen || !user) return null;

  const upiId = user.upi_id || `${user.mobile}@payben`;
  const amtParam = requestAmount && parseFloat(requestAmount) > 0 ? `%26am=${requestAmount}` : '';
  const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=upi://pay?pa=${encodeURIComponent(upiId)}%26pn=${encodeURIComponent(user.full_name)}${amtParam}`;

  const handleCopyUpi = () => {
    soundService.playKeypadClick();
    navigator.clipboard.writeText(upiId);
    addToast('UPI ID copied to clipboard!', 'success');
  };

  const handleDownload = () => {
    soundService.playKeypadClick();
    const a = document.createElement('a');
    a.href = qrUrl;
    a.download = `PayBen_QR_${upiId}.png`;
    a.target = '_blank';
    a.click();
    addToast('PayBen QR Code downloaded!', 'success');
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-in fade-in">
      <div className="bg-white rounded-3xl shadow-2xl max-w-sm w-full overflow-hidden border border-gray-100 p-6 text-center">
        {/* Header */}
        <div className="flex justify-between items-center mb-4">
          <div className="flex items-center gap-2">
            <QrCode className="w-5 h-5 text-[#0047cc]" />
            <h3 className="font-bold text-gray-900">My PayBen Bharat QR</h3>
          </div>
          <button
            onClick={onClose}
            className="p-1 rounded-full text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* QR Card Container */}
        <div className="p-4 bg-gradient-to-b from-blue-50/80 via-white to-orange-50/40 rounded-2xl border border-blue-100 shadow-inner flex flex-col items-center relative overflow-hidden">
          {/* PayBen Brand Header */}
          <img
            src="/payben-logo.png"
            alt="PayBen"
            className="h-8 w-auto mb-2 object-contain"
          />

          <h4 className="font-black text-gray-900 text-base">{user.full_name}</h4>
          <p className="text-xs text-gray-500 mb-3">{user.mobile}</p>

          {/* QR Box with center logo badge */}
          <div className="relative p-3 bg-white rounded-2xl shadow-md border border-gray-100 mb-3">
            <img
              src={qrUrl}
              alt="UPI QR Code"
              className="w-48 h-48 object-contain rounded-lg"
            />
            {/* Center Logo Watermark */}
            <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
              <div className="w-9 h-9 rounded-xl bg-white p-1 shadow-md border border-gray-100 flex items-center justify-center">
                <img src="/payben-logo.png" alt="" className="w-full h-auto object-contain" />
              </div>
            </div>
          </div>

          {/* Amount Badge if requested */}
          {requestAmount && parseFloat(requestAmount) > 0 && (
            <div className="mb-2 text-xs font-black text-[#ff6200] bg-orange-100/80 px-3 py-1 rounded-full">
              Requested: ₹{requestAmount}
            </div>
          )}

          {/* Copy UPI ID Pill */}
          <div
            onClick={handleCopyUpi}
            className="flex items-center gap-1.5 bg-blue-100/70 hover:bg-blue-100 text-[#0047cc] px-3.5 py-1.5 rounded-full text-xs font-mono font-bold cursor-pointer transition shadow-xs"
          >
            <span>{upiId}</span>
            <Copy className="w-3.5 h-3.5" />
          </div>
        </div>

        {/* Actions: Request Amount Toggle + Download */}
        <div className="mt-4 space-y-2">
          {showAmountInput ? (
            <div className="flex gap-2">
              <input
                type="number"
                placeholder="Enter amount (₹)"
                value={requestAmount}
                onChange={(e) => setRequestAmount(e.target.value)}
                className="flex-1 px-3 py-1.5 border border-gray-200 rounded-xl text-xs font-bold outline-none focus:border-[#0047cc]"
              />
              <button
                onClick={() => setShowAmountInput(false)}
                className="px-3 py-1.5 bg-gray-100 rounded-xl text-xs font-semibold text-gray-600"
              >
                Done
              </button>
            </div>
          ) : (
            <button
              onClick={() => setShowAmountInput(true)}
              className="text-xs font-bold text-[#0047cc] hover:underline flex items-center justify-center gap-1 mx-auto"
            >
              <Sparkles className="w-3.5 h-3.5 text-[#ff6200]" />
              <span>Request Specific Amount</span>
            </button>
          )}

          <div className="flex gap-2 pt-1">
            <button
              onClick={handleDownload}
              className="flex-1 py-2.5 rounded-xl bg-gray-100 hover:bg-gray-200 text-xs font-bold text-gray-700 flex items-center justify-center gap-1.5 transition"
            >
              <Download className="w-4 h-4" />
              <span>Download QR</span>
            </button>
            <button
              onClick={handleCopyUpi}
              className="flex-1 py-2.5 rounded-xl bg-[#0047cc] hover:bg-[#003db3] text-xs font-bold text-white flex items-center justify-center gap-1.5 transition shadow-sm"
            >
              <Share2 className="w-4 h-4" />
              <span>Share UPI ID</span>
            </button>
          </div>
        </div>

        <div className="flex items-center justify-center gap-1 text-[11px] text-gray-400 mt-4">
          <ShieldCheck className="w-4 h-4 text-emerald-500" />
          <span>Accepts payments from GPay, PhonePe, Paytm & BharatPe</span>
        </div>
      </div>
    </div>
  );
};
