// App.tsx
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import TabbedLogin from './pages/TabbedLogin';
import Dashboard from './pages/Dashboard';
import ProtectedRoute from './pages/components/ProtectedRoute';

function App() {
  return (
    <BrowserRouter basename="/app">   {/* <-- set this explicitly */}
      <Routes>
        <Route path="/" element={<TabbedLogin />} />
        <Route path="/login" element={<TabbedLogin />} />
        <Route
          path="/dashboard"
          element={
            <ProtectedRoute>
              <Dashboard />
            </ProtectedRoute>
          }
        />
        <Route path="*" element={<Navigate to="/login" replace />} />
      </Routes>
    </BrowserRouter>
  );
}

export default App;
