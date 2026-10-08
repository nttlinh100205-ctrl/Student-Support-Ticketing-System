import {
  BrowserRouter,
  Navigate,
  Route,
  Routes,
} from 'react-router-dom'

import './App.css'

import AdminRoute from './components/AdminRoute'
import ProtectedRoute from './components/ProtectedRoute'

import AdminUsersPage from './pages/AdminUsersPage'
import ChangePasswordPage from './pages/ChangePasswordPage'
import ForgotPasswordPage from './pages/ForgotPasswordPage'
import LoginHistoryPage from './pages/LoginHistoryPage'
import LoginPage from './pages/LoginPage'
import ProfilePage from './pages/ProfilePage'
import ResetPasswordPage from './pages/ResetPasswordPage'
import RegisterPage from './pages/RegisterPage'

function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route
          path="/login"
          element={<LoginPage />}
        />
<Route
  path="/register"
  element={<RegisterPage />}
/>
        <Route
          path="/forgot-password"
          element={<ForgotPasswordPage />}
        />

        <Route
          path="/reset-password/:token"
          element={<ResetPasswordPage />}
        />

        <Route
          path="/profile"
          element={
            <ProtectedRoute>
              <ProfilePage />
            </ProtectedRoute>
          }
        />

        <Route
          path="/change-password"
          element={
            <ProtectedRoute>
              <ChangePasswordPage />
            </ProtectedRoute>
          }
        />

        <Route
          path="/login-history"
          element={
            <ProtectedRoute>
              <LoginHistoryPage />
            </ProtectedRoute>
          }
        />

        <Route
          path="/admin/users"
          element={
            <AdminRoute>
              <AdminUsersPage />
            </AdminRoute>
          }
        />

        <Route
          path="/"
          element={
            <Navigate
              to="/login"
              replace
            />
          }
        />

        <Route
          path="*"
          element={
            <Navigate
              to="/login"
              replace
            />
          }
        />
      </Routes>
    </BrowserRouter>
  )
}

export default App