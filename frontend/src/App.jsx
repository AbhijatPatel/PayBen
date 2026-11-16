import React from 'react';
import { BrowserRouter } from 'react-router-dom';
import { ErrorBoundary } from './components/common/ErrorBoundary';
import { AuthProvider } from './context/AuthContext';
import { ToastProvider } from './context/ToastContext';
import { SecurityProvider } from './context/SecurityContext';
import { FintechProvider } from './context/FintechContext';
import { LanguageProvider } from './context/LanguageContext';
import { SecurityLockScreen } from './components/security/SecurityLockScreen';
import { PayBenIntro } from './components/intro/PayBenIntro';
import { AppRoutes } from './routes/AppRoutes';

function App() {
  return (
    <ErrorBoundary>
      <BrowserRouter>
        <LanguageProvider>
          <ToastProvider>
            <AuthProvider>
              <FintechProvider>
                <SecurityProvider>
                  <PayBenIntro />
                  <SecurityLockScreen />
                  <AppRoutes />
                </SecurityProvider>
              </FintechProvider>
            </AuthProvider>
          </ToastProvider>
        </LanguageProvider>
      </BrowserRouter>
    </ErrorBoundary>
  );
}

export default App;
