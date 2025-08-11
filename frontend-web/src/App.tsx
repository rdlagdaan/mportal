import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import TabbedLogin from './pages/TabbedLogin';

function App() {
  return (
    <BrowserRouter basename={import.meta.env.BASE_URL}>
      <Routes>
        <Route path="/" element={<TabbedLogin />} />
        <Route path="/login" element={<TabbedLogin />} />
        <Route path="*" element={<Navigate to="/login" replace />} />
      </Routes>
    </BrowserRouter>
  );
}

export default App;
