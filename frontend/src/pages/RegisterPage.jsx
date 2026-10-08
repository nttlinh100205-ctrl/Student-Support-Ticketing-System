import { useState } from 'react'
import {
  Link,
  useNavigate,
} from 'react-router-dom'

import {
  apiRequest,
} from '../services/api'

function RegisterPage() {
  const navigate = useNavigate()

  const [form, setForm] =
    useState({
      full_name: '',
      email: '',
      phone: '',
      password: '',
      password_confirmation: '',
    })

  const [message, setMessage] =
    useState('')

  const [success, setSuccess] =
    useState(false)

  const [loading, setLoading] =
    useState(false)

  const handleChange = (
    event
  ) => {
    const {
      name,
      value,
    } = event.target

    setForm((current) => ({
      ...current,
      [name]: value,
    }))
  }

  const getErrorMessage = (
    data
  ) => {
    if (data?.errors) {
      const firstError =
        Object.values(
          data.errors
        )?.[0]

      if (
        Array.isArray(
          firstError
        ) &&
        firstError.length > 0
      ) {
        return firstError[0]
      }
    }

    return (
      data?.message ||
      'Đăng ký tài khoản thất bại.'
    )
  }

  const handleSubmit = async (
    event
  ) => {
    event.preventDefault()

    if (
      form.password !==
      form.password_confirmation
    ) {
      setSuccess(false)

      setMessage(
        'Mật khẩu xác nhận không khớp.'
      )

      return
    }

    setLoading(true)
    setSuccess(false)
    setMessage('')

    try {
      const {
        response,
        data,
      } = await apiRequest(
        '/api/auth/register',
        {
          method: 'POST',
          body: JSON.stringify({
            full_name:
              form.full_name,
            email:
              form.email,
            phone:
              form.phone.trim()
                ? form.phone
                : null,
            password:
              form.password,
            password_confirmation:
              form.password_confirmation,
          }),
        }
      )

      if (
        !response.ok ||
        !data.success
      ) {
        setMessage(
          getErrorMessage(data)
        )

        return
      }

      setSuccess(true)

      setMessage(
        'Đăng ký tài khoản thành công. Bạn có thể đăng nhập ngay.'
      )
    } catch {
      setSuccess(false)

      setMessage(
        'Không thể kết nối tới hệ thống.'
      )
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="page">
      <div className="login-card register-card">
        <h1>
          Đăng ký tài khoản
        </h1>

        <p className="auth-description">
          Tạo tài khoản sinh viên mới
          để sử dụng hệ thống.
        </p>

        {!success ? (
          <form
            onSubmit={
              handleSubmit
            }
          >
            <label>
              Họ tên
            </label>

            <input
              type="text"
              name="full_name"
              value={
                form.full_name
              }
              onChange={
                handleChange
              }
              placeholder="Nhập họ và tên"
              required
            />

            <label>
              Email
            </label>

            <input
              type="email"
              name="email"
              value={form.email}
              onChange={
                handleChange
              }
              placeholder="Nhập email"
              required
            />

            <label>
              Số điện thoại
            </label>

            <input
              type="text"
              name="phone"
              value={form.phone}
              onChange={
                handleChange
              }
              placeholder="Không bắt buộc"
              maxLength="20"
            />

            <label>
              Mật khẩu
            </label>

            <input
              type="password"
              name="password"
              value={
                form.password
              }
              onChange={
                handleChange
              }
              placeholder="Tối thiểu 8 ký tự"
              minLength="8"
              required
            />

            <label>
              Xác nhận mật khẩu
            </label>

            <input
              type="password"
              name="password_confirmation"
              value={
                form.password_confirmation
              }
              onChange={
                handleChange
              }
              placeholder="Nhập lại mật khẩu"
              minLength="8"
              required
            />

            <button
              type="submit"
              disabled={loading}
            >
              {loading
                ? 'Đang đăng ký...'
                : 'Đăng ký'}
            </button>
          </form>
        ) : (
          <button
            type="button"
            onClick={() =>
              navigate(
                '/login',
                {
                  replace: true,
                }
              )
            }
          >
            Đi tới đăng nhập
          </button>
        )}

        {message && (
          <div
            className={
              success
                ? 'profile-message profile-message-success'
                : 'profile-message profile-message-error'
            }
          >
            {message}
          </div>
        )}

        {!success && (
          <div className="auth-links">
            <Link to="/login">
              Đã có tài khoản? Đăng nhập
            </Link>
          </div>
        )}
      </div>
    </div>
  )
}

export default RegisterPage