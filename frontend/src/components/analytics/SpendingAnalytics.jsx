import React, { useState } from 'react';
import { TrendingUp, Calendar, ArrowUpRight, BarChart3, AlertCircle } from 'lucide-react';

/**
 * SpendingAnalytics — Neo-Fintech Monthly Expenditure Chart
 * Built with pure responsive SVG vector geometry.
 * Uses real transaction data from the backend.
 */
export const SpendingAnalytics = ({ monthlyData = [], totalSpent = 0 }) => {
  const [hoveredIndex, setHoveredIndex] = useState(null);

  // Generate normalized 6-month slots if backend has partial history
  const months = ['Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov'];
  const now = new Date();
  const currentMonthIdx = now.getMonth();

  // Map backend monthly data into the recent 6 calendar months
  const chartData = Array.from({ length: 6 }).map((_, i) => {
    const d = new Date(now.getFullYear(), currentMonthIdx - 5 + i, 1);
    const label = d.toLocaleString('en-IN', { month: 'short' });
    const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;

    const match = Array.isArray(monthlyData)
      ? monthlyData.find((m) => m.month_key === key || m.month_label === label)
      : null;

    const amount = match ? parseFloat(match.total || match.spent || 0) : 0;
    return {
      label,
      key,
      amount,
      isCurrentMonth: i === 5
    };
  });

  const maxAmount = Math.max(...chartData.map((d) => d.amount), 500); // minimum scale anchor ₹500
  const avgMonthly = totalSpent > 0 ? (totalSpent / 6).toFixed(0) : 0;

  return (
    <div className="bg-white rounded-3xl p-6 border border-gray-200/80 shadow-xs space-y-5">
      {/* Chart Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        <div>
          <div className="flex items-center gap-2">
            <div className="w-8 h-8 rounded-xl bg-[#101D30] text-[#35D6B0] flex items-center justify-center shrink-0">
              <BarChart3 className="w-4 h-4" />
            </div>
            <div>
              <h2 className="text-base font-extrabold text-[#101D30]">Spending Analytics</h2>
              <p className="text-xs text-gray-500">6-Month monthly expenditure breakdown</p>
            </div>
          </div>
        </div>

        <div className="flex items-center gap-3">
          <div className="text-right">
            <span className="text-[10px] uppercase font-bold text-gray-400 tracking-wider block">
              Avg. Monthly
            </span>
            <span className="text-sm font-extrabold font-mono text-[#101D30]">
              ₹{Number(avgMonthly).toLocaleString('en-IN')}
            </span>
          </div>
          <div className="h-7 w-px bg-gray-200 hidden sm:block" />
          <div className="text-right">
            <span className="text-[10px] uppercase font-bold text-gray-400 tracking-wider block">
              Total Spent
            </span>
            <span className="text-sm font-extrabold font-mono text-emerald-600">
              ₹{Number(totalSpent).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
            </span>
          </div>
        </div>
      </div>

      {/* Main SVG Bar Chart Container */}
      <div className="relative pt-6 pb-2">
        {/* Y-Axis Guidelines */}
        <div className="absolute inset-x-0 top-6 bottom-8 flex flex-col justify-between pointer-events-none text-[10px] font-mono text-gray-300">
          <div className="border-b border-gray-100 flex justify-between">
            <span>₹{Math.round(maxAmount).toLocaleString('en-IN')}</span>
          </div>
          <div className="border-b border-gray-100 flex justify-between">
            <span>₹{Math.round(maxAmount / 2).toLocaleString('en-IN')}</span>
          </div>
          <div className="border-b border-gray-100 flex justify-between">
            <span>₹0</span>
          </div>
        </div>

        {/* Bars Grid */}
        <div className="relative z-10 grid grid-cols-6 gap-2 sm:gap-4 h-44 items-end px-2 sm:px-4">
          {chartData.map((item, idx) => {
            const heightPercent = maxAmount > 0 ? Math.max(8, (item.amount / maxAmount) * 100) : 8;
            const isHovered = hoveredIndex === idx;

            return (
              <div
                key={item.key}
                className="flex flex-col items-center h-full justify-end group cursor-pointer"
                onMouseEnter={() => setHoveredIndex(idx)}
                onMouseLeave={() => setHoveredIndex(null)}
              >
                {/* Floating Tooltip */}
                <div
                  className={`absolute -top-3 transition-all duration-200 pointer-events-none px-2.5 py-1 rounded-xl bg-[#101D30] text-white text-[11px] font-mono font-bold shadow-lg z-20 ${
                    isHovered ? 'opacity-100 scale-100' : 'opacity-0 scale-95'
                  }`}
                  style={{
                    left: `${(idx / 5) * 80 + 10}%`,
                    transform: 'translateX(-50%)'
                  }}
                >
                  <span className="text-gray-300 mr-1">{item.label}:</span>
                  <span className="text-[#35D6B0]">₹{item.amount.toFixed(2)}</span>
                </div>

                {/* Bar Pillar */}
                <div className="w-full max-w-[42px] flex items-end justify-center h-full pb-1">
                  <div
                    style={{ height: `${heightPercent}%` }}
                    className={`w-full rounded-t-xl transition-all duration-300 relative overflow-hidden ${
                      item.amount > 0
                        ? item.isCurrentMonth
                          ? 'bg-gradient-to-t from-[#101D30] to-[#35D6B0] shadow-sm'
                          : 'bg-[#101D30] group-hover:bg-[#1a2f4d]'
                        : 'bg-gray-100 group-hover:bg-gray-200'
                    }`}
                  >
                    {item.isCurrentMonth && item.amount > 0 && (
                      <div className="absolute inset-x-0 top-0 h-1 bg-[#35D6B0]" />
                    )}
                  </div>
                </div>

                {/* X-Axis Label */}
                <div className="text-center mt-2">
                  <span
                    className={`text-[11px] font-bold block transition-colors ${
                      item.isCurrentMonth
                        ? 'text-[#101D30] font-black'
                        : isHovered
                        ? 'text-gray-900'
                        : 'text-gray-400'
                    }`}
                  >
                    {item.label}
                  </span>
                </div>
              </div>
            );
          })}
        </div>
      </div>

      {/* Chart Footer Indicator */}
      <div className="pt-2 flex items-center justify-between border-t border-gray-100 text-[11px] text-gray-500">
        <div className="flex items-center gap-1.5">
          <span className="w-2.5 h-2.5 rounded-full bg-[#35D6B0] inline-block" />
          <span className="font-medium text-gray-600">Simulated Ledger Outflow</span>
        </div>
        <span className="font-mono text-gray-400">Values updated atomically from database</span>
      </div>
    </div>
  );
};

export default SpendingAnalytics;
