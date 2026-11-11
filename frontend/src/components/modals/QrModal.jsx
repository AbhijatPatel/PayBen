import React from 'react';
import { useAuth } from '../../context/AuthContext';
import { X, QrCode, Download, ShieldCheck, Copy } from 'lucide-react';
import { useToast } from '../../context/ToastContext';

export const QrModal = ({ isOpen, onClose }) => {
  const { user } = useAuth();
  const { addToast } = useToast();

  if (!isOpen || !user) return null;

  const upiId = user.upi_id || `${user.mobile}@payben`;
  // Generate high-resolution SVG/QR representation or QR server API
  const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=upi://pay?pa=${encodeURIComponent(upiId)}%26pn=${encodeURIComponent(user.full_name)}`;

  const handleCopyUpi = () => {
    navigator.clipboard.writeText(upiId);
    addToast('UPI ID copied to clipboard!', 'success');
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm animate-in fade-in">
      <div className="bg-white rounded-3xl shadow-2xl max-w-sm w-full overflow-hidden border border-gray-100 p-6 text-center">
        <div className="flex justify-between items-center mb-4">
          <div className="flex items-center gap-2">
            <QrCode className="w-5 h-5 text-[#1a73e8]" />
            <h3 className="font-bold text-gray-900">My PayBen QR</h3>
          </div>
          <button
            onClick={onClose}
            className="p-1 rounded-full text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* QR Card Container */}
        <div className="p-4 bg-gradient-to-b from-blue-50 to-white rounded-2xl border border-blue-100 shadow-inner flex flex-col items-center">
          <div className="w-12 h-12 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-bold text-lg flex items-center justify-center mb-2 shadow-md">
            {user.full_name?.charAt(0).toUpperCase()}
          </div>
          <h4 className="font-bold text-gray-900 text-base">{user.full_name}</h4>
          <p className="text-xs text-gray-500 mb-3">{user.mobile}</p>

          <div className="p-3 bg-white rounded-2xl shadow-md border border-gray-100 mb-3">
            <img
              src={qrUrl}
              alt="UPI QR Code"
              className="w-48 h-48 object-contain rounded-lg"
              onError={(e) => {
                // Fallback inline placeholder
                e.target.style.display = 'none';
              }}
            />
          </div>

          <div
            onClick={handleCopyUpi}
            className="flex items-center gap-1.5 bg-blue-100/70 hover:bg-blue-100 text-[#1a73e8] px-3 py-1.5 rounded-full text-xs font-mono font-bold cursor-pointer transition"
          >
            <span>{upiId}</span>
            <Copy className="w-3.5 h-3.5" />
          </div>
        </div>

        <div className="flex items-center justify-center gap-1 text-[11px] text-gray-400 mt-4">
          <ShieldCheck className="w-4 h-4 text-emerald-500" />
          <span>Scan with any UPI app (PayBen, GPay, PhonePe, Paytm)</span>
        </div>
      </div>
    </div>
  );
};
