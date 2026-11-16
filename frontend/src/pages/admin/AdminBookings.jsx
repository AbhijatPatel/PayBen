import React, { useState, useEffect } from 'react';
import { travelService } from '../../services/travelService';
import { useToast } from '../../context/ToastContext';
import { Briefcase, Search, CheckCircle2, XCircle, Loader2, Train, Bus, Plane, Building2 } from 'lucide-react';

export const AdminBookings = () => {
  const { addToast } = useToast();
  const [bookings, setBookings] = useState([]);
  const [loading, setLoading] = useState(true);
  const [filter, setFilter] = useState('all');

  useEffect(() => {
    travelService.listBookings(filter).then((res) => {
      if (res && res.success) {
        setBookings(res.bookings || []);
      }
    }).catch((err) => {
      addToast('Error loading bookings', 'error');
    }).finally(() => {
      setLoading(false);
    });
  }, [filter]);

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h2 className="text-xl font-black text-white">Travel Reservations Oversight</h2>
          <p className="text-xs text-slate-400">Review sandbox train, bus, flight and hotel bookings</p>
        </div>

        <div className="flex gap-2">
          {['all', 'train', 'bus', 'flight', 'hotel'].map((f) => (
            <button
              key={f}
              onClick={() => setFilter(f)}
              className={`px-3 py-1.5 rounded-xl text-xs font-bold transition capitalize ${
                filter === f ? 'bg-orange-500 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'
              }`}
            >
              {f}
            </button>
          ))}
        </div>
      </div>

      {loading ? (
        <div className="p-12 text-center bg-slate-800 rounded-3xl">
          <Loader2 className="w-6 h-6 animate-spin mx-auto text-orange-400" />
        </div>
      ) : (
        <div className="bg-slate-800 rounded-3xl border border-slate-700 overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs text-slate-300 font-mono">
              <thead className="bg-slate-900/80 text-[10px] uppercase text-slate-400">
                <tr>
                  <th className="p-4">Reference</th>
                  <th className="p-4">Service</th>
                  <th className="p-4">Route / Location</th>
                  <th className="p-4">Date</th>
                  <th className="p-4">Fare</th>
                  <th className="p-4">Status</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-700">
                {bookings.map((b) => (
                  <tr key={b.booking_reference} className="hover:bg-slate-700/40">
                    <td className="p-4 font-bold text-white">
                      {b.booking_reference}
                      <span className="block text-[10px] text-blue-400">PNR: {b.pnr_or_ticket_number}</span>
                    </td>
                    <td className="p-4 font-sans font-bold text-white">
                      {b.title}
                      <span className="block text-[10px] text-slate-400 font-mono uppercase">{b.category}</span>
                    </td>
                    <td className="p-4">
                      {b.source_city} → {b.destination_city}
                    </td>
                    <td className="p-4">{b.travel_date}</td>
                    <td className="p-4 font-bold text-emerald-400">
                      ₹{parseFloat(b.amount).toFixed(2)}
                    </td>
                    <td className="p-4">
                      <span className={`px-2 py-0.5 rounded-full text-[10px] font-bold ${
                        b.status === 'CONFIRMED' ? 'bg-emerald-900/60 text-emerald-300' : 'bg-rose-900/60 text-rose-300'
                      }`}>
                        {b.status}
                      </span>
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

export default AdminBookings;
