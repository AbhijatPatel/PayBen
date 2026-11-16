import React, { useState, useEffect } from 'react';
import { travelService } from '../../services/travelService';
import { useAuth } from '../../context/AuthContext';
import { useToast } from '../../context/ToastContext';
import {
  Building2,
  Calendar,
  Search,
  CheckCircle2,
  Star,
  MapPin,
  Loader2,
  ShieldCheck,
  Wifi,
  Coffee
} from 'lucide-react';

export const HotelBooking = () => {
  const { wallet, refreshBalance } = useAuth();
  const { addToast } = useToast();

  const [city, setCity] = useState('Goa');
  const [checkin, setCheckin] = useState(new Date(Date.now() + 172800000).toISOString().split('T')[0]);
  const [checkout, setCheckout] = useState(new Date(Date.now() + 345600000).toISOString().split('T')[0]);

  const [loading, setLoading] = useState(false);
  const [hotels, setHotels] = useState([]);
  const [guestName, setGuestName] = useState('Rahul Sharma');
  const [bookingInProgress, setBookingInProgress] = useState(false);
  const [confirmedBooking, setConfirmedBooking] = useState(null);

  const handleSearch = async (e) => {
    if (e) e.preventDefault();
    setLoading(true);
    try {
      const res = await travelService.searchHotels(city, checkin, checkout);
      if (res && res.success) {
        setHotels(res.hotels || []);
      }
    } catch (err) {
      addToast(err?.message || 'Error searching hotels', 'error');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    handleSearch();
  }, []);

  const handleBookHotel = async (hotel) => {
    const fare = parseFloat(hotel.price_per_night) * 2; // 2 nights
    const balance = parseFloat(wallet?.balance || 0);

    if (fare > balance) {
      addToast('Insufficient wallet balance. Please add cash to your wallet.', 'error');
      return;
    }

    setBookingInProgress(true);
    try {
      const res = await travelService.createBooking({
        category: 'hotel',
        provider_name: hotel.name,
        title: `${hotel.name} (2 Nights Stay)`,
        source_city: city,
        destination_city: hotel.location,
        travel_date: checkin,
        return_date: checkout,
        class_or_room_type: hotel.room_type,
        amount: fare,
        passengers: [
          {
            name: guestName.trim() || 'Primary Guest',
            age: 28,
            gender: 'MALE',
            seat_number: 'Room 304'
          }
        ]
      });

      if (res && res.success) {
        setConfirmedBooking(res.data);
        refreshBalance();
        addToast('Hotel room reserved successfully in Sandbox mode!', 'success');
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
            ROOM VOUCHER ISSUED • SANDBOX
          </span>
          <h2 className="text-xl font-extrabold text-[#101D30] mt-2">Hotel Check-In Voucher</h2>
        </div>

        <div className="p-4 bg-[#F5F7FA] rounded-2xl text-left text-xs font-mono space-y-2 border border-gray-100">
          <div className="flex justify-between">
            <span className="text-gray-500">Booking Ref:</span>
            <span className="font-extrabold text-blue-700">{confirmedBooking.booking_reference}</span>
          </div>
          <div className="flex justify-between">
            <span className="text-gray-500">Hotel:</span>
            <span className="font-bold text-[#101D30]">{confirmedBooking.title}</span>
          </div>
          <div className="flex justify-between">
            <span className="text-gray-500">Dates:</span>
            <span className="font-bold text-[#101D30]">{confirmedBooking.travel_date}</span>
          </div>
          <div className="flex justify-between pt-2 border-t border-gray-200 text-sm">
            <span className="text-gray-700 font-sans font-bold">Total Paid:</span>
            <span className="font-black text-emerald-600">₹{parseFloat(confirmedBooking.amount).toFixed(2)}</span>
          </div>
        </div>

        <button
          onClick={() => { setConfirmedBooking(null); }}
          className="w-full py-3 rounded-2xl bg-[#101D30] text-[#35D6B0] font-bold text-xs hover:bg-[#1a2f4d] transition"
        >
          Book Another Hotel
        </button>
      </div>
    );
  }

  return (
    <div className="space-y-6 max-w-5xl mx-auto pb-12 animate-in fade-in">
      {/* Banner */}
      <div className="p-6 sm:p-8 bg-[#101D30] text-white rounded-3xl border border-slate-800 shadow-md flex items-center justify-between">
        <div className="space-y-1">
          <span className="text-[10px] font-mono font-bold text-[#35D6B0] uppercase tracking-wider block">
            Resorts, Villas & Boutique Hotels
          </span>
          <h1 className="text-2xl sm:text-3xl font-black">Hotel Reservations</h1>
          <p className="text-xs text-slate-300">Verified properties in Goa, Manali, Jaipur, Mumbai & beyond with instant room vouchers.</p>
        </div>
        <div className="w-12 h-12 rounded-2xl bg-white/10 text-[#35D6B0] flex items-center justify-center shrink-0">
          <Building2 className="w-6 h-6" />
        </div>
      </div>

      {/* Filter Bar */}
      <form onSubmit={handleSearch} className="bg-white rounded-3xl p-5 border border-gray-200/80 shadow-xs grid grid-cols-1 sm:grid-cols-4 gap-3">
        <div>
          <label className="block text-[10px] uppercase font-bold text-gray-500 mb-1">Destination City</label>
          <input
            type="text"
            value={city}
            onChange={(e) => setCity(e.target.value)}
            className="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs font-bold text-gray-800 bg-[#F5F7FA]"
          />
        </div>
        <div>
          <label className="block text-[10px] uppercase font-bold text-gray-500 mb-1">Check-in</label>
          <input
            type="date"
            value={checkin}
            onChange={(e) => setCheckin(e.target.value)}
            className="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs font-bold text-gray-800 bg-[#F5F7FA]"
          />
        </div>
        <div>
          <label className="block text-[10px] uppercase font-bold text-gray-500 mb-1">Check-out</label>
          <input
            type="date"
            value={checkout}
            onChange={(e) => setCheckout(e.target.value)}
            className="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs font-bold text-gray-800 bg-[#F5F7FA]"
          />
        </div>
        <div className="flex items-end">
          <button
            type="submit"
            disabled={loading}
            className="w-full py-2.5 bg-[#101D30] hover:bg-[#1a2f4d] disabled:opacity-50 text-[#35D6B0] font-extrabold text-xs rounded-xl flex items-center justify-center gap-1.5 transition shadow-xs"
          >
            {loading ? <Loader2 className="w-4 h-4 animate-spin" /> : <><Search className="w-4 h-4" /><span>Search Hotels</span></>}
          </button>
        </div>
      </form>

      {/* Hotel Cards */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        {hotels.map((h) => (
          <div
            key={h.hotel_id}
            className="bg-white rounded-3xl border border-gray-200/80 shadow-xs hover:shadow-md transition-all p-5 flex flex-col justify-between"
          >
            <div>
              <div className="flex items-center justify-between mb-2">
                <span className="flex items-center gap-0.5 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-bold border border-emerald-200">
                  <Star className="w-3 h-3 fill-emerald-600 text-emerald-600" />
                  <span>{h.user_rating} ({h.reviews_count})</span>
                </span>
                <span className="text-[10px] font-bold text-gray-400">{h.star_rating}-Star Hotel</span>
              </div>

              <h3 className="text-base font-extrabold text-[#101D30] leading-snug">{h.name}</h3>
              <p className="text-xs text-gray-500 flex items-center gap-1 mt-1">
                <MapPin className="w-3 h-3 text-[#35D6B0] shrink-0" />
                <span className="truncate">{h.location}</span>
              </p>

              <div className="mt-3 flex flex-wrap gap-1.5">
                {h.amenities.map((am) => (
                  <span key={am} className="text-[9px] font-semibold text-gray-600 bg-gray-100 px-2 py-0.5 rounded-md">
                    {am}
                  </span>
                ))}
              </div>
            </div>

            <div className="mt-5 pt-4 border-t border-gray-100 flex items-center justify-between">
              <div>
                <span className="text-[10px] text-gray-400 block">Per Night</span>
                <span className="text-lg font-black font-mono text-[#101D30]">₹{h.price_per_night.toFixed(0)}</span>
              </div>

              <button
                onClick={() => handleBookHotel(h)}
                disabled={bookingInProgress}
                className="px-4 py-2 rounded-xl bg-[#101D30] text-[#35D6B0] hover:bg-[#1a2f4d] font-bold text-xs transition active:scale-95 disabled:opacity-50"
              >
                Book Room
              </button>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

export default HotelBooking;
