import React from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import Navigation from './components/Navigation';
import AdminPage from './pages/AdminPage';
import DemoPage from './pages/DemoPage';
import NotFoundPage from './pages/NotFoundPage';
import AuthPage from './pages/AuthPage';
import ProfilePage from './pages/ProfilePage';
import { AuthProvider, useAuth } from './context/AuthContext';
import { ToastProvider } from './utils/ToastContext';
import KucoinMarket from './components/KucoinMarket';
import DerivMarket from './components/DerivMarket';
import MarketDashboard from './components/MarketDashboard';
import LoginPage from './components/LoginPage';
import TraderDashboard from './components/TraderDashboard';
import LandingPage from './components/LandingPage';
import Footer from './components/Footer';

function ProtectedRoute({ children, adminOnly }) {
  const { user, loading } = useAuth();
  if (loading) return <div>Loading...</div>;
  if (!user) return <Navigate to="/login" replace />;
  if (adminOnly && user.role !== 'admin') return <Navigate to="/" replace />;
  return children;
}

function App() {
  return (
    <ToastProvider>
      <AuthProvider>
        <Router>
          <Navigation />
          <Routes>
            {/* Public routes */}
            <Route path="/login" element={<LoginPage />} />
            <Route path="/auth" element={<AuthPage />} />
            <Route path="/demo" element={<DemoPage />} />
            <Route path="/" element={<LandingPage />} />

            {/* Protected routes */}
            <Route path="/dashboard" element={<ProtectedRoute><TraderDashboard /></ProtectedRoute>} />
            <Route path="/admin" element={<ProtectedRoute adminOnly={true}><AdminPage /></ProtectedRoute>} />
            <Route path="/profile" element={<ProtectedRoute><ProfilePage /></ProtectedRoute>} />
            <Route path="/kucoin" element={<ProtectedRoute><KucoinMarket /></ProtectedRoute>} />
            <Route path="/deriv" element={<ProtectedRoute><DerivMarket /></ProtectedRoute>} />
            <Route path="/markets" element={<ProtectedRoute><MarketDashboard /></ProtectedRoute>} />

            {/* 404 route */}
            <Route path="*" element={<NotFoundPage />} />
          </Routes>
          <Footer />
        </Router>
      </AuthProvider>
    </ToastProvider>
  );
}

export default App;

