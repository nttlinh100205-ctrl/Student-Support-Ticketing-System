import {
  useEffect,
  useState,
} from 'react'

import {
  useNavigate,
} from 'react-router-dom'

import {
  API_BASE_URL,
  apiRequest,
  clearAuth,
  getCurrentUser,
} from '../services/api'

function ProfilePage() {
  const navigate = useNavigate()

  const [profile, setProfile] =
    useState(null)

  const [form, setForm] =
    useState({
      full_name: '',
      email: '',
      phone: '',
    })

  const [avatarFile, setAvatarFile] =
    useState(null)

  const [avatarPreview, setAvatarPreview] =
    useState(null)

  const [message, setMessage] =
    useState('Đang tải hồ sơ...')

  const [success, setSuccess] =
    useState(false)

  const [saving, setSaving] =
    useState(false)

  const avatarUrl = (user) => {
    if (!user?.avatar) {
      return null
    }

    return `${API_BASE_URL}/media/${user.avatar}`
  }

  const avatarUrlWithCacheBust = (user) => {
    const url = avatarUrl(user)

    if (!url) {
      return null
    }

    return `${url}?v=${Date.now()}`
  }

  useEffect(() => {
    const loadProfile = async () => {
      try {
        const { response, data } =
          await apiRequest(
            '/api/profile'
          )

        if (response.status === 401) {
          clearAuth()

          navigate('/login', {
            replace: true,
          })

          return
        }

        if (
          response.status === 403 &&
          getCurrentUser()
            ?.must_change_password
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
          !response.ok ||
          !data.success
        ) {
          setSuccess(false)

          setMessage(
            data.message ||
              'Không thể tải hồ sơ.'
          )

          return
        }

        const user = data.data

        setProfile(user)

        setForm({
          full_name:
            user.full_name || '',
          email:
            user.email || '',
          phone:
            user.phone || '',
        })

        setAvatarPreview(
          avatarUrlWithCacheBust(
            user
          )
        )

        setMessage('')
      } catch {
        setSuccess(false)

        setMessage(
          'Không thể kết nối tới hệ thống.'
        )
      }
    }

    loadProfile()
  }, [navigate])

  const updateStoredUser = (
    updatedUser
  ) => {
    const stored =
      getCurrentUser()

    if (!stored) {
      return
    }

    localStorage.setItem(
      'user',
      JSON.stringify({
        ...stored,
        full_name:
          updatedUser.full_name,
        email:
          updatedUser.email,
        role:
          updatedUser.role,
        must_change_password:
          updatedUser
            .must_change_password,
      })
    )
  }

  const handleInputChange = (
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

  const handleUpdateProfile =
    async (event) => {
      event.preventDefault()

      setSaving(true)
      setSuccess(false)
      setMessage('')

      try {
        const { response, data } =
          await apiRequest(
            '/api/profile',
            {
              method: 'PUT',
              body: JSON.stringify(
                form
              ),
            }
          )

        if (
          !response.ok ||
          !data.success
        ) {
          setMessage(
            data.message ||
              'Cập nhật hồ sơ thất bại.'
          )

          return
        }

        setProfile(data.data)

        setForm({
          full_name:
            data.data.full_name ||
            '',
          email:
            data.data.email || '',
          phone:
            data.data.phone || '',
        })

        updateStoredUser(
          data.data
        )

        setSuccess(true)

        setMessage(
          'Cập nhật hồ sơ thành công.'
        )
      } catch {
        setSuccess(false)

        setMessage(
          'Không thể kết nối tới hệ thống.'
        )
      } finally {
        setSaving(false)
      }
    }

  const handleAvatarChange = (
    event
  ) => {
    const file =
      event.target.files?.[0]

    if (!file) {
      return
    }

    const validTypes = [
      'image/jpeg',
      'image/png',
      'image/webp',
    ]

    if (
      !validTypes.includes(
        file.type
      )
    ) {
      setSuccess(false)

      setMessage(
        'Ảnh chỉ được phép là JPG, JPEG, PNG hoặc WEBP.'
      )

      event.target.value = ''

      return
    }

    if (
      file.size >
      2 * 1024 * 1024
    ) {
      setSuccess(false)

      setMessage(
        'Ảnh đại diện không được vượt quá 2MB.'
      )

      event.target.value = ''

      return
    }

    setAvatarFile(file)

    setAvatarPreview(
      URL.createObjectURL(file)
    )

    setSuccess(false)
    setMessage('')
  }

  const handleUploadAvatar =
    async () => {
      if (!avatarFile) {
        setSuccess(false)

        setMessage(
          'Vui lòng chọn ảnh đại diện.'
        )

        return
      }

      setSaving(true)
      setSuccess(false)
      setMessage('')

      const formData =
        new FormData()

      formData.append(
        'avatar',
        avatarFile
      )

      try {
        const { response, data } =
          await apiRequest(
            '/api/profile/avatar',
            {
              method: 'POST',
              body: formData,
            }
          )

        if (
          !response.ok ||
          !data.success
        ) {
          setMessage(
            data.message ||
              'Cập nhật ảnh đại diện thất bại.'
          )

          return
        }

        const updatedUser =
          data.data

        setProfile(
          updatedUser
        )

        setAvatarFile(null)

        setAvatarPreview(
          avatarUrlWithCacheBust(
            updatedUser
          )
        )

        setSuccess(true)

        setMessage(
          'Cập nhật ảnh đại diện thành công.'
        )
      } catch {
        setSuccess(false)

        setMessage(
          'Không thể kết nối tới hệ thống.'
        )
      } finally {
        setSaving(false)
      }
    }

  const handleLogout = () => {
    clearAuth()

    navigate('/login', {
      replace: true,
    })
  }

  const handleLogoutAll =
    async () => {
      try {
        const { response, data } =
          await apiRequest(
            '/api/auth/logout-all',
            {
              method: 'POST',
            }
          )

        if (!response.ok) {
          setSuccess(false)

          setMessage(
            data.message ||
              'Đăng xuất tất cả thiết bị thất bại.'
          )

          return
        }

        clearAuth()

        navigate('/login', {
          replace: true,
        })
      } catch {
        setSuccess(false)

        setMessage(
          'Không thể kết nối tới hệ thống.'
        )
      }
    }

  const initial =
    profile?.full_name
      ?.trim()
      ?.charAt(0)
      ?.toUpperCase() || '?'

  return (
    <div className="page">
      <div className="profile-card profile-edit-card">
        <h1>
          Hồ sơ cá nhân
        </h1>

        <div className="profile-avatar-section">
          <div className="profile-avatar">
            {avatarPreview ? (
              <img
                src={avatarPreview}
                alt="Ảnh đại diện"
                onError={() => {
                  setAvatarPreview(
                    null
                  )
                }}
              />
            ) : (
              <span>
                {initial}
              </span>
            )}
          </div>

          <div className="avatar-controls">
            <label
              className="avatar-file-label"
              htmlFor="avatar"
            >
              Chọn ảnh đại diện
            </label>

            <input
              id="avatar"
              className="avatar-file-input"
              type="file"
              accept=".jpg,.jpeg,.png,.webp"
              onChange={
                handleAvatarChange
              }
            />

            <p className="avatar-help">
              JPG, JPEG, PNG hoặc WEBP.
              Tối đa 2MB.
            </p>

            <button
              type="button"
              className="avatar-upload-button"
              onClick={
                handleUploadAvatar
              }
              disabled={
                !avatarFile ||
                saving
              }
            >
              {saving
                ? 'Đang cập nhật...'
                : 'Cập nhật ảnh'}
            </button>
          </div>
        </div>

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

        {profile && (
          <form
            onSubmit={
              handleUpdateProfile
            }
            className="profile-form"
          >
            <div className="profile-form-grid">
              <div>
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
                    handleInputChange
                  }
                  required
                />
              </div>

              <div>
                <label>
                  Email
                </label>

                <input
                  type="email"
                  name="email"
                  value={
                    form.email
                  }
                  onChange={
                    handleInputChange
                  }
                  required
                />
              </div>

              <div>
                <label>
                  Số điện thoại
                </label>

                <input
                  type="text"
                  name="phone"
                  value={
                    form.phone
                  }
                  onChange={
                    handleInputChange
                  }
                  placeholder="Chưa cập nhật"
                />
              </div>

              <div>
                <label>
                  Vai trò
                </label>

                <input
                  type="text"
                  value={
                    profile.role
                  }
                  disabled
                />
              </div>
            </div>

            <button
              type="submit"
              disabled={saving}
            >
              {saving
                ? 'Đang cập nhật...'
                : 'Cập nhật hồ sơ'}
            </button>
          </form>
        )}

        <div className="profile-navigation">
          {profile?.role ===
            'admin' && (
            <button
              type="button"
              onClick={() =>
                navigate(
                  '/admin/users'
                )
              }
            >
              Quản lý tài khoản
            </button>
          )}

          <button
            type="button"
            onClick={() =>
              navigate(
                '/login-history'
              )
            }
          >
            Lịch sử đăng nhập
          </button>

          <button
            type="button"
            onClick={() =>
              navigate(
                '/change-password'
              )
            }
          >
            Đổi mật khẩu
          </button>
        </div>

        <div className="profile-session-actions">
          <button
            type="button"
            onClick={
              handleLogout
            }
          >
            Đăng xuất
          </button>

          <button
            type="button"
            className="danger-session-button"
            onClick={
              handleLogoutAll
            }
          >
            Đăng xuất tất cả thiết bị
          </button>
        </div>
      </div>
    </div>
  )
}

export default ProfilePage