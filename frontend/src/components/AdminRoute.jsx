import { Navigate } from 'react-router-dom'
import {
  getCurrentUser,
  isAuthenticated,
} from '../services/api'

function AdminRoute({ children }) {
  if (!isAuthenticated()) {
    return (
      <Navigate
        to="/login"
        replace
      />
    )
  }

  const user = getCurrentUser()

  if (user?.role !== 'admin') {
    return (
      <Navigate
        to="/profile"
        replace
      />
    )
  }

  return children
}

export default AdminRoute