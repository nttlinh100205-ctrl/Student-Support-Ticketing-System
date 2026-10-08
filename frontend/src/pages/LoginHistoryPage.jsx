import { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import {
  apiRequest,
  clearAuth,
} from '../services/api'

function LoginHistoryPage() {
  const navigate = useNavigate()

  const [histories, setHistories] = useState([])
  const [message, setMessage] = useState(
    'Đang tải lịch sử đăng nhập...'
  )

  useEffect(() => {
    const loadHistory = async () => {
      try {
        const { response, data } =
          await apiRequest(
            '/api/profile/login-history'
          )

        if (response.status === 401) {
          clearAuth()

          navigate('/login', {
            replace: true,
          })

          return
        }

        if (!response.ok || !data.success) {
          setMessage(
            data.message ||
              'Không thể tải lịch sử đăng nhập.'
          )

          return
        }

        setHistories(data.data || [])
        setMessage('')
      } catch {
        setMessage(
          'Không thể kết nối tới hệ thống.'
        )
      }
    }

    loadHistory()
  }, [navigate])

  const formatDate = (value) => {
    if (!value) {
      return '-'
    }

    return new Date(value).toLocaleString(
      'vi-VN',
      {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
      }
    )
  }

  const formatReason = (reason) => {
    if (!reason) {
      return '-'
    }

    const reasons = {
      INVALID_CREDENTIALS:
        'Sai email hoặc mật khẩu',

      ACCOUNT_LOCKED:
        'Tài khoản đã bị khóa',

      TEMPORARILY_LOCKED:
        'Tài khoản tạm thời bị khóa',
    }

    return reasons[reason] || reason
  }

  return (
    <div className="history-page">
      <div className="history-card">
        <div className="history-header">
          <div>
            <p className="history-subtitle">
              Tài khoản cá nhân
            </p>

            <h1>
              Lịch sử đăng nhập
            </h1>

            <p className="history-description">
              Theo dõi các lần đăng nhập gần đây
              của tài khoản.
            </p>
          </div>

          <button
            type="button"
            className="history-back-button"
            onClick={() =>
              navigate('/profile')
            }
          >
            ← Quay lại hồ sơ
          </button>
        </div>

        {message && (
          <div className="history-message">
            {message}
          </div>
        )}

        {!message &&
          histories.length === 0 && (
            <div className="history-empty">
              Chưa có lịch sử đăng nhập.
            </div>
          )}

        {histories.length > 0 && (
          <>
            <div className="history-summary">
              Có{' '}
              <strong>
                {histories.length}
              </strong>{' '}
              lần đăng nhập gần đây
            </div>

            <div className="history-table-wrapper">
              <table className="history-table">
                <thead>
                  <tr>
                    <th>Thời gian</th>
                    <th>Trạng thái</th>
                    <th>Địa chỉ IP</th>
                    <th>Lý do</th>
                  </tr>
                </thead>

                <tbody>
                  {histories.map(
                    (history) => (
                      <tr key={history.id}>
                        <td>
                          <span className="history-time">
                            {formatDate(
                              history.created_at
                            )}
                          </span>
                        </td>

                        <td>
                          <span
                            className={
                              history.success
                                ? 'status-badge status-success'
                                : 'status-badge status-failed'
                            }
                          >
                            {history.success
                              ? 'Thành công'
                              : 'Thất bại'}
                          </span>
                        </td>

                        <td>
                          <span className="history-ip">
                            {history.ip_address ||
                              '-'}
                          </span>
                        </td>

                        <td>
                          <span className="history-reason">
                            {formatReason(
                              history.failure_reason
                            )}
                          </span>
                        </td>
                      </tr>
                    )
                  )}
                </tbody>
              </table>
            </div>
          </>
        )}
      </div>
    </div>
  )
}

export default LoginHistoryPage