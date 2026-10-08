import { useState } from 'react'

import {
  Link,
  Navigate,
  useNavigate,
} from 'react-router-dom'

import {
  apiRequest,
  getCurrentUser,
  isAuthenticated,
  saveAuth,
} from '../services/api'

function LoginPage() {
  const navigate = useNavigate()

  const [email, setEmail] =
    useState('')

  const [password, setPassword] =
    useState('')

  const [message, setMessage] =
    useState('')

  const [loading, setLoading] =
    useState(false)

  if (isAuthenticated()) {
    const currentUser =
      getCurrentUser()

    if (
      currentUser
        ?.must_change_password
    ) {
      return (
        <Navigate
          to="/change-password"
          replace
        />
      )
    }

    return (
      <Navigate
        to={
          currentUser?.role ===
          'admin'
            ? '/admin/users'
            : '/profile'
        }
        replace
      />
    )
  }

  const handleSubmit =
    async (event) => {
      event.preventDefault()

      setLoading(true)
      setMessage('')

      try {
        const {
          response,
          data,
        } = await apiRequest(
          '/api/auth/login',
          {
            method: 'POST',

            body: JSON.stringify({
              email,
              password,
            }),
          }
        )

        if (
          !response.ok ||
          !data.success
        ) {
          setMessage(
            data.message ||
              'Đăng nhập thất bại.'
          )

          return
        }

        saveAuth(data.data)

        if (
          data.data.user
            .must_change_password
        ) {
          navigate(
            '/change-password',
            {
              replace: true,
            }
          )

          return
        }

        if (
          data.data.user.role ===
          'admin'
        ) {
          navigate(
            '/admin/users',
            {
              replace: true,
            }
          )

          return
        }

        navigate('/profile', {
          replace: true,
        })
      } catch {
        setMessage(
          'Không thể kết nối tới hệ thống.'
        )
      } finally {
        setLoading(false)
      }
    }

  return (
    <div className="page">
      <div className="login-card">
        <h1>
          Student Support System
        </h1>

        <form
          onSubmit={handleSubmit}
        >
          <label>Email</label>

          <input
            type="email"
            value={email}
            onChange={(event) =>
              setEmail(
                event.target.value
              )
            }
            placeholder="Nhập email"
            required
          />

          <label>
            Mật khẩu
          </label>

          <input
            type="password"
            value={password}
            onChange={(event) =>
              setPassword(
                event.target.value
              )
            }
            placeholder="Nhập mật khẩu"
            required
          />

          <button
            type="submit"
            disabled={loading}
          >
            {loading
              ? 'Đang đăng nhập...'
              : 'Đăng nhập'}
          </button>
        </form>

        {message && (
          <p className="message">
            {message}
          </p>
        )}

        <div className="auth-links auth-links-group">
          <Link to="/forgot-password">
            Quên mật khẩu?
          </Link>

          <Link to="/register">
            Đăng ký tài khoản
          </Link>
        </div>
      </div>
    </div>
  )
}

export default LoginPage