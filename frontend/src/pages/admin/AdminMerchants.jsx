import React, { useState, useEffect } from 'react';
import { merchantService } from '../../services/merchantService';
import { useToast } from '../../context/ToastContext';
import { ShoppingBag, Loader2, CheckCircle2, XCircle, ExternalLink } from 'lucide-react';

export const AdminMerchants = () => {
  const { addToast } = useToast();
  const [merchants, setMerchants] = useState([]);
  const [loading, setLoading] = useState(true);

  const loadData = async () => {
    setLoading(true);
    try {
      const res = await merchantService.list('all', true);
      if (res && res.success) {
        setMerchants(res.merchants || []);
      }
    } catch {
      addToast('Error loading merchants', 'error');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadData();
  }, []);

  const handleToggle = async (slug, currentStatus) => {
    try {
      const nextStatus = currentStatus === 1 ? 0 : 1;
      const res = await merchantService.toggleStatus(slug, nextStatus);
      if (res && res.success) {
        addToast(res.message, 'success');
        loadData();
      }
    } catch (err) {
      addToast(err?.message || 'Toggle failed', 'error');
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-xl font-black text-white">Partner Merchants & Services</h2>
        <p className="text-xs text-slate-400">Manage third-party marketplace discovery and redirection status</p>
      </div>

      {loading ? (
        <div className="p-12 text-center bg-slate-800 rounded-3xl">
          <Loader2 className="w-6 h-6 animate-spin mx-auto text-orange-400" />
        </div>
      ) : (
        <div className="bg-slate-800 rounded-3xl border border-slate-700 overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs text-slate-300">
              <thead className="bg-slate-900/80 text-[10px] uppercase text-slate-400 font-mono">
                <tr>
                  <th className="p-4">Partner Name</th>
                  <th className="p-4">Category</th>
                  <th className="p-4">Integration Type</th>
                  <th className="p-4">Official Target URL</th>
                  <th className="p-4">Status</th>
                  <th className="p-4 text-right">Action</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-700">
                {merchants.map((m) => (
                  <tr key={m.slug} className="hover:bg-slate-700/40">
                    <td className="p-4 font-bold text-white flex items-center gap-2">
                      <div className="w-7 h-7 rounded-lg bg-slate-900 text-orange-400 font-black flex items-center justify-center text-xs">
                        {m.name.charAt(0)}
                      </div>
                      <span>{m.name}</span>
                    </td>
                    <td className="p-4 uppercase font-mono text-[10px] text-slate-400">
                      {m.category_slug}
                    </td>
                    <td className="p-4 font-mono text-emerald-400">
                      {m.integration_type}
                    </td>
                    <td className="p-4 font-mono text-[11px] text-slate-400 truncate max-w-xs">
                      <a href={m.official_url} target="_blank" rel="noreferrer" className="hover:underline flex items-center gap-1">
                        <span>{m.official_url}</span>
                        <ExternalLink className="w-3 h-3" />
                      </a>
                    </td>
                    <td className="p-4">
                      {m.is_enabled ? (
                        <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-900/60 text-emerald-300">
                          Active
                        </span>
                      ) : (
                        <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-900/60 text-rose-300">
                          Disabled
                        </span>
                      )}
                    </td>
                    <td className="p-4 text-right">
                      <button
                        onClick={() => handleToggle(m.slug, m.is_enabled)}
                        className={`px-3 py-1.5 rounded-xl font-bold text-xs transition ${
                          m.is_enabled
                            ? 'bg-rose-950 text-rose-300 border border-rose-800 hover:bg-rose-900'
                            : 'bg-emerald-950 text-emerald-300 border border-emerald-800 hover:bg-emerald-900'
                        }`}
                      >
                        {m.is_enabled ? 'Disable' : 'Enable'}
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </div>
  );
};

export default AdminMerchants;
