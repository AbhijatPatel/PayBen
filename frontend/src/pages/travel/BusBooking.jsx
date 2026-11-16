import React, { useState, useEffect } from 'react';
import { travelService } from '../../services/travelService';
import { useAuth } from '../../context/AuthContext';
import { useToast } from '../../context/ToastContext';
import {
  Bus,
  Calendar,
  Search,
  CheckCircle2,
  Clock,
  ExternalLink,
  Loader2,
  ShieldCheck,
  Star,
  MapPin
} from 'lucide-react';

export const BusBooking = () => {
  const { wallet, refreshBalance } = useAuth();
  const { addToast } = useToast();

  const [from, setFrom] = useState('Delhi');
  const [to, setTo] = useState('Jaipur');
  const [date, setDate] = useState(new Date(Date.now() + 86400000).toISOString().split('T')[0]);

  const [loading, setLoading] = useState(false);
  const [buses, setBuses] = useState([]);
  const [selectedBus, setSelectedBus] = useState(null);
  const [selectedSeat, setSelectedSeat] = useState('14U');
  const [passengerName, setPassengerName] = useState('Rahul Sharma');
  const [bookingInProgress, setBookingInProgress] = useState(false);
  const [confirmedBooking, setConfirmedBooking] = useState(null);

  const handleSearch = async (e) => {
    if (e) e.preventDefault();
    setLoading(true);
    setSelectedBus(null);
    try {
      const res = await travelService.searchBuses(from, to, date);
      if (res && res.success) {
        setBuses(res.buses || []);
      }
    } catch (err) {
      addToast(err?.message || 'Error loading buses', 'error');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    handleSearch();
  }, []);

  const handleBookBus = async () => {
    if (!selectedBus) return;
    const fare = parseFloat(selectedBus.fare);
    const balance = parseFloat(wallet?.balance || 0);

    if (fare > balance) {
      addToast('Insufficient wallet balance. Please add cash to your wallet.', 'error');
      return;
    }

    setBookingInProgress(true);
    try {
      const res = await travelService.createBooking({
        category: 'bus',
        provider_name: selectedBus.operator_name,
        title: `${selectedBus.operator_name} (${selectedBus.bus_type})`,
        source_city: from,
        destination_city: to,
        travel_date: date,
        class_or_room_type: 'AC Sleeper',
        amount: fare,
        passengers: [
          {
            name: passengerName.trim() || 'Passenger',
            age: 26,
            gender: 'MALE',
            seat_number: selectedSeat
          }
        ]
      });

      if (res && res.success) {
        setConfirmedBooking(res.data);
        refreshBalance();
        addToast('Bus seat booked successfully in Sandbox!', 'success');
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
            SEAT CONFIRMED • SANDBOX
          </span>
          <h2 className="text-xl font-extrabold text-[#101D30] mt-2">Bus Ticket Issued</h2>
        </div>

        <div className="p-4 bg-[#F5F7FA] rounded-2xl text-left text-xs font-mono space-y-2 border border-gray-100">
          <div className="flex justify-between">
            <span className="text-gray-500">Ticket No:</span>
            <span className="font-extrabold text-blue-700">{confirmedBooking.pnr}</span>
          </div>
          <div className="flex justify-between">
            <span className="text-gray-500">Operator:</span>
            <span className="font-bold text-[#101D30]">{confirmedBooking.title}</span>
          </div>
          <div className="flex justify-between">
            <span className="text-gray-500">Route:</span>
            <span className="font-bold text-[#101D30]">{confirmedBooking.source_city} → {confirmedBooking.destination_city}</span>
          </div>
          <div className="flex justify-between">
            <span className="text-gray-500">Date:</span>
            <span className="font-bold text-[#101D30]">{confirmedBooking.travel_date}</span>
          </div>
          <div className="flex justify-between pt-2 border-t border-gray-200 text-sm">
            <span className="text-gray-700 font-sans font-bold">Fare Paid:</span>
            <span className="font-black text-emerald-600">₹{parseFloat(confirmedBooking.amount).toFixed(2)}</span>
          </div>
        </div>

        <button
          onClick={() => { setConfirmedBooking(null); setSelectedBus(null); }}
          className="w-full py-3 rounded-2xl bg-[#101D30] text-[#35D6B0] font-bold text-xs hover:bg-[#1a2f4d] transition"
        >
          Book Another Bus
        </button>
      </div>
    );
  }

  return (
    <div className="space-y-6 max-w-5xl mx-auto pb-12 animate-in fade-in">
      {/* Banner */}
      <div className="p-6 sm:p-8 bg-[#101D30] text-white rounded-3xl border border-slate-800 shadow-md flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div className="space-y-1">
          <span className="text-[10px] font-mono font-bold text-[#35D6B0] uppercase tracking-wider block">
            Intercity Buses • Verified Operators
          </span>
          <h1 className="text-2xl sm:text-3xl font-black">Bus Ticket Booking</h1>
          <p className="text-xs text-slate-300">IntrCity, Zingbus, VRL Travels & State RTCs with real-time seat booking.</p>
        </div>

        <a
          href="https://www.redbus.in"
          target="_blank"
          rel="noopener noreferrer"
          className="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-2xl bg-white/10 hover:bg-white/15 text-white font-bold text-xs border border-white/15 backdrop-blur-md transition self-start sm:self-auto"
        >
          <span>Official redBus</span>
          <ExternalLink className="w-3.5 h-3.5 text-[#35D6B0]" />
        </a>
      </div>

      {/* Filter Bar */}
      <form onSubmit={handleSearch} className="bg-white rounded-3xl p-5 border border-gray-200/80 shadow-xs grid grid-cols-1 sm:grid-cols-4 gap-3">
        <div>
          <label className="block text-[10px] uppercase font-bold text-gray-500 mb-1">Source City</label>
          <input
            type="text"
            value={from}
            onChange={(e) => setFrom(e.target.value)}
            className="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs font-bold text-gray-800 bg-[#F5F7FA]"
          />
        </div>
        <div>
          <label className="block text-[10px] uppercase font-bold text-gray-500 mb-1">Destination City</label>
          <input
            type="text"
            value={to}
            onChange={(e) => setTo(e.target.value)}
            className="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs font-bold text-gray-800 bg-[#F5F7FA]"
          />
        </div>
        <div>
          <label className="block text-[10px] uppercase font-bold text-gray-500 mb-1">Date</label>
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
            {loading ? <Loader2 className="w-4 h-4 animate-spin" /> : <><Search className="w-4 h-4" /><span>Search Buses</span></>}
          </button>
        </div>
      </form>

      {/* Bus Cards */}
      <div className="space-y-4">
        {buses.map((bus) => {
          const isSelected = selectedBus?.bus_id === bus.bus_id;
          return (
            <div
              key={bus.bus_id}
              className={`p-5 rounded-3xl border transition-all ${
                isSelected ? 'border-[#35D6B0] bg-[#F5F7FA] shadow-md' : 'border-gray-200/80 bg-white shadow-xs'
              }`}
            >
              <div className="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-gray-100 gap-2">
                <div>
                  <div className="flex items-center gap-2">
                    <h3 className="text-base font-extrabold text-[#101D30]">{bus.operator_name}</h3>
                    <span className="flex items-center gap-0.5 px-2 py-0.2 rounded-md bg-emerald-50 text-emerald-700 text-[10px] font-bold border border-emerald-200">
                      <Star className="w-2.5 h-2.5 fill-emerald-600 text-emerald-600" />
                      <span>{bus.rating}</span>
                    </span>
                  </div>
                  <p className="text-xs text-gray-500 mt-0.5">{bus.bus_type}</p>
                </div>

                <div className="flex items-center gap-4 text-xs font-mono font-bold">
                  <div>
                    <span className="text-sm font-black text-[#101D30]">{bus.departure_time}</span>
                    <span className="text-[10px] text-gray-400 block">{from}</span>
                  </div>
                  <span className="text-[10px] text-gray-400 px-2 py-0.5 bg-gray-100 rounded-full">{bus.duration}</span>
                  <div>
                    <span className="text-sm font-black text-[#101D30]">{bus.arrival_time}</span>
                    <span className="text-[10px] text-gray-400 block">{to}</span>
                  </div>
                </div>
              </div>

              <div className="pt-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div className="flex flex-wrap gap-2 text-[10px] text-gray-500">
                  <span className="px-2 py-1 bg-gray-100 rounded-lg">Boarding: {bus.boarding_points[0]}</span>
                  <span className="px-2 py-1 bg-gray-100 rounded-lg">{bus.seats_available} Seats Available</span>
                </div>

                <div className="flex items-center gap-3">
                  <div className="text-right">
                    <span className="text-lg font-black font-mono text-[#101D30]">₹{bus.fare.toFixed(2)}</span>
                  </div>
                  <button
                    onClick={() => setSelectedBus(isSelected ? null : bus)}
                    className="px-4 py-2 rounded-xl bg-[#101D30] text-[#35D6B0] hover:bg-[#1a2f4d] font-extrabold text-xs transition"
                  >
                    {isSelected ? 'Selected' : 'Select Seat'}
                  </button>
                </div>
              </div>

              {/* Seat Selection Panel */}
              {isSelected && (
                <div className="mt-4 pt-4 border-t border-gray-200 space-y-3 animate-in fade-in">
                  <div className="flex items-center justify-between">
                    <span className="text-xs font-bold text-[#101D30]">Choose Seat Number:</span>
                    <div className="flex gap-2">
                      {['12L', '14U', '16L', '18U', '20L'].map((s) => (
                        <button
                          key={s}
                          type="button"
                          onClick={() => setSelectedSeat(s)}
                          className={`w-9 h-9 rounded-xl font-mono text-xs font-bold border transition ${
                            selectedSeat === s
                              ? 'bg-[#101D30] text-[#35D6B0] border-[#35D6B0]'
                              : 'bg-white text-gray-700 border-gray-300'
                          }`}
                        >
                          {s}
                        </button>
                      ))}
                    </div>
                  </div>

                  <div className="flex flex-col sm:flex-row items-center justify-between gap-2 pt-2">
                    <input
                      type="text"
                      value={passengerName}
                      onChange={(e) => setPassengerName(e.target.value)}
                      placeholder="Passenger Name"
                      className="w-full sm:w-64 px-3 py-2 rounded-xl border border-gray-200 text-xs font-bold text-gray-800 bg-white"
                    />
                    <button
                      onClick={handleBookBus}
                      disabled={bookingInProgress}
                      className="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs shadow-xs transition disabled:opacity-50"
                    >
                      {bookingInProgress ? <Loader2 className="w-4 h-4 animate-spin" /> : <span>Confirm & Pay ₹{bus.fare.toFixed(2)}</span>}
                    </button>
                  </div>
                </div>
              )}
            </div>
          );
        })}
      </div>
    </div>
  );
};

export default BusBooking;
