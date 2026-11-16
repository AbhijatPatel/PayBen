import React from 'react';
import { Link } from 'react-router-dom';
import {
  Plane,
  Train,
  Bus,
  Building2,
  ShoppingBag,
  Utensils,
  Car,
  ArrowRight,
  Sparkles,
  ExternalLink
} from 'lucide-react';

export const ExploreSuperApp = () => {
  const categories = [
    {
      title: 'Travel & Ticket Booking',
      desc: 'Trains, buses, flights & hotel reservations with sandbox PNR confirmation.',
      link: '/travel/trains',
      badge: 'Integrated Sandbox',
      theme: 'from-[#101D30] to-[#1E3A5F]',
      accentColor: 'text-[#35D6B0]',
      mainIcon: Plane,
      subItems: [
        { name: 'Trains (IRCTC)', path: '/travel/trains', icon: Train },
        { name: 'Buses (redBus)', path: '/travel/buses', icon: Bus },
        { name: 'Flights', path: '/travel/flights', icon: Plane },
        { name: 'Hotels', path: '/travel/hotels', icon: Building2 }
      ]
    },
    {
      title: 'Online Shopping',
      desc: 'Discover verified e-commerce partners like Flipkart, Myntra & Meesho.',
      link: '/shopping',
      badge: 'Verified Partners',
      theme: 'from-[#101D30] to-[#2D3748]',
      accentColor: 'text-amber-400',
      mainIcon: ShoppingBag,
      subItems: [
        { name: 'Flipkart', path: '/shopping', icon: ShoppingBag },
        { name: 'Myntra', path: '/shopping', icon: ShoppingBag },
        { name: 'Meesho', path: '/shopping', icon: ShoppingBag }
      ]
    },
    {
      title: 'Food & Grocery Delivery',
      desc: 'Instant restaurant orders and 10-minute grocery delivery via Swiggy.',
      link: '/food',
      badge: 'On-Demand',
      theme: 'from-[#101D30] to-[#3D261A]',
      accentColor: 'text-orange-400',
      mainIcon: Utensils,
      subItems: [
        { name: 'Food Delivery', path: '/food', icon: Utensils },
        { name: 'Instamart Grocery', path: '/food', icon: ShoppingBag },
        { name: 'Swiggy Genie', path: '/food', icon: ExternalLink }
      ]
    },
    {
      title: 'Ride Booking & Mobility',
      desc: 'Uber cabs & Rapido bike taxis with transparent route fare estimators.',
      link: '/mobility',
      badge: 'City Commute',
      theme: 'from-[#101D30] to-[#1A365D]',
      accentColor: 'text-blue-400',
      mainIcon: Car,
      subItems: [
        { name: 'Uber Go & Auto', path: '/mobility', icon: Car },
        { name: 'Rapido Bike Taxi', path: '/mobility', icon: Car },
        { name: 'Outstation', path: '/mobility', icon: CompassIcon }
      ]
    }
  ];

  function CompassIcon(props) {
    return <Plane {...props} />;
  }

  return (
    <section className="space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <div className="flex items-center gap-2">
            <h2 className="text-base font-extrabold text-[#101D30]">Explore Everything</h2>
            <span className="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full bg-[#101D30] text-[#35D6B0] tracking-wide">
              Super App 2.0
            </span>
          </div>
          <p className="text-xs text-gray-500">Everyday digital lifestyle services unified in one platform</p>
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        {categories.map((cat) => {
          const MainIcon = cat.mainIcon;
          return (
            <div
              key={cat.title}
              className="bg-white rounded-3xl p-6 border border-gray-200/80 shadow-xs hover:border-[#35D6B0] transition-all flex flex-col justify-between group"
            >
              <div>
                <div className="flex items-center justify-between mb-3">
                  <div className="flex items-center gap-3">
                    <div className="w-12 h-12 rounded-2xl bg-[#101D30] text-[#35D6B0] flex items-center justify-center shadow-sm group-hover:scale-105 transition-transform">
                      <MainIcon className="w-6 h-6" />
                    </div>
                    <div>
                      <h3 className="text-base font-extrabold text-[#101D30]">{cat.title}</h3>
                      <span className="text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                        {cat.badge}
                      </span>
                    </div>
                  </div>

                  <Link
                    to={cat.link}
                    className="w-8 h-8 rounded-full bg-[#F5F7FA] text-gray-500 hover:bg-[#101D30] hover:text-[#35D6B0] flex items-center justify-center transition"
                  >
                    <ArrowRight className="w-4 h-4" />
                  </Link>
                </div>

                <p className="text-xs text-gray-600 leading-relaxed mb-4">
                  {cat.desc}
                </p>

                {/* Sub Items Quick Pill Links */}
                <div className="flex flex-wrap gap-2">
                  {cat.subItems.map((sub) => {
                    const SubIcon = sub.icon;
                    return (
                      <Link
                        key={sub.name}
                        to={sub.path}
                        className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[#F5F7FA] hover:bg-gray-100 text-xs font-bold text-gray-800 transition"
                      >
                        <SubIcon className="w-3.5 h-3.5 text-gray-500" />
                        <span>{sub.name}</span>
                      </Link>
                    );
                  })}
                </div>
              </div>
            </div>
          );
        })}
      </div>
    </section>
  );
};

export default ExploreSuperApp;
