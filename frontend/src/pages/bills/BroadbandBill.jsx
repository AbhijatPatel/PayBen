import React, { useState, useEffect } from 'react';
import { billService } from '../../services/billService';
import { useAuth } from '../../context/AuthContext';
import { useToast } from '../../context/ToastContext';
import { Wifi, CheckCircle2, Loader2, Search, ArrowRight, ShieldCheck, Download, RefreshCw } from 'lucide-react';

export const BroadbandBill = () => {
  const { wallet, refreshBalance } = useAuth();
  const { addToast } = useToast();

  const [providers, setProviders] = useState([]);
  const [selectedProvider, setSelectedProvider] = useState('');
  const [consumerNumber, setConsumerNumber] = useState('1008291038');
  const [bill, setBill] = useState(null);
  const [fetching, setFetching] = useState(false);
  const [paying, setPaying] = useState(false);
  const [receipt, setReceipt] = useState(null);

  useEffect(() => {
    billService.getProviders('broadband').then((res) => {
      if (res && res.success && res.data && res.data.length > 0) {
        setProviders(res.data);
        setSelectedProvider(res.data[0].id.toString());
      }
    }).catch(() => {
      // fallback
    });
  }, []);

  const handleFetchBill = async (e) => {
    e.preventDefault();
    if (!consumerNumber.trim()) {
      addToast('Please enter your broadband account or telephone number', 'error');
      return;
    }
    setFetching(true);
    setBill(null);
    try {
      const res = await billService.fetchBill('broadband', parseInt(selectedProvider || '1'), consumerNumber.trim());
      if (res && res.success && res.data) {
        setBill(res.data);
        addToast('Broadband bill fetched successfully!', 'success');
      } else {
        addToast('No unpaid bill found for this account.', 'info');
      }
    } catch (err) {
      addToast(err.message || 'Error fetching bill', 'error');
    } finally {
      setFetching(false);
    }
  };

  const handlePayBill = async () => {
    if (!bill) return;
    const amount = parseFloat(bill.bill_amount);
    const currentBal = parseFloat(wallet?.balance || 0);

    if (amount > currentBal) {
      addToast('Insufficient wallet balance. Please add cash to your wallet.', 'error');
      return;
    }

    setPaying(true);
    try {
      const res = await billService.payBill('broadband', parseInt(selectedProvider || '1'), consumerNumber.trim(), amount);
      if (res && res.success) {
        setReceipt({
          ...res.data,
          consumer_name: bill.consumer_name,
          date: new Date().toLocaleString('en-IN')
        });
        refreshBalance();
        addToast('Broadband bill paid successfully!', 'success');
      }
    } catch (err) {
      addToast(err.message || 'Payment failed', 'error');
    } finally {
      setPaying(false);
    }
  };

  if (receipt) {
    return (
      <div className="max-w-md mx-auto p-6 bg-white rounded-3xl border border-gray-200/80 shadow-sm text-center space-y-5 animate-in zoom-in-95">
        <div className="w-16 h-16 rounded-3xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto shadow-inner">
          <CheckCircle2 className="w-10 h-10" />
        </div>
        <div>
          <h2 className="text-xl font-extrabold text-[#101D30]">Broadband Bill Paid!</h2>
          <p className="text-xs text-gray-500 mt-1">Transaction confirmed in Sandbox</p>
        </div>

        <div className="p-4 bg-[#F5F7FA] rounded-2xl text-left text-xs space-y-2 font-mono">
          <div className="flex justify-between">
            <span className="text-gray-500">Transaction ID:</span>
            <span className="font-bold text-[#101D30]">{receipt.transaction_id}</span>
          </div>
          <div className="flex justify-between">
            <span className="text-gray-500">Provider:</span>
            <span className="font-bold text-[#101D30]">{receipt.provider}</span>
          </div>
          <div className="flex justify-between">
            <span className="text-gray-500">Account No:</span>
            <span className="font-bold text-[#101D30]">{receipt.consumer_number}</span>
          </div>
          <div className="flex justify-between">
            <span className="text-gray-500">Customer:</span>
            <span className="font-bold text-[#101D30]">{receipt.consumer_name}</span>
          </div>
          <div className="flex justify-between pt-2 border-t border-gray-200 text-sm">
            <span className="text-gray-700 font-sans font-bold">Amount Paid:</span>
            <span className="font-black text-emerald-600">₹{parseFloat(receipt.amount).toFixed(2)}</span>
          </div>
        </div>

        <button
          onClick={() => { setReceipt(null); setBill(null); }}
          className="w-full py-3 rounded-2xl bg-[#101D30] text-[#35D6B0] font-bold text-xs hover:bg-[#1a2f4d] transition"
        >
          Pay Another Bill
        </button>
      </div>
    );
  }

  return (
    <div className="max-w-2xl mx-auto space-y-6 pb-12">
      {/* Header Banner */}
      <div className="p-6 bg-[#101D30] text-white rounded-3xl border border-slate-800 flex items-center justify-between">
        <div className="space-y-1">
          <span className="text-[10px] font-mono font-bold text-[#35D6B0] uppercase tracking-wider block">
            High-Speed Internet • BBPS Sandbox
          </span>
          <h1 className="text-xl sm:text-2xl font-black">Broadband & FTTH Bill</h1>
          <p className="text-xs text-slate-300">JioFiber, Airtel Xstream, ACT Fibernet, BSNL & Tata Play</p>
        </div>
        <div className="w-12 h-12 rounded-2xl bg-white/10 text-[#35D6B0] flex items-center justify-center shrink-0">
          <Wifi className="w-6 h-6" />
        </div>
      </div>

      {/* Fetch Bill Form */}
      <div className="bg-white rounded-3xl p-6 border border-gray-200/80 shadow-xs space-y-5">
        <form onSubmit={handleFetchBill} className="space-y-4">
          <div>
            <label className="block text-xs font-bold text-gray-700 mb-1.5">Select Broadband Operator</label>
            <select
              value={selectedProvider}
              onChange={(e) => setSelectedProvider(e.target.value)}
              className="w-full px-4 py-3 rounded-2xl border border-gray-200 text-sm font-semibold text-gray-800 bg-[#F5F7FA] outline-none focus:border-[#35D6B0]"
            >
              {providers.length > 0 ? (
                providers.map((p) => (
                  <option key={p.id} value={p.id}>
                    {p.name} ({p.state})
                  </option>
                ))
              ) : (
                <>
                  <option value="1">JioFiber Broadband (National)</option>
                  <option value="2">Airtel Xstream Fiber (National)</option>
                  <option value="3">ACT Fibernet (National)</option>
                </>
              )}
            </select>
          </div>

          <div>
            <label className="block text-xs font-bold text-gray-700 mb-1.5">Account / Fixedline Number</label>
            <input
              type="text"
              value={consumerNumber}
              onChange={(e) => setConsumerNumber(e.target.value)}
              placeholder="e.g. 1008291038"
              className="w-full px-4 py-3 rounded-2xl border border-gray-200 text-sm font-mono text-gray-900 bg-[#F5F7FA] outline-none focus:border-[#35D6B0]"
            />
            <span className="text-[10px] text-gray-400 mt-1 block">
              Demo Test Account: 1008291038 or any custom ID
            </span>
          </div>

          <button
            type="submit"
            disabled={fetching}
            className="w-full py-3.5 bg-[#101D30] hover:bg-[#1a2f4d] disabled:opacity-50 text-[#35D6B0] font-extrabold text-xs sm:text-sm rounded-2xl flex items-center justify-center gap-2 transition shadow-xs"
          >
            {fetching ? <Loader2 className="w-4 h-4 animate-spin" /> : <span>Fetch Pending Bill</span>}
          </button>
        </form>

        {/* Bill Details Presentation */}
        {bill && (
          <div className="p-5 rounded-2xl bg-emerald-50/60 border border-emerald-200 space-y-4 animate-in fade-in">
            <div className="flex items-center justify-between border-b border-emerald-200/60 pb-3">
              <div>
                <span className="text-[10px] uppercase font-bold text-emerald-800 tracking-wider">Customer Name</span>
                <h4 className="text-sm font-extrabold text-emerald-950">{bill.consumer_name}</h4>
              </div>
              <div className="text-right">
                <span className="text-[10px] uppercase font-bold text-emerald-800 tracking-wider">Due Date</span>
                <p className="text-xs font-mono font-bold text-emerald-900">{bill.due_date}</p>
              </div>
            </div>

            <div className="flex items-center justify-between">
              <div>
                <span className="text-xs font-medium text-gray-600">Total Payable Amount</span>
                <div className="text-2xl font-black font-mono text-[#101D30]">
                  ₹{parseFloat(bill.bill_amount).toFixed(2)}
                </div>
              </div>

              <button
                onClick={handlePayBill}
                disabled={paying}
                className="px-6 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs sm:text-sm shadow-sm transition flex items-center gap-2 active:scale-95 disabled:opacity-50"
              >
                {paying ? <Loader2 className="w-4 h-4 animate-spin" /> : <span>Pay from Wallet</span>}
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default BroadbandBill;
