import React from 'react';
import { Link } from 'react-router-dom';
import { Smartphone, Tv, Zap, Droplet, Flame, ArrowUpRight } from 'lucide-react';

/**
 * PopularServices — Neo-Fintech Recharge & Bill Payment Grid
 * Features Mobile Recharge, DTH Recharge, Electricity, Water, and Gas.
 * Fully clickable, responsive cards with consistent Lucide icon containers.
 */
export const PopularServices = () => {
  const services = [
    {
      name: 'Mobile Recharge',
      desc: 'Prepaid & Postpaid',
      route: '/recharge/mobile',
      icon: Smartphone,
      accent: 'text-blue-600',
      tag: 'Instant'
    },
    {
      name: 'DTH Recharge',
      desc: 'Direct-to-Home TV',
      route: '/recharge/dth',
      icon: Tv,
      accent: 'text-indigo-600',
      tag: 'Fast'
    },
    {
      name: 'Electricity Bill',
      desc: 'State Power Boards',
      route: '/bills/electricity',
      icon: Zap,
      accent: 'text-amber-500',
      tag: 'BBPS'
    },
    {
      name: 'Water Bill',
      desc: 'Municipal Authorities',
      route: '/bills/water',
      icon: Droplet,
      accent: 'text-cyan-600',
      tag: 'Auto-Fetch'
    },
    {
      name: 'Gas Cylinder/Piped',
      desc: 'LPG & Piped Gas',
      route: '/bills/gas',
      icon: Flame,
      accent: 'text-rose-500',
      tag: 'Subsidized'
    }
  ];

  return (
    <section id="services" className="space-y-3">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-base font-extrabold text-[#101D30]">Popular Services</h2>
          <p className="text-xs text-gray-500">Pick any utility or recharge to get started</p>
        </div>
      </div>

      <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3 sm:gap-4">
        {services.map((item) => {
          const Icon = item.icon;
          return (
            <Link
              key={item.name}
              to={item.route}
              className="group p-4 sm:p-5 bg-white rounded-3xl border border-gray-200/80 shadow-xs hover:shadow-md hover:border-[#35D6B0] transition-all flex flex-col items-center text-center relative overflow-hidden focus:outline-none focus:ring-2 focus:ring-[#35D6B0]"
              aria-label={`Open ${item.name}`}
            >
              {/* Top Accent Strip on hover */}
              <div className="absolute top-0 inset-x-0 h-1 bg-[#35D6B0] opacity-0 group-hover:opacity-100 transition-opacity" />

              {/* Tag Badge */}
              <span className="absolute top-2.5 right-2.5 text-[9px] font-bold text-gray-400 group-hover:text-[#101D30] transition-colors">
                {item.tag}
              </span>

              {/* Icon Container */}
              <div className="w-13 h-13 sm:w-14 sm:h-14 rounded-2xl bg-[#F5F7FA] border border-gray-100 flex items-center justify-center mb-3 group-hover:scale-105 group-hover:bg-[#101D30] transition-all shadow-xs">
                <Icon className={`w-6 h-6 ${item.accent} group-hover:text-[#35D6B0] transition-colors`} />
              </div>

              {/* Title & Description */}
              <span className="text-xs sm:text-sm font-bold text-[#101D30] group-hover:text-[#101D30] transition-colors leading-tight">
                {item.name}
              </span>
              <span className="text-[10px] text-gray-400 mt-1 line-clamp-1">
                {item.desc}
              </span>

              {/* Subtle arrow on hover */}
              <div className="mt-2 text-[#35D6B0] opacity-0 group-hover:opacity-100 transition-opacity flex items-center text-[10px] font-bold gap-0.5">
                <span>Pay now</span>
                <ArrowUpRight className="w-3 h-3" />
              </div>
            </Link>
          );
        })}
      </div>
    </section>
  );
};

export default PopularServices;
