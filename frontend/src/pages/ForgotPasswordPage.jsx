import { useState } from 'react'
import { Link } from 'react-router-dom'
import { apiRequest } from '../services/api'

function ForgotPasswordPage() {
  const [email, setEmail] = useState('')
  const [message, setMessage] =
    useState('')
  const [loading, setLoading] =
    useState(false)

  const handleSubmit = async (event) => {
    event.preventDefault()

    setLoading(true)
    setMessage('')

    try {
      const { response, data } =
        await apiRequest(
          '/api/auth/forgot-password',
          {
            method: 'POST',
            body: JSON.stringify({
              email,
            }),
          }
        )

      if (!response.ok || !data.success) {
        setMessage(
          data.message ||
            'Không thể gửi yêu cầu đặt lại mật khẩu.'
        )
        return
      }

      setMessage(
        'Nếu email tồn tại, liên kết đặt lại mật khẩu đã được gửi.'
      )
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
        <h1>Quên mật khẩu</h1>

        <p className="auth-description">
          Nhập email của bạn để nhận
          liên kết đặt lại mật khẩu.
        </p>

        <form onSubmit={handleSubmit}>
          <label>Email</label>

          <input
            type="email"
            value={email}
            onChange={(event) =>
              setEmail(event.target.value)
            }
            placeholder="Nhập email"
            required
          />

          <button
            type="submit"
            disabled={loading}
          >
            {loading
              ? 'Đang gửi...'
              : 'Gửi liên kết đặt lại'}
          </button>
        </form>

        {message && (
          <p className="message">
            {message}
          </p>
        )}

        <div className="auth-links">
          <Link to="/login">
            ← Quay lại đăng nhập
          </Link>
        </div>
      </div>
    </div>
  )
}

export default ForgotPasswordPage