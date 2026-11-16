import React, { useState, useEffect } from 'react';
import { mobilityService } from '../../services/mobilityService';
import { useToast } from '../../context/ToastContext';
import {
  Car,
  Bike,
  Navigation,
  MapPin,
  ExternalLink,
  ShieldCheck,
  Clock,
  ArrowRight,
  Loader2,
  Users,
  Compass
} from 'lucide-react';

export const RideBooking = () => {
  const { addToast } = useToast();

  const [pickup, setPickup] = useState('Connaught Place, New Delhi');
  const [drop, setDrop] = useState('Indira Gandhi International Airport, Terminal 3');
  const [selectedProvider, setSelectedProvider] = useState('all'); // 'all', 'uber', 'rapido'

  const [loading, setLoading] = useState(false);
  const [mobilityData, setMobilityData] = useState(null);

  const fetchEstimates = async () => {
    setLoading(true);
    try {
      const res = await mobilityService.estimate(pickup, drop);
      if (res && res.success) {
        setMobilityData(res.mobility);
      }
    } catch (err) {
      addToast('Error fetching ride estimates', 'error');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchEstimates();
  }, []);

  const handleUseCurrentLocation = () => {
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        () => {
          setPickup('Current GPS Location (Resolved)');
          addToast('Current location detected successfully', 'success');
        },
        () => {
          addToast('Location access denied. Please type your location manually.', 'info');
        }
      );
    } else {
      addToast('Geolocation is not supported by your browser.', 'info');
    }
  };

  const handleContinueWithProvider = async (providerName, ride) => {
    try {
      await mobilityService.logRedirect({
        provider: providerName.toLowerCase(),
        ride_type: ride.id,
        pickup,
        drop,
        estimated_fare: ride.estimated_fare
      });
    } catch {
      // logged gracefully
    }

    // Open official provider
    window.open(ride.deep_link, '_blank', 'noopener,noreferrer');
    addToast(`Redirecting to official ${providerName} app for pickup dispatch!`, 'info');
  };

  return (
    <div className="space-y-6 max-w-5xl mx-auto pb-12 animate-in fade-in">
      {/* Banner */}
      <div className="p-6 sm:p-8 bg-[#101D30] text-white rounded-3xl border border-slate-800 shadow-md flex items-center justify-between">
        <div className="space-y-1">
          <span className="text-[10px] font-mono font-bold text-[#35D6B0] uppercase tracking-wider block">
            Cabs, Auto Rickshaws & Bike Taxis
          </span>
          <h1 className="text-2xl sm:text-3xl font-black">Ride Booking & Mobility</h1>
          <p className="text-xs text-slate-300">Compare estimated fares for Uber and Rapido, then dispatch on the official provider app.</p>
        </div>
        <div className="w-12 h-12 rounded-2xl bg-white/10 text-[#35D6B0] flex items-center justify-center shrink-0">
          <Car className="w-6 h-6" />
        </div>
      </div>

      {/* Disclaimers Box */}
      <div className="p-4 bg-emerald-50/70 rounded-2xl border border-emerald-100 flex items-start gap-3 text-xs text-emerald-950">
        <ShieldCheck className="w-5 h-5 text-emerald-700 shrink-0 mt-0.5" />
        <div>
          <span className="font-bold">Transparent Mobility Adapter:</span> Fare estimates are approximate for demonstration. PayBen does not claim a ride was booked or debited from your wallet. Clicking "Continue with Uber / Rapido" opens the official verified ride application for driver dispatch.
        </div>
      </div>

      {/* Location Search Bar */}
      <div className="bg-white rounded-3xl p-6 border border-gray-200/80 shadow-xs space-y-4">
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div className="space-y-1">
            <div className="flex items-center justify-between">
              <label className="text-[10px] uppercase font-bold text-gray-500">Pickup Location</label>
              <button
                type="button"
                onClick={handleUseCurrentLocation}
                className="text-[10px] font-bold text-blue-600 hover:underline flex items-center gap-1"
              >
                <Navigation className="w-3 h-3" />
                <span>Use GPS Location</span>
              </button>
            </div>
            <div className="relative">
              <MapPin className="w-4 h-4 text-emerald-600 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                type="text"
                value={pickup}
                onChange={(e) => setPickup(e.target.value)}
                className="w-full pl-9 pr-4 py-2.5 rounded-2xl border border-gray-200 text-xs font-bold text-gray-800 bg-[#F5F7FA] outline-none focus:border-[#35D6B0]"
              />
            </div>
          </div>

          <div className="space-y-1">
            <label className="text-[10px] uppercase font-bold text-gray-500">Destination Drop</label>
            <div className="relative">
              <Compass className="w-4 h-4 text-rose-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                type="text"
                value={drop}
                onChange={(e) => setDrop(e.target.value)}
                className="w-full pl-9 pr-4 py-2.5 rounded-2xl border border-gray-200 text-xs font-bold text-gray-800 bg-[#F5F7FA] outline-none focus:border-[#35D6B0]"
              />
            </div>
          </div>
        </div>

        <div className="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2">
          <div className="flex gap-2 self-start sm:self-auto">
            {[
              { id: 'all', label: 'All Providers' },
              { id: 'uber', label: 'Uber Only' },
              { id: 'rapido', label: 'Rapido Only' }
            ].map((p) => (
              <button
                key={p.id}
                onClick={() => setSelectedProvider(p.id)}
                className={`px-3 py-1.5 rounded-xl text-xs font-bold transition ${
                  selectedProvider === p.id
                    ? 'bg-[#101D30] text-[#35D6B0]'
                    : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200/80'
                }`}
              >
                {p.label}
              </button>
            ))}
          </div>

          <button
            onClick={fetchEstimates}
            disabled={loading}
            className="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-[#101D30] text-[#35D6B0] font-extrabold text-xs shadow-xs hover:bg-[#1a2f4d] transition flex items-center justify-center gap-1.5 disabled:opacity-50"
          >
            {loading ? <Loader2 className="w-4 h-4 animate-spin" /> : <span>Update Estimates</span>}
          </button>
        </div>
      </div>

      {/* Ride Results Deck */}
      {mobilityData && (
        <div className="space-y-6">
          {/* UBER Section */}
          {(selectedProvider === 'all' || selectedProvider === 'uber') && (
            <section className="bg-white rounded-3xl p-6 border border-gray-200/80 shadow-xs space-y-4">
              <div className="flex items-center justify-between pb-3 border-b border-gray-100">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 rounded-2xl bg-black text-white flex items-center justify-center font-black text-sm">
                    Uber
                  </div>
                  <div>
                    <h3 className="text-base font-extrabold text-[#101D30]">Uber India</h3>
                    <p className="text-xs text-gray-500">Official Web & Mobile Rideshare Dispatch</p>
                  </div>
                </div>
                <span className="text-[10px] font-mono font-bold text-gray-400">
                  Integration: EXTERNAL_REDIRECT
                </span>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                {mobilityData.uber?.categories?.map((ride) => (
                  <div
                    key={ride.id}
                    className="p-4 rounded-2xl bg-[#F5F7FA] border border-gray-100 hover:border-gray-300 transition-all flex flex-col justify-between"
                  >
                    <div>
                      <div className="flex items-center justify-between">
                        <h4 className="text-sm font-bold text-[#101D30]">{ride.name}</h4>
                        <span className="text-base font-black font-mono text-[#101D30]">
                          ₹{ride.estimated_fare.toFixed(0)}
                        </span>
                      </div>
                      <p className="text-[11px] text-gray-500 mt-1">{ride.desc}</p>
                      <div className="flex items-center gap-3 mt-2 text-[10px] text-gray-400 font-mono">
                        <span className="flex items-center gap-1">
                          <Clock className="w-3 h-3 text-emerald-600" />
                          <span>{ride.eta}</span>
                        </span>
                        <span className="flex items-center gap-1">
                          <Users className="w-3 h-3" />
                          <span>Max {ride.capacity}</span>
                        </span>
                      </div>
                    </div>

                    <button
                      onClick={() => handleContinueWithProvider('Uber', ride)}
                      className="mt-4 w-full py-2 rounded-xl bg-black hover:bg-gray-800 text-white font-bold text-xs flex items-center justify-center gap-1.5 transition active:scale-95"
                    >
                      <span>Continue with Uber</span>
                      <ExternalLink className="w-3 h-3 text-[#35D6B0]" />
                    </button>
                  </div>
                ))}
              </div>
            </section>
          )}

          {/* RAPIDO Section */}
          {(selectedProvider === 'all' || selectedProvider === 'rapido') && (
            <section className="bg-white rounded-3xl p-6 border border-gray-200/80 shadow-xs space-y-4">
              <div className="flex items-center justify-between pb-3 border-b border-gray-100">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 rounded-2xl bg-amber-400 text-gray-900 flex items-center justify-center font-black text-sm">
                    Rapido
                  </div>
                  <div>
                    <h3 className="text-base font-extrabold text-[#101D30]">Rapido India</h3>
                    <p className="text-xs text-gray-500">Bike Taxis, Auto Rickshaws & Cabs</p>
                  </div>
                </div>
                <span className="text-[10px] font-mono font-bold text-gray-400">
                  Integration: EXTERNAL_REDIRECT
                </span>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                {mobilityData.rapido?.categories?.map((ride) => (
                  <div
                    key={ride.id}
                    className="p-4 rounded-2xl bg-[#F5F7FA] border border-gray-100 hover:border-gray-300 transition-all flex flex-col justify-between"
                  >
                    <div>
                      <div className="flex items-center justify-between">
                        <h4 className="text-sm font-bold text-[#101D30]">{ride.name}</h4>
                        <span className="text-base font-black font-mono text-[#101D30]">
                          ₹{ride.estimated_fare.toFixed(0)}
                        </span>
                      </div>
                      <p className="text-[11px] text-gray-500 mt-1">{ride.desc}</p>
                      <div className="flex items-center gap-3 mt-2 text-[10px] text-gray-400 font-mono">
                        <span className="flex items-center gap-1">
                          <Clock className="w-3 h-3 text-emerald-600" />
                          <span>{ride.eta}</span>
                        </span>
                        <span className="flex items-center gap-1">
                          <Users className="w-3 h-3" />
                          <span>Max {ride.capacity}</span>
                        </span>
                      </div>
                    </div>

                    <button
                      onClick={() => handleContinueWithProvider('Rapido', ride)}
                      className="mt-4 w-full py-2 rounded-xl bg-amber-400 hover:bg-amber-500 text-gray-950 font-extrabold text-xs flex items-center justify-center gap-1.5 transition active:scale-95"
                    >
                      <span>Continue with Rapido</span>
                      <ExternalLink className="w-3 h-3" />
                    </button>
                  </div>
                ))}
              </div>
            </section>
          )}
        </div>
      )}
    </div>
  );
};

export default RideBooking;
