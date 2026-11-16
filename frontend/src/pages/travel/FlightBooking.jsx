import React, { useState, useEffect } from 'react';
import { travelService } from '../../services/travelService';
import { useAuth } from '../../context/AuthContext';
import { useToast } from '../../context/ToastContext';
import {
  Plane,
  Calendar,
  Search,
  CheckCircle2,
  Clock,
  Loader2,
  ShieldCheck,
  Luggage,
  Sparkles
} from 'lucide-react';

export const FlightBooking = () => {
  const { wallet, refreshBalance } = useAuth();
  const { addToast } = useToast();

  const [from, setFrom] = useState('DEL - New Delhi');
  const [to, setTo] = useState('BOM - Mumbai');
  const [date, setDate] = useState(new Date(Date.now() + 259200000).toISOString().split('T')[0]);

  const [loading, setLoading] = useState(false);
  const [flights, setFlights] = useState([]);
  const [selectedFlight, setSelectedFlight] = useState(null);
  const [passengerName, setPassengerName] = useState('Rahul Sharma');
  const [bookingInProgress, setBookingInProgress] = useState(false);
  const [confirmedBooking, setConfirmedBooking] = useState(null);

  const handleSearch = async (e) => {
    if (e) e.preventDefault();
    setLoading(true);
    setSelectedFlight(null);
    try {
      const res = await travelService.searchFlights(from, to, date);
      if (res && res.success) {
        setFlights(res.flights || []);
      }
    } catch (err) {
      addToast(err?.message || 'Error searching flights', 'error');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    handleSearch();
  }, []);

  const handleBookFlight = async (flight) => {
    const fare = parseFloat(flight.fare);
    const balance = parseFloat(wallet?.balance || 0);

    if (fare > balance) {
      addToast('Insufficient wallet balance. Please add cash to your wallet.', 'error');
      return;
    }

    setBookingInProgress(true);
    try {
      const res = await travelService.createBooking({
        category: 'flight',
        provider_name: flight.airline,
        title: `${flight.airline} (${flight.flight_number})`,
        source_city: from,
        destination_city: to,
        travel_date: date,
        class_or_room_type: 'Economy',
        amount: fare,
        passengers: [
          {
            name: passengerName.trim() || 'Passenger',
            age: 30,
            gender: 'MALE',
            seat_number: '18F (Window)'
          }
        ]
      });

      if (res && res.success) {
        setConfirmedBooking(res.data);
        refreshBalance();
        addToast('Flight ticket reserved successfully in Sandbox mode!', 'success');
      }
    } catch (err) {
      addToast(err?.message || 'Booking failed', 'error');
    } finally {
      setBookingInProgress(false);
    }
  };

  if (confirmedBooking) {
    return (
      <div className="max-w-md mx-auto bg-white rounded-3xl p-6 border border-gray-200/80 shadow-md text-center space-y-4 animate-in zoom-in-95">
        <div className="w-16 h-16 rounded-3xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto shadow-inner">
          <CheckCircle2 className="w-10 h-10" />
        </div>
        <div>
          <span className="text-[10px] font-mono font-bold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">
            E-TICKET ISSUED • SANDBOX
          </span>
          <h2 className="text-xl font-extrabold text-[#101D30] mt-2">Flight Boarding Pass Ready</h2>
        </div>

        <div className="p-4 bg-[#F5F7FA] rounded-2xl text-left text-xs font-mono space-y-2 border border-gray-100">
          <div className="flex justify-between">
            <span className="text-gray-500">Ticket Ref / PNR:</span>
            <span className="font-extrabold text-blue-700">{confirmedBooking.pnr}</span>
          </div>
          <div className="flex justify-between">
            <span className="text-gray-500">Flight:</span>
            <span className="font-bold text-[#101D30]">{confirmedBooking.title}</span>
          </div>
          <div className="flex justify-between">
            <span className="text-gray-500">Sector:</span>
            <span className="font-bold text-[#101D30]">{confirmedBooking.source_city} → {confirmedBooking.destination_city}</span>
          </div>
          <div className="flex justify-between">
            <span className="text-gray-500">Departure:</span>
            <span className="font-bold text-[#101D30]">{confirmedBooking.travel_date}</span>
          </div>
          <div className="flex justify-between pt-2 border-t border-gray-200 text-sm">
            <span className="text-gray-700 font-sans font-bold">Total Fare:</span>
            <span className="font-black text-emerald-600">₹{parseFloat(confirmedBooking.amount).toFixed(2)}</span>
          </div>
        </div>

        <button
          onClick={() => { setConfirmedBooking(null); setSelectedFlight(null); }}
          className="w-full py-3 rounded-2xl bg-[#101D30] text-[#35D6B0] font-bold text-xs hover:bg-[#1a2f4d] transition"
        >
          Book Another Flight
        </button>
      </div>
    );
  }

  return (
    <div className="space-y-6 max-w-5xl mx-auto pb-12 animate-in fade-in">
      {/* Top Banner */}
      <div className="p-6 sm:p-8 bg-[#101D30] text-white rounded-3xl border border-slate-800 shadow-md flex items-center justify-between">
        <div className="space-y-1">
          <span className="text-[10px] font-mono font-bold text-[#35D6B0] uppercase tracking-wider block">
            Domestic & International • Sandbox Simulator
          </span>
          <h1 className="text-2xl sm:text-3xl font-black">Flight Ticket Booking</h1>
          <p className="text-xs text-slate-300">IndiGo, Air India, Vistara & Akasa Air with zero-convenience fee sandbox checkout.</p>
        </div>
        <div className="w-12 h-12 rounded-2xl bg-white/10 text-[#35D6B0] flex items-center justify-center shrink-0">
          <Plane className="w-6 h-6" />
        </div>
      </div>

      {/* Flight Search Bar */}
      <form onSubmit={handleSearch} className="bg-white rounded-3xl p-5 border border-gray-200/80 shadow-xs grid grid-cols-1 sm:grid-cols-4 gap-3">
        <div>
          <label className="block text-[10px] uppercase font-bold text-gray-500 mb-1">Departure Airport</label>
          <input
            type="text"
            value={from}
            onChange={(e) => setFrom(e.target.value)}
            className="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs font-bold text-gray-800 bg-[#F5F7FA]"
          />
        </div>
        <div>
          <label className="block text-[10px] uppercase font-bold text-gray-500 mb-1">Arrival Airport</label>
          <input
            type="text"
            value={to}
            onChange={(e) => setTo(e.target.value)}
            className="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs font-bold text-gray-800 bg-[#F5F7FA]"
          />
        </div>
        <div>
          <label className="block text-[10px] uppercase font-bold text-gray-500 mb-1">Travel Date</label>
          <input
            type="date"
            value={date}
            onChange={(e) => setDate(e.target.value)}
            className="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs font-bold text-gray-800 bg-[#F5F7FA]"
          />
        </div>
        <div className="flex items-end">
          <button
            type="submit"
            disabled={loading}
            className="w-full py-2.5 bg-[#101D30] hover:bg-[#1a2f4d] disabled:opacity-50 text-[#35D6B0] font-extrabold text-xs rounded-xl flex items-center justify-center gap-1.5 transition shadow-xs"
          >
            {loading ? <Loader2 className="w-4 h-4 animate-spin" /> : <><Search className="w-4 h-4" /><span>Search Flights</span></>}
          </button>
        </div>
      </form>

      {/* Flight Results */}
      <div className="space-y-4">
        {flights.map((f) => (
          <div
            key={f.flight_id}
            className="p-5 bg-white rounded-3xl border border-gray-200/80 shadow-xs hover:border-[#35D6B0]/60 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-4"
          >
            <div className="flex items-center gap-3">
              <div className="w-12 h-12 rounded-2xl bg-[#101D30] text-[#35D6B0] flex items-center justify-center shrink-0">
                <Plane className="w-6 h-6" />
              </div>
              <div>
                <h3 className="text-base font-extrabold text-[#101D30]">{f.airline}</h3>
                <span className="text-xs text-gray-400 font-mono">{f.flight_number} • {f.aircraft}</span>
              </div>
            </div>

            <div className="flex items-center gap-4 text-xs font-mono font-bold text-gray-700">
              <div className="text-left">
                <span className="text-sm font-black text-[#101D30]">{f.departure_time}</span>
                <span className="text-[10px] text-gray-400 block">{from.split(' - ')[0]}</span>
              </div>
              <div className="text-center px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700 text-[10px]">
                {f.duration} ({f.stops})
              </div>
              <div className="text-right">
                <span className="text-sm font-black text-[#101D30]">{f.arrival_time}</span>
                <span className="text-[10px] text-gray-400 block">{to.split(' - ')[0]}</span>
              </div>
            </div>

            <div className="flex items-center justify-between sm:justify-end gap-4 border-t sm:border-t-0 pt-3 sm:pt-0">
              <div className="text-right">
                <span className="text-lg font-black font-mono text-emerald-600">₹{f.fare.toFixed(2)}</span>
                <span className="text-[10px] text-gray-400 block">{f.seats_left} seats left</span>
              </div>

              <button
                onClick={() => handleBookFlight(f)}
                disabled={bookingInProgress}
                className="px-5 py-2.5 rounded-xl bg-[#101D30] hover:bg-[#1a2f4d] text-[#35D6B0] font-extrabold text-xs shadow-xs transition active:scale-95 disabled:opacity-50"
              >
                {bookingInProgress ? <Loader2 className="w-4 h-4 animate-spin" /> : <span>Book Flight</span>}
              </button>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

export default FlightBooking;
