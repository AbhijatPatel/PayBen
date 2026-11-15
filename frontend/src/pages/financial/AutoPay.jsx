import React, { useState } from 'react';
import { useFintech } from '../../context/FintechContext';
import { useToast } from '../../context/ToastContext';
import {
  Repeat,
  Plus,
  CheckCircle2,
  PauseCircle,
  PlayCircle,
  Calendar,
  Landmark,
  Tv,
  Zap,
  Trash2,
  ShieldCheck
} from 'lucide-react';

export const AutoPay = () => {
  const { autopayList, addAutoPayMandate, toggleAutoPayStatus, bankAccounts } = useFintech();
  const { addToast } = useToast();

  const [modalOpen, setModalOpen] = useState(false);
  const [billerName, setBillerName] = useState('');
  const [category, setCategory] = useState('Utility Bill');
  const [maxLimit, setMaxLimit] = useState('1500');
  const [frequency, setFrequency] = useState('Monthly');
  const [selectedBank, setSelectedBank] = useState('State Bank of India (4829)');

  const handleCreateMandate = (e) => {
    e.preventDefault();
    if (!billerName.trim()) {
      addToast('Please enter the biller name', 'error');
      return;
    }
    addAutoPayMandate({
      name: billerName,
      category,
      amount_limit: `₹${parseFloat(maxLimit).toFixed(2)}`,
      frequency,
      next_date: '01 Next Month',
      bank: selectedBank
    });
    addToast(`AutoPay mandate for ${billerName} registered!`, 'success');
    setModalOpen(false);
    setBillerName('');
  };

  return (
    <div className="max-w-4xl mx-auto space-y-6 pb-12 animate-in fade-in">
      {/* Banner */}
      <div className="bg-gradient-to-r from-[#0047cc] to-[#ff6200] rounded-3xl p-6 text-white shadow-xl flex items-center justify-between relative overflow-hidden">
        <div className="relative z-10 space-y-1">
          <div className="flex items-center gap-2">
            <Repeat className="w-6 h-6 text-yellow-300" />
            <h2 className="text-2xl font-black tracking-tight">AutoPay Mandates</h2>
          </div>
          <p className="text-xs text-blue-100 font-medium">
            Never miss a bill due date. Set up automatic UPI recurring mandates with custom spending limits.
          </p>
        </div>
        <div className="hidden sm:block opacity-20">
          <Repeat className="w-32 h-32" />
        </div>
      </div>

      {/* Mandates Management */}
      <div className="bg-white rounded-3xl border border-gray-200/80 p-6 shadow-xs space-y-4">
        <div className="flex items-center justify-between pb-3 border-b border-gray-100">
          <div>
            <h3 className="text-base font-bold text-gray-900">Your Active Mandates</h3>
            <p className="text-xs text-gray-500">Authorized recurring standing instructions</p>
          </div>
          <button
            onClick={() => setModalOpen(true)}
            className="px-4 py-2 rounded-xl bg-[#0047cc] hover:bg-[#003db3] text-white text-xs font-bold transition flex items-center gap-1.5 shadow-sm"
          >
            <Plus className="w-4 h-4" />
            <span>Setup New AutoPay</span>
          </button>
        </div>

        <div className="space-y-3">
          {autopayList.map((item) => (
            <div
              key={item.id}
              className="p-4 rounded-2xl border border-gray-200/80 hover:border-blue-200 bg-white transition flex flex-col sm:flex-row sm:items-center justify-between gap-4"
            >
              <div className="flex items-center gap-3.5">
                <div className="w-11 h-11 rounded-2xl bg-blue-50 text-[#0047cc] flex items-center justify-center font-bold shrink-0">
                  <Repeat className="w-5 h-5" />
                </div>
                <div>
                  <div className="flex items-center gap-2">
                    <h4 className="font-bold text-sm text-gray-900">{item.name}</h4>
                    <span
                      className={`text-[10px] font-bold px-2 py-0.5 rounded-full ${
                        item.status === 'Active'
                          ? 'bg-emerald-100 text-emerald-700'
                          : 'bg-amber-100 text-amber-700'
                      }`}
                    >
                      {item.status.toUpperCase()}
                    </span>
                  </div>
                  <p className="text-xs text-gray-500 mt-0.5">
                    Max Limit: <strong className="text-gray-900 font-mono">{item.amount_limit}</strong> • {item.frequency}
                  </p>
                  <p className="text-[11px] text-gray-400 font-mono">
                    Next Due: {item.next_date} • {item.bank}
                  </p>
                </div>
              </div>

              <div className="flex items-center gap-2 self-end sm:self-center">
                <button
                  onClick={() => toggleAutoPayStatus(item.id)}
                  className={`px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 ${
                    item.status === 'Active'
                      ? 'bg-amber-50 hover:bg-amber-100 text-amber-700'
                      : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700'
                  }`}
                >
                  {item.status === 'Active' ? (
                    <>
                      <PauseCircle className="w-3.5 h-3.5" />
                      <span>Pause</span>
                    </>
                  ) : (
                    <>
                      <PlayCircle className="w-3.5 h-3.5" />
                      <span>Resume</span>
                    </>
                  )}
                </button>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Setup AutoPay Modal */}
      {modalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-in fade-in">
          <div className="bg-white rounded-3xl p-6 max-w-md w-full space-y-4 shadow-2xl border border-gray-100">
            <h3 className="text-base font-black text-gray-900">Create AutoPay Mandate</h3>
            <form onSubmit={handleCreateMandate} className="space-y-3">
              <div>
                <label className="block text-xs font-bold text-gray-700 mb-1">Biller / Service Name</label>
                <input
                  type="text"
                  required
                  placeholder="e.g. BESCOM Electricity / Netflix"
                  value={billerName}
                  onChange={(e) => setBillerName(e.target.value)}
                  className="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs font-bold outline-none focus:border-[#0047cc]"
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-gray-700 mb-1">Category</label>
                <select
                  value={category}
                  onChange={(e) => setCategory(e.target.value)}
                  className="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs font-bold outline-none"
                >
                  <option value="Utility Bill">Electricity / Water / Gas</option>
                  <option value="Mobile / DTH">Mobile & DTH Recharge</option>
                  <option value="Entertainment">OTT / Streaming (Netflix, Hotstar)</option>
                  <option value="SIP Investment">Mutual Fund SIP</option>
                </select>
              </div>

              <div>
                <label className="block text-xs font-bold text-gray-700 mb-1">Maximum Debit Limit (₹)</label>
                <input
                  type="number"
                  required
                  placeholder="2000"
                  value={maxLimit}
                  onChange={(e) => setMaxLimit(e.target.value)}
                  className="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs font-bold outline-none focus:border-[#0047cc]"
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-gray-700 mb-1">Frequency</label>
                <select
                  value={frequency}
                  onChange={(e) => setFrequency(e.target.value)}
                  className="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs font-bold outline-none"
                >
                  <option value="Monthly">Monthly</option>
                  <option value="Weekly">Weekly</option>
                  <option value="Quarterly">Quarterly</option>
                </select>
              </div>

              <div className="flex gap-2 pt-2">
                <button
                  type="button"
                  onClick={() => setModalOpen(false)}
                  className="flex-1 py-2.5 bg-gray-100 rounded-xl text-xs font-bold text-gray-600 hover:bg-gray-200"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="flex-1 py-2.5 bg-[#0047cc] text-white rounded-xl text-xs font-bold hover:bg-[#003db3] shadow-md shadow-blue-500/20"
                >
                  Authorize Mandate
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
