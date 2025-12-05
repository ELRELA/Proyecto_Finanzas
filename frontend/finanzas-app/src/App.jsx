import { useState, useEffect } from 'react';
import Login from './pages/Login';
import Dashboard from './pages/Dashboard';

function App() {
  // Leemos el token de localStorage para que no se pierda al recargar
  const [token, setToken] = useState(localStorage.getItem('token'));

  // Efecto para persistencia
  useEffect(() => {
    if (token) {
      localStorage.setItem('token', token);
    } else {
      localStorage.removeItem('token');
    }
  }, [token]);

  // Si no hay token, mostramos Login
  if (!token) {
    return <Login setToken={setToken} />;
  }

  // Si hay token, mostramos Dashboard
  return <Dashboard token={token} onLogout={() => setToken(null)} />;
}

export default App;