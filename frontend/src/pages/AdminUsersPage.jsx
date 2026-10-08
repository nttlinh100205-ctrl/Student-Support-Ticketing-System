import {
  useEffect,
  useState,
} from 'react'
import {
  useNavigate,
} from 'react-router-dom'

import {
  apiRequest,
  clearAuth,
} from '../services/api'

function AdminUsersPage() {
  const navigate = useNavigate()

  const [users, setUsers] = useState([])
  const [loading, setLoading] =
    useState(true)

  const [message, setMessage] =
    useState('')

  const [search, setSearch] =
    useState('')

  useEffect(() => {
    const loadUsers = async () => {
      try {
        const { response, data } =
          await apiRequest('/api/users')

        if (response.status === 401) {
          clearAuth()

          navigate('/login', {
            replace: true,
          })

          return
        }

        if (response.status === 403) {
          navigate('/profile', {
            replace: true,
          })

          return
        }

        if (
          !response.ok ||
          !data.success
        ) {
          setMessage(
            data.message ||
              'Không thể tải danh sách tài khoản.'
          )

          return
        }

        const result = data.data

        if (Array.isArray(result)) {
          setUsers(result)
        } else if (
          Array.isArray(result?.data)
        ) {
          setUsers(result.data)
        } else {
          setUsers([])
        }
      } catch {
        setMessage(
          'Không thể kết nối tới hệ thống.'
        )
      } finally {
        setLoading(false)
      }
    }

    loadUsers()
  }, [navigate])

  const reloadUsers = async () => {
    try {
      const { response, data } =
        await apiRequest('/api/users')

      if (!response.ok || !data.success) {
        return
      }

      const result = data.data

      if (Array.isArray(result)) {
        setUsers(result)
      } else if (
        Array.isArray(result?.data)
      ) {
        setUsers(result.data)
      }
    } catch {
      // Giữ nguyên danh sách hiện tại
    }
  }

  const handleChangeRole = async (
    user
  ) => {
    const role = window.prompt(
      [
        'Nhập vai trò mới:',
        '',
        'student',
        'staff',
        'department_head',
        'admin',
      ].join('\n'),
      user.role
    )

    if (!role) {
      return
    }

    const validRoles = [
      'student',
      'staff',
      'department_head',
      'admin',
    ]

    if (!validRoles.includes(role)) {
      setMessage(
        'Vai trò không hợp lệ.'
      )
      return
    }

    let departmentId = null

    if (
      role === 'staff' ||
      role === 'department_head'
    ) {
      const input =
        window.prompt(
          'Nhập Department ID:',
          user.department_id || ''
        )

      if (
        input === null ||
        input.trim() === ''
      ) {
        setMessage(
          'Vai trò này cần Department ID.'
        )
        return
      }

      departmentId = Number(input)

      if (
        Number.isNaN(departmentId) ||
        departmentId <= 0
      ) {
        setMessage(
          'Department ID không hợp lệ.'
        )
        return
      }
    }

    setMessage('Đang cập nhật vai trò...')

    try {
      const { response, data } =
        await apiRequest(
          `/api/users/${user.id}/role`,
          {
            method: 'PUT',
            body: JSON.stringify({
              role,
              department_id:
                departmentId,
            }),
          }
        )

      if (!response.ok || !data.success) {
        setMessage(
          data.message ||
            'Cập nhật vai trò thất bại.'
        )

        return
      }

      setMessage(
        'Cập nhật vai trò thành công.'
      )

      await reloadUsers()
    } catch {
      setMessage(
        'Không thể kết nối tới hệ thống.'
      )
    }
  }

  const handleChangeStatus = async (
    user
  ) => {
    const currentStatus =
      String(
        user.status || ''
      ).toUpperCase()

    const newStatus =
      currentStatus === 'ACTIVE'
        ? 'LOCKED'
        : 'ACTIVE'

    const actionText =
      newStatus === 'LOCKED'
        ? 'khóa'
        : 'mở khóa'

    const confirmed =
      window.confirm(
        `Bạn có chắc muốn ${actionText} tài khoản ${user.email}?`
      )

    if (!confirmed) {
      return
    }

    setMessage(
      `Đang ${actionText} tài khoản...`
    )

    try {
      const { response, data } =
        await apiRequest(
          `/api/users/${user.id}/status`,
          {
            method: 'PUT',
            body: JSON.stringify({
              status: newStatus,
            }),
          }
        )

      if (!response.ok || !data.success) {
        setMessage(
          data.message ||
            `Không thể ${actionText} tài khoản.`
        )

        return
      }

      setMessage(
        `${
          newStatus === 'LOCKED'
            ? 'Khóa'
            : 'Mở khóa'
        } tài khoản thành công.`
      )

      await reloadUsers()
    } catch {
      setMessage(
        'Không thể kết nối tới hệ thống.'
      )
    }
  }

  const handleLogout = () => {
    clearAuth()

    navigate('/login', {
      replace: true,
    })
  }

  const filteredUsers =
    users.filter((user) => {
      const keyword =
        search
          .trim()
          .toLowerCase()

      if (!keyword) {
        return true
      }

      return (
        String(
          user.full_name || ''
        )
          .toLowerCase()
          .includes(keyword) ||
        String(
          user.email || ''
        )
          .toLowerCase()
          .includes(keyword) ||
        String(
          user.role || ''
        )
          .toLowerCase()
          .includes(keyword)
      )
    })

  const roleLabel = (role) => {
    const labels = {
      admin: 'Quản trị viên',
      student: 'Sinh viên',
      staff: 'Nhân viên',
      department_head:
        'Trưởng đơn vị',
    }

    return labels[role] || role
  }

  return (
    <div className="admin-page">
      <div className="admin-container">
        <div className="admin-header">
          <div>
            <p className="admin-eyebrow">
              STUDENT SUPPORT SYSTEM
            </p>

            <h1>
              Quản lý tài khoản
            </h1>

            <p className="admin-description">
              Quản lý người dùng, vai trò
              và trạng thái tài khoản.
            </p>
          </div>

          <div className="admin-header-actions">
            <button
              type="button"
              className="admin-secondary-button"
              onClick={() =>
                navigate('/profile')
              }
            >
              Hồ sơ cá nhân
            </button>

            <button
              type="button"
              className="admin-logout-button"
              onClick={handleLogout}
            >
              Đăng xuất
            </button>
          </div>
        </div>

        <div className="admin-toolbar">
          <div>
            <strong>
              Danh sách người dùng
            </strong>

            <span className="admin-count">
              {filteredUsers.length}
            </span>
          </div>

          <input
            className="admin-search"
            type="text"
            value={search}
            onChange={(event) =>
              setSearch(
                event.target.value
              )
            }
            placeholder="Tìm theo tên, email, vai trò..."
          />
        </div>

        {message && (
          <div className="admin-message">
            {message}
          </div>
        )}

        {loading ? (
          <div className="admin-empty">
            Đang tải danh sách tài khoản...
          </div>
        ) : filteredUsers.length === 0 ? (
          <div className="admin-empty">
            Không tìm thấy tài khoản.
          </div>
        ) : (
          <div className="admin-table-wrapper">
            <table className="admin-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Người dùng</th>
                  <th>Vai trò</th>
                  <th>Phòng ban</th>
                  <th>Trạng thái</th>
                  <th>Thao tác</th>
                </tr>
              </thead>

              <tbody>
                {filteredUsers.map(
                  (user) => {
                    const active =
                      String(
                        user.status ||
                          ''
                      ).toUpperCase() ===
                      'ACTIVE'

                    return (
                      <tr key={user.id}>
                        <td>
                          #{user.id}
                        </td>

                        <td>
                          <div className="admin-user">
                            <strong>
                              {user.full_name}
                            </strong>

                            <span>
                              {user.email}
                            </span>
                          </div>
                        </td>

                        <td>
                          <span className="role-badge">
                            {roleLabel(
                              user.role
                            )}
                          </span>
                        </td>

                        <td>
                          {user.department_id ||
                            '-'}
                        </td>

                        <td>
                          <span
                            className={
                              active
                                ? 'account-status account-active'
                                : 'account-status account-locked'
                            }
                          >
                            {active
                              ? 'Hoạt động'
                              : 'Đã khóa'}
                          </span>
                        </td>

                        <td>
                          <div className="admin-actions">
                            <button
                              type="button"
                              className="table-button"
                              onClick={() =>
                                handleChangeRole(
                                  user
                                )
                              }
                            >
                              Đổi vai trò
                            </button>

                            <button
                              type="button"
                              className={
                                active
                                  ? 'table-button danger-button'
                                  : 'table-button success-button'
                              }
                              onClick={() =>
                                handleChangeStatus(
                                  user
                                )
                              }
                            >
                              {active
                                ? 'Khóa'
                                : 'Mở khóa'}
                            </button>
                          </div>
                        </td>
                      </tr>
                    )
                  }
                )}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  )
}

export default AdminUsersPage