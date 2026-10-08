export const API_BASE_URL =
  import.meta.env.VITE_API_BASE_URL ||
  'http://127.0.0.1:8000'

let refreshPromise = null

export function saveAuth(data) {
  if (data.token) {
    localStorage.setItem(
      'access_token',
      data.token
    )
  }

  if (data.refresh_token) {
    localStorage.setItem(
      'refresh_token',
      data.refresh_token
    )
  }

  if (data.user) {
    localStorage.setItem(
      'user',
      JSON.stringify(data.user)
    )
  }
}

export function clearAuth() {
  localStorage.removeItem(
    'access_token'
  )

  localStorage.removeItem(
    'refresh_token'
  )

  localStorage.removeItem(
    'user'
  )
}

export function getCurrentUser() {
  const user =
    localStorage.getItem('user')

  if (!user) {
    return null
  }

  try {
    return JSON.parse(user)
  } catch {
    return null
  }
}

export function isAuthenticated() {
  return Boolean(
    localStorage.getItem(
      'access_token'
    )
  )
}

async function refreshAccessToken() {
  const refreshToken =
    localStorage.getItem(
      'refresh_token'
    )

  if (!refreshToken) {
    clearAuth()
    return false
  }

  try {
    const response = await fetch(
      `${API_BASE_URL}/api/auth/refresh`,
      {
        method: 'POST',

        headers: {
          Accept:
            'application/json',

          'Content-Type':
            'application/json',
        },

        body: JSON.stringify({
          refresh_token:
            refreshToken,
        }),
      }
    )

    const data =
      await response.json()

    if (
      !response.ok ||
      !data.success ||
      !data.data?.token
    ) {
      clearAuth()
      return false
    }

    saveAuth(data.data)

    return true
  } catch {
    clearAuth()
    return false
  }
}

async function ensureRefresh() {
  if (!refreshPromise) {
    refreshPromise =
      refreshAccessToken()
        .finally(() => {
          refreshPromise = null
        })
  }

  return refreshPromise
}

async function sendRequest(
  path,
  options = {}
) {
  const token =
    localStorage.getItem(
      'access_token'
    )

  const headers = {
    Accept: 'application/json',
    ...(options.headers || {}),
  }

  const isFormData =
    options.body instanceof FormData

  if (!isFormData) {
    headers['Content-Type'] =
      headers['Content-Type'] ||
      'application/json'
  }

  if (token) {
    headers.Authorization =
      `Bearer ${token}`
  }

  return fetch(
    `${API_BASE_URL}${path}`,
    {
      ...options,
      headers,
    }
  )
}

export async function apiRequest(
  path,
  options = {}
) {
  let response =
    await sendRequest(
      path,
      options
    )

  /*
   * Khong refresh lai cho chinh
   * cac API xac thuc cong khai.
   */
  const skipRefresh = [
    '/api/auth/login',
    '/api/auth/register',
    '/api/auth/refresh',
    '/api/auth/forgot-password',
    '/api/auth/reset-password',
  ].includes(path)

  if (
    response.status === 401 &&
    !skipRefresh
  ) {
    const refreshed =
      await ensureRefresh()

    if (refreshed) {
      response =
        await sendRequest(
          path,
          options
        )
    }
  }

  let data = null

  try {
    data =
      await response.json()
  } catch {
    data = {
      success: false,
      message:
        'Phản hồi từ máy chủ không hợp lệ.',
    }
  }

  return {
    response,
    data,
  }
}