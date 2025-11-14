
import React, { createContext, useContext, useState, useEffect } from "react";

interface AuthContextType {
  isAuthenticated: boolean;
  user: any | null;
  login: (username: string, password: string) => Promise<boolean>;
  logout: () => void;
}

const AuthContext = createContext<AuthContextType>({
  isAuthenticated: false,
  user: null,
  login: async () => false,
  logout: () => {},
});

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [isAuthenticated, setIsAuthenticated] = useState<boolean>(false);
  const [user, setUser] = useState<any | null>(null);

  // Check if user is already logged in on component mount
  useEffect(() => {
    const checkAuth = async () => {
      // In a real implementation, you would check with your backend
      const userInfo = localStorage.getItem("user");
      if (userInfo) {
        setUser(JSON.parse(userInfo));
        setIsAuthenticated(true);
      }
    };
    
    checkAuth();
  }, []);

  const login = async (username: string, password: string): Promise<boolean> => {
    try {
      // In a real implementation, you would send credentials to your backend
      // For now, we'll simulate a successful login
      const mockUser = { id: 1, username, name: "Test User", role: "admin" };
      localStorage.setItem("user", JSON.stringify(mockUser));
      setUser(mockUser);
      setIsAuthenticated(true);
      return true;
    } catch (error) {
      console.error("Login failed", error);
      return false;
    }
  };

  const logout = () => {
    localStorage.removeItem("user");
    setUser(null);
    setIsAuthenticated(false);
  };

  return (
    <AuthContext.Provider value={{ isAuthenticated, user, login, logout }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => useContext(AuthContext);
