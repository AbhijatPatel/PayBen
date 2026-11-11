import React, { useState } from 'react';
import { Outlet } from 'react-router-dom';
import { Navbar } from '../components/common/Navbar';
import { Sidebar } from '../components/common/Sidebar';
import { AddMoneyModal } from '../components/modals/AddMoneyModal';

export const MainLayout = () => {
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [addMoneyOpen, setAddMoneyOpen] = useState(false);

  return (
    <div className="min-h-screen flex flex-col bg-[#f8fafd]">
      <Navbar
        onToggleSidebar={() => setSidebarOpen(!sidebarOpen)}
        onOpenAddMoney={() => setAddMoneyOpen(true)}
      />

      <div className="flex-1 flex max-w-7xl w-full mx-auto">
        <Sidebar
          isOpen={sidebarOpen}
          onClose={() => setSidebarOpen(false)}
        />

        <main className="flex-1 p-4 sm:p-6 lg:p-8 overflow-x-hidden">
          <Outlet context={{ openAddMoney: () => setAddMoneyOpen(true) }} />
        </main>
      </div>

      <AddMoneyModal
        isOpen={addMoneyOpen}
        onClose={() => setAddMoneyOpen(false)}
      />
    </div>
  );
};
