import React, { useState } from 'react';
import { Outlet, useLocation } from 'react-router-dom';
import { Navbar } from '../components/common/Navbar';
import { Sidebar } from '../components/common/Sidebar';
import { AddMoneyModal } from '../components/modals/AddMoneyModal';
import { ScanPayModal } from '../components/modals/ScanPayModal';
import { QrModal } from '../components/modals/QrModal';

export const MainLayout = () => {
  const { pathname } = useLocation();
  const isAuthRoute = ['/login', '/register', '/forgot-password', '/reset-password'].includes(pathname);
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [addMoneyOpen, setAddMoneyOpen] = useState(false);
  const [scanPayOpen, setScanPayOpen] = useState(false);
  const [myQrOpen, setMyQrOpen] = useState(false);

  return (
    <div className="min-h-screen flex flex-col bg-[#f6f8fc]">
      {!isAuthRoute && (
        <Navbar
          onToggleSidebar={() => setSidebarOpen(!sidebarOpen)}
          onOpenAddMoney={() => setAddMoneyOpen(true)}
          onOpenScanPay={() => setScanPayOpen(true)}
          onOpenMyQr={() => setMyQrOpen(true)}
        />
      )}

      <div className={`flex-1 flex ${isAuthRoute ? 'w-full' : 'max-w-7xl w-full mx-auto'}`}>
        {!isAuthRoute && (
          <Sidebar
            isOpen={sidebarOpen}
            onClose={() => setSidebarOpen(false)}
          />
        )}

        <div className={`flex-1 overflow-x-hidden ${isAuthRoute ? '' : 'p-4 sm:p-6 lg:p-8'}`}>
          <Outlet
            context={{
              openAddMoney: () => setAddMoneyOpen(true),
              openScanPay: () => setScanPayOpen(true),
              openMyQr: () => setMyQrOpen(true)
            }}
          />
        </div>
      </div>

      {!isAuthRoute && (
        <>
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
        </>
      )}
    </div>
  );
};
