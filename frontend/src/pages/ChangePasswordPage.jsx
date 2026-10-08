import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import {
  apiRequest,
  getCurrentUser,
} from '../services/api'

function ChangePasswordPage() {
  const navigate = useNavigate()

  const [currentPassword, setCurrentPassword] =
    useState('')

  const [password, setPassword] =
    useState('')

  const [
    passwordConfirmation,
    setPasswordConfirmation,
  ] = useState('')

  const [message, setMessage] =
    useState('')

  const [loading, setLoading] =
    useState(false)

  const handleSubmit = async (event) => {
    event.preventDefault()

    if (
      password !==
      passwordConfirmation
    ) {
      setMessage(
        'Mật khẩu xác nhận không khớp.'
      )

      return
    }

    setLoading(true)
    setMessage('')

    try {
      const { response, data } =
        await apiRequest(
          '/api/profile/password',
          {
            method: 'PUT',
            body: JSON.stringify({
              current_password:
                currentPassword,
              password,
              password_confirmation:
                passwordConfirmation,
            }),
          }
        )

      if (!response.ok || !data.success) {
        setMessage(
          data.message ||
            'Đổi mật khẩu thất bại.'
        )

        return
      }

      const user =
        getCurrentUser()

      if (user) {
        user.must_change_password =
          false

        localStorage.setItem(
          'user',
          JSON.stringify(user)
        )
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
        <h1>Đổi mật khẩu</h1>

        <form onSubmit={handleSubmit}>
          <label>
            Mật khẩu hiện tại
          </label>

          <input
            type="password"
            value={currentPassword}
            onChange={(event) =>
              setCurrentPassword(
                event.target.value
              )
            }
            required
          />

          <label>
            Mật khẩu mới
          </label>

          <input
            type="password"
            value={password}
            onChange={(event) =>
              setPassword(
                event.target.value
              )
            }
            required
          />

          <label>
            Xác nhận mật khẩu mới
          </label>

          <input
            type="password"
            value={
              passwordConfirmation
            }
            onChange={(event) =>
              setPasswordConfirmation(
                event.target.value
              )
            }
            required
          />

          <button
            type="submit"
            disabled={loading}
          >
            {loading
              ? 'Đang xử lý...'
              : 'Đổi mật khẩu'}
          </button>
        </form>

        {message && (
          <p className="message">
            {message}
          </p>
        )}
      </div>
    </div>
  )
}

export default ChangePasswordPage
