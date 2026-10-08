import { useState } from 'react'
import {
  Link,
  useNavigate,
  useParams,
  useSearchParams,
} from 'react-router-dom'
import { apiRequest } from '../services/api'

function ResetPasswordPage() {
  const navigate = useNavigate()
  const { token } = useParams()

  const [searchParams] =
    useSearchParams()

  const [email, setEmail] =
    useState(
      searchParams.get('email') || ''
    )

  const [password, setPassword] =
    useState('')

  const [
    passwordConfirmation,
    setPasswordConfirmation,
  ] = useState('')

  const [message, setMessage] =
    useState('')

  const [success, setSuccess] =
    useState(false)

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
    setSuccess(false)

    try {
      const { response, data } =
        await apiRequest(
          '/api/auth/reset-password',
          {
            method: 'POST',
            body: JSON.stringify({
              token,
              email,
              password,
              password_confirmation:
                passwordConfirmation,
            }),
          }
        )

      if (!response.ok || !data.success) {
        setMessage(
          data.message ||
            'Không thể đặt lại mật khẩu.'
        )
        return
      }

      setSuccess(true)

      setMessage(
        'Đặt lại mật khẩu thành công.'
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
        <h1>
          Đặt lại mật khẩu
        </h1>

        {!success ? (
          <form onSubmit={handleSubmit}>
            <label>Email</label>

            <input
              type="email"
              value={email}
              onChange={(event) =>
                setEmail(
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
              placeholder="Tối thiểu 8 ký tự"
              minLength="8"
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
              minLength="8"
              required
            />

            <button
              type="submit"
              disabled={loading}
            >
              {loading
                ? 'Đang xử lý...'
                : 'Đặt lại mật khẩu'}
            </button>
          </form>
        ) : (
          <button
            type="button"
            onClick={() =>
              navigate('/login', {
                replace: true,
              })
            }
          >
            Đăng nhập
          </button>
        )}

        {message && (
          <p
            className={
              success
                ? 'message success-message'
                : 'message'
            }
          >
            {message}
          </p>
        )}

        {!success && (
          <div className="auth-links">
            <Link to="/login">
              ← Quay lại đăng nhập
            </Link>
          </div>
        )}
      </div>
    </div>
  )
}

export default ResetPasswordPage