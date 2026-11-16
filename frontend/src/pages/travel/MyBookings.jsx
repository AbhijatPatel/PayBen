import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { travelService } from '../../services/travelService';
import { useAuth } from '../../context/AuthContext';
import { useToast } from '../../context/ToastContext';
import {
  Briefcase,
  Train,
  Bus,
  Plane,
  Building2,
  Calendar,
  CheckCircle2,
  XCircle,
  Loader2,
  AlertCircle,
  Clock,
  ArrowRight
} from 'lucide-react';

const getCategoryIcon = (cat) => {
  switch (cat) {
    case 'train':
      return Train;
    case 'bus':
      return Bus;
    case 'flight':
      return Plane;
    case 'hotel':
      return Building2;
    default:
      return Briefcase;
  }
};

export const MyBookings = () => {
  const { refreshBalance } = useAuth();
  const { addToast } = useToast();

  const [category, setCategory] = useState('all');
  const [bookings, setBookings] = useState([]);
  const [loading, setLoading] = useState(true);
  const [cancellingRef, setCancellingRef] = useState(null);

  const fetchBookings = async () => {
    setLoading(true);
    try {
      const res = await travelService.listBookings(category);
      if (res && res.success) {
        setBookings(res.bookings || []);
      }
    } catch (err) {
      addToast(err?.message || 'Error loading bookings', 'error');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchBookings();
  }, [category]);

  const handleCancelBooking = async (ref) => {
    if (!window.confirm(`Are you sure you want to cancel reservation ${ref}? A full simulated refund will be returned to your wallet.`)) {
      return;
    }

    setCancellingRef(ref);
    try {
      const res = await travelService.cancelBooking(ref);
      if (res && res.success) {
        addToast(`Reservation cancelled. ₹${res.refund_amount} refunded to your wallet!`, 'success');
        refreshBalance();
        fetchBookings();
      }
    } catch (err) {
      addToast(err?.message || 'Failed to cancel reservation', 'error');
    } finally {
      setCancellingRef(null);
    }
  };

  return (
    <div className="space-y-6 max-w-5xl mx-auto pb-12 animate-in fade-in">
      {/* Banner */}
      <div className="p-6 sm:p-8 bg-[#101D30] text-white rounded-3xl border border-slate-800 shadow-md flex items-center justify-between">
        <div className="space-y-1">
          <span className="text-[10px] font-mono font-bold text-[#35D6B0] uppercase tracking-wider block">
            Travel Reservations & Itineraries
          </span>
          <h1 className="text-2xl sm:text-3xl font-black">My Bookings</h1>
          <p className="text-xs text-slate-300">Unified passbook for Train, Bus, Flight & Hotel reservations with atomic refunds.</p>
        </div>
        <div className="w-12 h-12 rounded-2xl bg-white/10 text-[#35D6B0] flex items-center justify-center shrink-0">
          <Briefcase className="w-6 h-6" />
        </div>
      </div>

      {/* Category Tabs */}
      <div className="flex gap-2 overflow-x-auto pb-1">
        {[
          { id: 'all', label: 'All Bookings' },
          { id: 'train', label: 'Trains' },
          { id: 'bus', label: 'Buses' },
          { id: 'flight', label: 'Flights' },
          { id: 'hotel', label: 'Hotels' }
        ].map((tab) => (
          <button
            key={tab.id}
            onClick={() => setCategory(tab.id)}
            className={`px-4 py-2 rounded-2xl text-xs font-bold transition whitespace-nowrap ${
              category === tab.id
                ? 'bg-[#101D30] text-[#35D6B0] shadow-sm'
                : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200/80'
            }`}
          >
            {tab.label}
          </button>
        ))}
      </div>

      {/* Loading */}
      {loading && (
        <div className="p-12 text-center bg-white rounded-3xl border border-gray-100">
          <Loader2 className="w-6 h-6 animate-spin mx-auto text-[#101D30]" />
          <p className="text-xs text-gray-400 mt-2 font-medium">Syncing travel ledger...</p>
        </div>
      )}

      {/* Bookings List */}
      {!loading && bookings.length > 0 && (
        <div className="space-y-4">
          {bookings.map((b) => {
            const Icon = getCategoryIcon(b.category);
            const isCancelled = b.status === 'CANCELLED';
            const isCancelling = cancellingRef === b.booking_reference;

            return (
              <div
                key={b.booking_reference}
                className="bg-white rounded-3xl p-5 border border-gray-200/80 shadow-xs space-y-3"
              >
                <div className="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-gray-100 gap-2">
                  <div className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-2xl bg-[#101D30] text-[#35D6B0] flex items-center justify-center shrink-0">
                      <Icon className="w-5 h-5" />
                    </div>
                    <div>
                      <div className="flex items-center gap-2">
                        <span className="text-[10px] font-mono uppercase font-bold text-gray-400">
                          {b.category}
                        </span>
                        <span>•</span>
                        <span className="text-[11px] font-mono font-bold text-blue-700">
                          PNR: {b.pnr_or_ticket_number}
                        </span>
                      </div>
                      <h3 className="text-sm sm:text-base font-extrabold text-[#101D30]">
                        {b.title}
                      </h3>
                    </div>
                  </div>

                  <div className="flex items-center gap-3">
                    <div className="text-right">
                      <span className="text-sm font-black font-mono text-[#101D30]">
                        ₹{parseFloat(b.amount).toFixed(2)}
                      </span>
                      <span className="text-[10px] text-gray-400 block font-mono">
                        {b.booking_reference}
                      </span>
                    </div>

                    {isCancelled ? (
                      <span className="px-2.5 py-1 rounded-full bg-rose-50 text-rose-700 border border-rose-200 text-[10px] font-bold flex items-center gap-1">
                        <XCircle className="w-3 h-3" />
                        <span>CANCELLED</span>
                      </span>
                    ) : (
                      <span className="px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold flex items-center gap-1">
                        <CheckCircle2 className="w-3 h-3" />
                        <span>CONFIRMED</span>
                      </span>
                    )}
                  </div>
                </div>

                <div className="flex flex-col sm:flex-row sm:items-center justify-between text-xs text-gray-600 gap-2">
                  <div className="flex items-center gap-4">
                    <div>
                      <span className="text-[10px] text-gray-400 uppercase block">Route</span>
                      <span className="font-bold text-[#101D30]">{b.source_city} → {b.destination_city}</span>
                    </div>
                    <div>
                      <span className="text-[10px] text-gray-400 uppercase block">Travel Date</span>
                      <span className="font-bold text-[#101D30]">{b.travel_date}</span>
                    </div>
                    <div>
                      <span className="text-[10px] text-gray-400 uppercase block">Class / Type</span>
                      <span className="font-bold text-[#101D30]">{b.class_or_room_type}</span>
                    </div>
                  </div>

                  {!isCancelled && (
                    <button
                      onClick={() => handleCancelBooking(b.booking_reference)}
                      disabled={isCancelling}
                      className="px-3.5 py-1.5 rounded-xl border border-rose-200 text-rose-600 hover:bg-rose-50 text-xs font-bold transition self-start sm:self-auto disabled:opacity-50"
                    >
                      {isCancelling ? 'Processing Refund...' : 'Cancel & Refund'}
                    </button>
                  )}
                </div>

                {/* Passenger list */}
                {b.passengers && b.passengers.length > 0 && (
                  <div className="pt-2 border-t border-gray-50 flex items-center gap-2 text-[11px] text-gray-500 font-mono">
                    <span>Passengers:</span>
                    {b.passengers.map((p, idx) => (
                      <span key={idx} className="bg-gray-100 px-2 py-0.5 rounded-md font-semibold text-gray-700">
                        {p.name} ({p.gender?.charAt(0)}, {p.age}y) • Seat {p.seat_number}
                      </span>
                    ))}
                  </div>
                )}
              </div>
            );
          })}
        </div>
      )}

      {/* Empty State */}
      {!loading && bookings.length === 0 && (
        <div className="text-center py-12 px-4 bg-white rounded-3xl border border-dashed border-gray-200 space-y-3">
          <div className="w-12 h-12 rounded-2xl bg-[#F5F7FA] text-gray-400 flex items-center justify-center mx-auto">
            <Briefcase className="w-6 h-6" />
          </div>
          <div>
            <h3 className="text-sm font-bold text-[#101D30]">No Travel Reservations Yet</h3>
            <p className="text-xs text-gray-500 mt-1 max-w-sm mx-auto">
              Plan your next trip with train, bus, flight or hotel tickets using your PayBen sandbox wallet balance.
            </p>
          </div>
          <div className="flex justify-center gap-2 pt-2">
            <Link
              to="/travel/trains"
              className="px-4 py-2 rounded-xl bg-[#101D30] text-[#35D6B0] text-xs font-bold hover:bg-[#1a2f4d] transition"
            >
              Book Trains
            </Link>
            <Link
              to="/travel/flights"
              className="px-4 py-2 rounded-xl bg-[#F5F7FA] text-[#101D30] text-xs font-bold hover:bg-gray-100 transition"
            >
              Book Flights
            </Link>
          </div>
        </div>
      )}
    </div>
  );
};

export default MyBookings;
