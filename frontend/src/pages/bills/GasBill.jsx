import React, { useState, useEffect } from 'react';
import { billService } from '../../services/billService';
import { useAuth } from '../../context/AuthContext';
import { useToast } from '../../context/ToastContext';
import { Flame, CheckCircle2, Loader2, Search, ArrowRight } from 'lucide-react';

export const GasBill = () => {
  const { wallet, refreshBalance } = useAuth();
  const { addToast } = useToast();

  const [providers, setProviders] = useState([]);
  const [selectedProvider, setSelectedProvider] = useState('');
  const [consumerNumber, setConsumerNumber] = useState('3001234567');
  const [bill, setBill] = useState(null);
  const [fetching, setFetching] = useState(false);
  const [paying, setPaying] = useState(false);
  const [receipt, setReceipt] = useState(null);

  useEffect(() => {
    billService.getProviders('gas').then((res) => {
      if (res.success && res.data.length > 0) {
        setProviders(res.data);
        setSelectedProvider(res.data[0].id.toString());
      }
    });
  }, []);

  const handleFetchBill = async (e) => {
    e.preventDefault();
    setFetching(true);
    setBill(null);
    try {
      const res = await billService.fetchBill('gas', parseInt(selectedProvider), consumerNumber.trim());
      if (res.success && res.data) {
        setBill(res.data);
        addToast('Piped gas bill details fetched!', 'success');
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
      addToast('Insufficient wallet balance. Please add funds.', 'error');
      return;
    }
    setPaying(true);
    try {
      const res = await billService.payBill('gas', parseInt(selectedProvider), consumerNumber.trim(), amount);
      if (res.success) {
        setReceipt(res.data);
        await refreshBalance();
        addToast('Gas bill paid successfully!', 'success');
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
        <div className="w-10 h-10 rounded-2xl bg-red-50 text-red-500 flex items-center justify-center">
          <Flame className="w-5 h-5 fill-current" />
        </div>
        <div>
          <h1 className="text-xl font-bold text-gray-900">Piped Gas & Cylinder Payment</h1>
          <p className="text-xs text-gray-500">Indraprastha, Mahanagar, Adani & Gujarat Gas</p>
        </div>
      </div>

      {receipt ? (
        <div className="bg-white rounded-3xl p-8 border border-gray-100 shadow-xl text-center space-y-4">
          <div className="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto">
            <CheckCircle2 className="w-10 h-10" />
          </div>
          <div>
            <span className="text-xs uppercase tracking-wider text-emerald-700 font-bold bg-emerald-50 px-3 py-1 rounded-full">
              Payment Successful
            </span>
            <div className="text-3xl font-black text-gray-900 mt-3">
              ₹{parseFloat(receipt.amount).toFixed(2)}
            </div>
            <p className="text-sm text-gray-500 mt-1">
              Paid to <strong className="text-gray-800">{receipt.provider}</strong> (BP Number: {receipt.consumer_number})
            </p>
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
              <label className="block text-xs font-semibold text-gray-600 mb-1">Select Gas Provider</label>
              <select
                value={selectedProvider}
                onChange={(e) => { setSelectedProvider(e.target.value); setBill(null); }}
                className="w-full px-3 py-2.5 rounded-2xl border border-gray-200 focus:border-red-500 focus:ring-2 focus:ring-red-100 outline-none text-sm font-medium bg-white"
              >
                {providers.map((p) => (
                  <option key={p.id} value={p.id}>{p.name} ({p.state})</option>
                ))}
              </select>
            </div>

            <div>
              <label className="block text-xs font-semibold text-gray-600 mb-1">BP Number / Customer ID</label>
              <input
                type="text"
                required
                value={consumerNumber}
                onChange={(e) => { setConsumerNumber(e.target.value); setBill(null); }}
                placeholder="e.g. 3001234567"
                className="w-full px-4 py-2.5 rounded-2xl border border-gray-200 focus:border-red-500 focus:ring-2 focus:ring-red-100 outline-none text-sm font-medium"
              />
            </div>

            <button
              type="submit"
              disabled={fetching}
              className="w-full py-3 bg-red-500 hover:bg-red-600 disabled:bg-red-300 text-white font-bold rounded-2xl flex items-center justify-center gap-2 transition shadow-md shadow-red-500/20"
            >
              {fetching ? <Loader2 className="w-5 h-5 animate-spin" /> : <Search className="w-4 h-4" />}
              <span>Fetch Gas Bill</span>
            </button>
          </form>

          {bill && (
            <div className="p-5 bg-red-50/50 border border-red-200 rounded-3xl space-y-4">
              <div className="flex justify-between items-start">
                <div>
                  <span className="text-[10px] font-bold uppercase tracking-wider text-red-800 bg-red-100 px-2 py-0.5 rounded-full">
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

              <button
                type="button"
                onClick={handlePayBill}
                disabled={paying}
                className="w-full py-3.5 bg-emerald-600 hover:bg-emerald-700 disabled:bg-emerald-300 text-white font-bold rounded-2xl flex items-center justify-center gap-2 transition"
              >
                {paying ? <Loader2 className="w-5 h-5 animate-spin" /> : <ArrowRight className="w-4 h-4" />}
                <span>Pay ₹{parseFloat(bill.bill_amount).toFixed(2)} from Wallet</span>
              </button>
            </div>
          )}
        </div>
      )}
    </div>
  );
};
