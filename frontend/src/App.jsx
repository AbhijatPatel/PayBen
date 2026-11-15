import React from 'react';
import { BrowserRouter } from 'react-router-dom';
import { AuthProvider } from './context/AuthContext';
import { ToastProvider } from './context/ToastContext';
import { SecurityProvider } from './context/SecurityContext';
import { SecurityLockScreen } from './components/security/SecurityLockScreen';
import { AppRoutes } from './routes/AppRoutes';

function App() {
  return (
    <BrowserRouter>
      <ToastProvider>
        <AuthProvider>
          <SecurityProvider>
            <SecurityLockScreen />
            <AppRoutes />
          </SecurityProvider>
        </AuthProvider>
      </ToastProvider>
    </BrowserRouter>
  );
}

export default App;

