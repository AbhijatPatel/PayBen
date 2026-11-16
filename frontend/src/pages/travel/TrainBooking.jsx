import React, { useState, useEffect } from 'react';
import { travelService } from '../../services/travelService';
import { useAuth } from '../../context/AuthContext';
import { useToast } from '../../context/ToastContext';
import {
  Train,
  Calendar,
  Search,
  CheckCircle2,
  Clock,
  ExternalLink,
  Loader2,
  User,
  ShieldCheck,
  ArrowRight,
  Sparkles
} from 'lucide-react';

export const TrainBooking = () => {
  const { wallet, refreshBalance } = useAuth();
  const { addToast } = useToast();

  const [from, setFrom] = useState('NDLS - New Delhi');
  const [to, setTo] = useState('BSB - Varanasi Junction');
  const [date, setDate] = useState(new Date(Date.now() + 86400000).toISOString().split('T')[0]);
  const [quota, setQuota] = useState('GN');

  const [loading, setLoading] = useState(false);
  const [trains, setTrains] = useState([]);
  const [selectedTrain, setSelectedTrain] = useState(null);
  const [selectedClass, setSelectedClass] = useState(null);

  // Passenger Modal / State
  const [passengerName, setPassengerName] = useState('Rahul Sharma');
  const [passengerAge, setPassengerAge] = useState('28');
  const [passengerGender, setPassengerGender] = useState('MALE');
  const [berthPref, setBerthPref] = useState('LOWER');
  const [bookingInProgress, setBookingInProgress] = useState(false);
  const [confirmedBooking, setConfirmedBooking] = useState(null);

  const handleSearch = async (e) => {
    if (e) e.preventDefault();
    setLoading(true);
    setSelectedTrain(null);
    setSelectedClass(null);
    try {
      const res = await travelService.searchTrains(from, to, date, quota);
      if (res && res.success) {
        setTrains(res.trains || []);
        addToast(`Found ${res.trains?.length || 0} trains available`, 'info');
      }
    } catch (err) {
      addToast(err?.message || 'Failed to search trains', 'error');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    handleSearch();
  }, []);

  const handleBookTicket = async () => {
    if (!selectedTrain || !selectedClass) return;
    const fare = parseFloat(selectedClass.fare);
    const balance = parseFloat(wallet?.balance || 0);

    if (fare > balance) {
      addToast('Insufficient wallet balance. Please add cash to your PayBen wallet.', 'error');
      return;
    }

    setBookingInProgress(true);
    try {
      const res = await travelService.createBooking({
        category: 'train',
        provider_name: 'Indian Railways (IRCTC Sandbox)',
        title: `${selectedTrain.train_name} (${selectedTrain.train_number})`,
        source_city: from,
        destination_city: to,
        travel_date: date,
        class_or_room_type: selectedClass.code,
        amount: fare,
        passengers: [
          {
            name: passengerName.trim() || 'Passenger',
            age: parseInt(passengerAge) || 28,
            gender: passengerGender,
            berth_or_seat_preference: berthPref,
            seat_number: 'B3-42 (Confirmed)'
          }
        ]
      });

      if (res && res.success) {
        setConfirmedBooking(res.data);
        refreshBalance();
        addToast('Train reservation confirmed in Sandbox mode!', 'success');
      }
    } catch (err) {
      addToast(err?.message || 'Booking failed', 'error');
    } finally {
      setBookingInProgress(false);
    }
  };

  if (confirmedBooking) {
    return (
      <div className="max-w-lg mx-auto bg-white rounded-3xl p-6 border border-gray-200/80 shadow-md text-center space-y-5 animate-in zoom-in-95">
        <div className="w-16 h-16 rounded-3xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto shadow-inner">
          <CheckCircle2 className="w-10 h-10" />
        </div>
        <div>
          <span className="text-[10px] font-mono font-bold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">
            CONFIRMED • SANDBOX TICKET
          </span>
          <h2 className="text-xl font-extrabold text-[#101D30] mt-2">IRCTC Electronic Ticket</h2>
          <p className="text-xs text-gray-500">Atomic ledger settlement verified</p>
        </div>

        <div className="p-4 bg-[#F5F7FA] rounded-2xl text-left text-xs font-mono space-y-2 border border-gray-100">
          <div className="flex justify-between">
            <span className="text-gray-500">PNR Number:</span>
            <span className="font-extrabold text-blue-700">{confirmedBooking.pnr}</span>
          </div>
          <div className="flex justify-between">
            <span className="text-gray-500">Booking Ref:</span>
            <span className="font-bold text-[#101D30]">{confirmedBooking.booking_reference}</span>
          </div>
          <div className="flex justify-between">
            <span className="text-gray-500">Train:</span>
            <span className="font-bold text-[#101D30] truncate max-w-[220px]">{confirmedBooking.title}</span>
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

        <div className="p-3 bg-amber-50 rounded-xl border border-amber-200 text-[11px] text-amber-800 text-left">
          <strong>Notice:</strong> This is an educational sandbox ticket demonstration. For commercial rail travel, please use the official Indian Railways IRCTC website.
        </div>

        <button
          onClick={() => { setConfirmedBooking(null); setSelectedTrain(null); }}
          className="w-full py-3 rounded-2xl bg-[#101D30] text-[#35D6B0] font-bold text-xs hover:bg-[#1a2f4d] transition"
        >
          Book Another Ticket
        </button>
      </div>
    );
  }

  return (
    <div className="space-y-6 max-w-5xl mx-auto pb-12 animate-in fade-in">
      {/* Top Banner */}
      <div className="p-6 sm:p-8 bg-[#101D30] text-white rounded-3xl border border-slate-800 shadow-md flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div className="space-y-1">
          <div className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-950/80 text-[#35D6B0] text-[10px] font-mono font-bold border border-[#35D6B0]/30">
            <span className="w-1.5 h-1.5 rounded-full bg-[#35D6B0] animate-pulse" />
            <span>INDIAN RAILWAYS SANDBOX MODULE</span>
          </div>
          <h1 className="text-2xl sm:text-3xl font-black">Train Ticket Booking</h1>
          <p className="text-xs text-slate-300 max-w-xl">
            Search Vande Bharat, Rajdhani, Shatabdi & Superfast trains. Instant wallet confirmation with PNR generation.
          </p>
        </div>

        <a
          href="https://www.irctc.co.in/nget/train-search"
          target="_blank"
          rel="noopener noreferrer"
          className="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-2xl bg-white/10 hover:bg-white/15 text-white font-bold text-xs border border-white/15 backdrop-blur-md transition self-start sm:self-auto"
        >
          <span>Official IRCTC</span>
          <ExternalLink className="w-3.5 h-3.5 text-[#35D6B0]" />
        </a>
      </div>

      {/* Search Filter Bar */}
      <form onSubmit={handleSearch} className="bg-white rounded-3xl p-5 border border-gray-200/80 shadow-xs grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
        <div>
          <label className="block text-[10px] uppercase font-bold text-gray-500 mb-1">From Station</label>
          <input
            type="text"
            value={from}
            onChange={(e) => setFrom(e.target.value)}
            className="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs font-bold text-gray-800 bg-[#F5F7FA] outline-none focus:border-[#35D6B0]"
          />
        </div>

        <div>
          <label className="block text-[10px] uppercase font-bold text-gray-500 mb-1">To Station</label>
          <input
            type="text"
            value={to}
            onChange={(e) => setTo(e.target.value)}
            className="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs font-bold text-gray-800 bg-[#F5F7FA] outline-none focus:border-[#35D6B0]"
          />
        </div>

        <div>
          <label className="block text-[10px] uppercase font-bold text-gray-500 mb-1">Travel Date</label>
          <input
            type="date"
            value={date}
            onChange={(e) => setDate(e.target.value)}
            className="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs font-bold text-gray-800 bg-[#F5F7FA] outline-none focus:border-[#35D6B0]"
          />
        </div>

        <div>
          <label className="block text-[10px] uppercase font-bold text-gray-500 mb-1">Quota</label>
          <select
            value={quota}
            onChange={(e) => setQuota(e.target.value)}
            className="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs font-bold text-gray-800 bg-[#F5F7FA] outline-none focus:border-[#35D6B0]"
          >
            <option value="GN">General (GN)</option>
            <option value="TQ">Tatkal (TQ)</option>
            <option value="LD">Ladies (LD)</option>
          </select>
        </div>

        <div className="flex items-end">
          <button
            type="submit"
            disabled={loading}
            className="w-full py-2.5 bg-[#101D30] hover:bg-[#1a2f4d] disabled:opacity-50 text-[#35D6B0] font-extrabold text-xs rounded-xl flex items-center justify-center gap-1.5 transition shadow-xs"
          >
            {loading ? <Loader2 className="w-4 h-4 animate-spin" /> : <><Search className="w-4 h-4" /><span>Search Trains</span></>}
          </button>
        </div>
      </form>

      {/* Train Results List */}
      <div className="space-y-4">
        {trains.map((train) => (
          <div
            key={train.train_number}
            className="bg-white rounded-3xl p-5 border border-gray-200/80 shadow-xs space-y-4 hover:border-gray-300 transition-all"
          >
            {/* Top row: Train Name & Timing */}
            <div className="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-gray-100 gap-2">
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-2xl bg-[#101D30] text-[#35D6B0] flex items-center justify-center shrink-0">
                  <Train className="w-5 h-5" />
                </div>
                <div>
                  <h3 className="text-base font-extrabold text-[#101D30]">
                    {train.train_name} <span className="font-mono text-gray-400 font-normal">#{train.train_number}</span>
                  </h3>
                  <p className="text-[11px] text-gray-400">
                    Runs on: {train.running_days.join(', ')}
                  </p>
                </div>
              </div>

              <div className="flex items-center gap-4 text-xs font-mono font-bold text-gray-700">
                <div className="text-left">
                  <span className="text-sm font-black text-[#101D30]">{train.departure_time}</span>
                  <span className="text-[10px] text-gray-400 block">{train.from_station.split(' - ')[0]}</span>
                </div>
                <div className="text-center px-2 py-0.5 rounded-full bg-gray-100 text-[10px] text-gray-500">
                  {train.duration}
                </div>
                <div className="text-right">
                  <span className="text-sm font-black text-[#101D30]">{train.arrival_time}</span>
                  <span className="text-[10px] text-gray-400 block">{train.to_station.split(' - ')[0]}</span>
                </div>
              </div>
            </div>

            {/* Classes & Fares Carousel / Grid */}
            <div className="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
              {train.classes.map((cls) => {
                const isSelected = selectedTrain?.train_number === train.train_number && selectedClass?.code === cls.code;
                return (
                  <button
                    key={cls.code}
                    type="button"
                    onClick={() => {
                      setSelectedTrain(train);
                      setSelectedClass(cls);
                    }}
                    className={`p-3 rounded-2xl border text-left transition-all ${
                      isSelected
                        ? 'border-[#35D6B0] bg-[#101D30] text-white shadow-sm ring-2 ring-[#35D6B0]/30'
                        : 'border-gray-200 bg-[#F5F7FA] hover:bg-gray-100 text-gray-800'
                    }`}
                  >
                    <div className="flex justify-between items-center mb-1">
                      <span className={`font-mono text-xs font-black ${isSelected ? 'text-[#35D6B0]' : 'text-blue-700'}`}>
                        {cls.code}
                      </span>
                      <span className={`font-mono font-bold text-xs ${isSelected ? 'text-white' : 'text-gray-900'}`}>
                        ₹{cls.fare.toFixed(0)}
                      </span>
                    </div>
                    <div className="text-[10px] font-bold text-emerald-600 truncate">
                      {cls.availability}
                    </div>
                  </button>
                );
              })}
            </div>
          </div>
        ))}
      </div>

      {/* Selected Class Booking Confirmation Drawer */}
      {selectedTrain && selectedClass && (
        <div className="bg-white rounded-3xl p-6 border-2 border-[#35D6B0] shadow-xl space-y-4 animate-in slide-in-from-bottom-4">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-gray-100 gap-2">
            <div>
              <span className="text-[10px] font-mono font-bold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full">
                RESERVATION SELECTION
              </span>
              <h3 className="text-base font-extrabold text-[#101D30] mt-1">
                {selectedTrain.train_name} • {selectedClass.name} ({selectedClass.code})
              </h3>
            </div>
            <div className="text-right">
              <span className="text-[10px] uppercase font-bold text-gray-400 block">Total Fare</span>
              <span className="text-2xl font-black font-mono text-emerald-600">
                ₹{selectedClass.fare.toFixed(2)}
              </span>
            </div>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div>
              <label className="block text-[10px] uppercase font-bold text-gray-500 mb-1">Passenger Full Name</label>
              <input
                type="text"
                value={passengerName}
                onChange={(e) => setPassengerName(e.target.value)}
                className="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs font-bold text-gray-800 bg-[#F5F7FA]"
              />
            </div>
            <div>
              <label className="block text-[10px] uppercase font-bold text-gray-500 mb-1">Age</label>
              <input
                type="number"
                value={passengerAge}
                onChange={(e) => setPassengerAge(e.target.value)}
                className="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs font-bold text-gray-800 bg-[#F5F7FA]"
              />
            </div>
            <div>
              <label className="block text-[10px] uppercase font-bold text-gray-500 mb-1">Gender</label>
              <select
                value={passengerGender}
                onChange={(e) => setPassengerGender(e.target.value)}
                className="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs font-bold text-gray-800 bg-[#F5F7FA]"
              >
                <option value="MALE">Male</option>
                <option value="FEMALE">Female</option>
                <option value="OTHER">Other</option>
              </select>
            </div>
            <div>
              <label className="block text-[10px] uppercase font-bold text-gray-500 mb-1">Berth Preference</label>
              <select
                value={berthPref}
                onChange={(e) => setBerthPref(e.target.value)}
                className="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs font-bold text-gray-800 bg-[#F5F7FA]"
              >
                <option value="LOWER">Lower Berth</option>
                <option value="MIDDLE">Middle Berth</option>
                <option value="UPPER">Upper Berth</option>
                <option value="SIDE_LOWER">Side Lower</option>
              </select>
            </div>
          </div>

          <div className="pt-2 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div className="flex items-center gap-1.5 text-[11px] text-gray-400">
              <ShieldCheck className="w-4 h-4 text-emerald-600" />
              <span>Simulated Sandbox booking • Zero real money charged</span>
            </div>

            <button
              onClick={handleBookTicket}
              disabled={bookingInProgress}
              className="w-full sm:w-auto px-6 py-3 rounded-xl bg-[#101D30] hover:bg-[#1a2f4d] text-[#35D6B0] font-extrabold text-xs sm:text-sm shadow-md transition flex items-center justify-center gap-2 active:scale-95 disabled:opacity-50"
            >
              {bookingInProgress ? <Loader2 className="w-4 h-4 animate-spin" /> : <span>Confirm & Pay from Wallet</span>}
            </button>
          </div>
        </div>
      )}
    </div>
  );
};

export default TrainBooking;
