import React, { useState, useEffect } from 'react';
import { billService } from '../../services/billService';
import { useAuth } from '../../context/AuthContext';
import { useToast } from '../../context/ToastContext';
import { Zap, CheckCircle2, Loader2, Search, ArrowRight, ShieldCheck } from 'lucide-react';

export const ElectricityBill = () => {
  const { wallet, refreshBalance } = useAuth();
  const { addToast } = useToast();

  const [providers, setProviders] = useState([]);
  const [selectedProvider, setSelectedProvider] = useState('');
  const [consumerNumber, setConsumerNumber] = useState('1001234567');
  const [bill, setBill] = useState(null);
  const [fetching, setFetching] = useState(false);
  const [paying, setPaying] = useState(false);
  const [receipt, setReceipt] = useState(null);

  useEffect(() => {
    billService.getProviders('electricity').then((res) => {
      if (res.success && res.data.length > 0) {
        setProviders(res.data);
        setSelectedProvider(res.data[0].id.toString());
      }
    });
  }, []);

  const handleFetchBill = async (e) => {
    e.preventDefault();
    if (!consumerNumber.trim()) {
      addToast('Please enter your consumer number / CA number', 'error');
      return;
    }
    setFetching(true);
    setBill(null);
    try {
      const res = await billService.fetchBill('electricity', parseInt(selectedProvider), consumerNumber.trim());
      if (res.success && res.data) {
        setBill(res.data);
        addToast('Bill fetched successfully!', 'success');
      }
    } catch (err) {
      addToast(err.message, 'error');
    } finally {
      setFetching(false);
    }
  };

  const handlePayBill = async () => {
    if (!bill) return;
    const amount = parseFloat(bill.bill_amount);
    if (amount > wallet.balance) {
      addToast('Insufficient wallet balance. Please add funds first.', 'error');
      return;
    }

    setPaying(true);
    try {
      const res = await billService.payBill('electricity', parseInt(selectedProvider), consumerNumber.trim(), amount);
      if (res.success) {
        setReceipt(res.data);
        await refreshBalance();
        addToast('Electricity bill paid successfully!', 'success');
      }
    } catch (err) {
      addToast(err.message, 'error');
    } finally {
      setPaying(false);
    }
  };

  return (
    <div className="max-w-xl mx-auto space-y-6 pb-12">
      <div className="flex items-center gap-3">
        <div className="w-10 h-10 rounded-2xl bg-amber-50 text-amber-500 flex items-center justify-center">
          <Zap className="w-5 h-5 fill-current" />
        </div>
        <div>
          <h1 className="text-xl font-bold text-gray-900">Electricity Bill Payment</h1>
          <p className="text-xs text-gray-500">Pay power bills across all Indian state electricity boards</p>
        </div>
      </div>

      {receipt ? (
        <div className="bg-white rounded-3xl p-8 border border-gray-100 shadow-xl text-center space-y-4">
          <div className="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto">
            <CheckCircle2 className="w-10 h-10" />
          </div>
          <div>
            <span className="text-xs uppercase tracking-wider text-emerald-700 font-bold bg-emerald-50 px-3 py-1 rounded-full">
              Bill Paid Successfully
            </span>
            <div className="text-3xl font-black text-gray-900 mt-3">
              ₹{parseFloat(receipt.amount).toFixed(2)}
            </div>
            <p className="text-sm text-gray-500 mt-1">
              Paid to <strong className="text-gray-800">{receipt.provider}</strong> for Consumer No: <strong className="text-gray-800">{receipt.consumer_number}</strong>
            </p>
          </div>

          <div className="bg-gray-50 rounded-2xl p-4 text-xs space-y-2 text-left">
            <div className="flex justify-between">
              <span className="text-gray-500">Transaction ID</span>
              <span className="font-mono font-bold text-gray-800">{receipt.transaction_id}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-gray-500">Remaining Wallet Balance</span>
              <span className="font-bold text-gray-800">₹{parseFloat(receipt.new_balance).toFixed(2)}</span>
            </div>
          </div>

          <button
            onClick={() => { setReceipt(null); setBill(null); }}
            className="w-full py-3 bg-[#1a73e8] hover:bg-[#1557b0] text-white font-bold rounded-2xl transition"
          >
            Pay Another Bill
          </button>
        </div>
      ) : (
        <div className="bg-white rounded-3xl p-6 sm:p-8 border border-gray-100 shadow-sm space-y-6">
          <form onSubmit={handleFetchBill} className="space-y-4">
            <div>
              <label className="block text-xs font-semibold text-gray-600 mb-1">Select Electricity Board</label>
              <select
                value={selectedProvider}
                onChange={(e) => { setSelectedProvider(e.target.value); setBill(null); }}
                className="w-full px-3 py-2.5 rounded-2xl border border-gray-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-100 outline-none text-sm font-medium bg-white"
              >
                {providers.map((p) => (
                  <option key={p.id} value={p.id}>{p.name} ({p.state})</option>
                ))}
              </select>
            </div>

            <div>
              <label className="block text-xs font-semibold text-gray-600 mb-1">Consumer Number / CA Number</label>
              <div className="relative">
                <input
                  type="text"
                  required
                  value={consumerNumber}
                  onChange={(e) => { setConsumerNumber(e.target.value); setBill(null); }}
                  placeholder="e.g. 1001234567"
                  className="w-full px-4 py-2.5 rounded-2xl border border-gray-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-100 outline-none text-sm font-medium"
                />
              </div>
            </div>

            <button
              type="submit"
              disabled={fetching}
              className="w-full py-3 bg-amber-500 hover:bg-amber-600 disabled:bg-amber-300 text-white font-bold rounded-2xl flex items-center justify-center gap-2 transition shadow-md shadow-amber-500/20"
            >
              {fetching ? (
                <>
                  <Loader2 className="w-5 h-5 animate-spin" />
                  <span>Fetching Bill Details...</span>
                </>
              ) : (
                <>
                  <Search className="w-4 h-4" />
                  <span>Fetch Bill</span>
                </>
              )}
            </button>
          </form>

          {/* Fetched Bill Card */}
          {bill && (
            <div className="p-5 bg-gradient-to-br from-amber-50/50 to-orange-50/40 border border-amber-200 rounded-3xl space-y-4 animate-in fade-in">
              <div className="flex justify-between items-start">
                <div>
                  <span className="text-[10px] font-bold uppercase tracking-wider text-amber-800 bg-amber-100 px-2 py-0.5 rounded-full">
                    Bill Verified
                  </span>
                  <h3 className="text-base font-bold text-gray-900 mt-2">{bill.consumer_name}</h3>
                  <p className="text-xs text-gray-500">{bill.provider_name}</p>
                </div>
                <div className="text-right">
                  <span className="text-xs text-gray-400">Bill Amount</span>
                  <div className="text-2xl font-black text-gray-900">
                    ₹{parseFloat(bill.bill_amount).toFixed(2)}
                  </div>
                </div>
              </div>

              <div className="flex justify-between text-xs text-gray-600 pt-2 border-t border-amber-200/60">
                <span>Due Date: <strong>{bill.due_date}</strong></span>
                <span>Bill Date: <strong>{bill.bill_date}</strong></span>
              </div>

              <button
                type="button"
                onClick={handlePayBill}
                disabled={paying}
                className="w-full py-3.5 bg-emerald-600 hover:bg-emerald-700 disabled:bg-emerald-300 text-white font-bold rounded-2xl flex items-center justify-center gap-2 transition shadow-md shadow-emerald-600/20"
              >
                {paying ? (
                  <>
                    <Loader2 className="w-5 h-5 animate-spin" />
                    <span>Processing Payment...</span>
                  </>
                ) : (
                  <>
                    <span>Pay ₹{parseFloat(bill.bill_amount).toFixed(2)} from Wallet</span>
                    <ArrowRight className="w-4 h-4" />
                  </>
                )}
              </button>
            </div>
          )}
        </div>
      )}
    </div>
  );
};
