
import { BrowserRouter as Router, Routes, Route, Navigate } from "react-router-dom";
import { AuthProvider } from "./contexts/AuthContext";

function App() {
  return (
    <AuthProvider>
      <Router>
        <Routes>
          <Route path="/" element={<Navigate to="/index.html" />} />
          <Route path="/power-consumption" element={<Navigate to="/power_consumption.html" />} />
          <Route path="/income-summary" element={<Navigate to="/income_summary.html" />} />
          <Route path="*" element={<Navigate to="/index.html" />} />
        </Routes>
      </Router>
    </AuthProvider>
  );
}

export default App;
