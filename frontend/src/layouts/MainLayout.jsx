import React, { useState } from 'react';
import { Outlet } from 'react-router-dom';
import { Navbar } from '../components/common/Navbar';
import { Sidebar } from '../components/common/Sidebar';
import { AddMoneyModal } from '../components/modals/AddMoneyModal';
import { ScanPayModal } from '../components/modals/ScanPayModal';
import { QrModal } from '../components/modals/QrModal';

export const MainLayout = () => {
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [addMoneyOpen, setAddMoneyOpen] = useState(false);
  const [scanPayOpen, setScanPayOpen] = useState(false);
  const [myQrOpen, setMyQrOpen] = useState(false);

  return (
    <div className="min-h-screen flex flex-col bg-[#f6f8fc]">
      <Navbar
        onToggleSidebar={() => setSidebarOpen(!sidebarOpen)}
        onOpenAddMoney={() => setAddMoneyOpen(true)}
        onOpenScanPay={() => setScanPayOpen(true)}
        onOpenMyQr={() => setMyQrOpen(true)}
      />

      <div className="flex-1 flex max-w-7xl w-full mx-auto">
        <Sidebar
          isOpen={sidebarOpen}
          onClose={() => setSidebarOpen(false)}
        />

        <main className="flex-1 p-4 sm:p-6 lg:p-8 overflow-x-hidden">
          <Outlet
            context={{
              openAddMoney: () => setAddMoneyOpen(true),
              openScanPay: () => setScanPayOpen(true),
              openMyQr: () => setMyQrOpen(true)
            }}
          />
        </main>
      </div>

      <AddMoneyModal
        isOpen={addMoneyOpen}
        onClose={() => setAddMoneyOpen(false)}
      />

      <ScanPayModal
        isOpen={scanPayOpen}
        onClose={() => setScanPayOpen(false)}
      />

      <QrModal
        isOpen={myQrOpen}
        onClose={() => setMyQrOpen(false)}
      />
    </div>
  );
};
