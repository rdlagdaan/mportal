import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import TabbedLogin from './pages/TabbedLogin';
import Dashboard from './pages/Dashboard';                  // ⬅️ added
import ProtectedRoute from './pages/components/ProtectedRoute';    // ⬅️ added

function App() {
  return (
    <BrowserRouter basename={import.meta.env.BASE_URL}>
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
