import { defineStore } from 'pinia'
import { ref } from 'vue'
import { login, logout, getUserInfo, type LoginData, type UserInfo } from '@/api/auth'

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string>('')
  const userInfo = ref<UserInfo | null>(null)
  const isLoggedIn = ref<boolean>(false)

  // 安全地从localStorage获取token
  const getStoredToken = (): string => {
    try {
      const storedToken = localStorage.getItem('token')
      return storedToken && typeof storedToken === 'string' ? storedToken : ''
    } catch (error) {
      console.error('获取存储的token失败:', error)
      return ''
    }
  }

  // 安全地保存token
  const setStoredToken = (newToken: string): void => {
    try {
      if (newToken && typeof newToken === 'string') {
        localStorage.setItem('token', newToken)
      }
    } catch (error) {
      console.error('保存token失败:', error)
    }
  }

  // 安全地清除token
  const clearStoredToken = (): void => {
    try {
      localStorage.removeItem('token')
      localStorage.removeItem('userInfo')
    } catch (error) {
      console.error('清除token失败:', error)
    }
  }

  // 初始化token状态
  token.value = getStoredToken()
  isLoggedIn.value = !!token.value

  // 登录
  const loginAction = async (data: LoginData) => {
    try {
      // 输入验证
      if (!data || typeof data !== 'object') {
        throw new Error('登录数据无效')
      }
      
      const response = await login(data)
      
      // 响应验证
      if (!response?.data?.data?.token) {
        throw new Error('登录响应数据无效')
      }
      
      token.value = response.data.data.token
      userInfo.value = response.data.data.user
      isLoggedIn.value = true
      
      // 安全地保存到localStorage
      setStoredToken(response.data.data.token)
      try {
        localStorage.setItem('userInfo', JSON.stringify(response.data.data.user))
      } catch (error) {
        console.error('保存用户信息失败:', error)
      }
      
      return response
    } catch (error) {
      // 登录失败时清除状态
      token.value = ''
      userInfo.value = null
      isLoggedIn.value = false
      throw error
    }
  }

  // 登出
  const logoutAction = async () => {
    try {
      // 如果有token，尝试调用登出API
      if (token.value) {
        await logout()
      }
    } catch (error) {
      console.error('登出失败:', error)
    } finally {
      // 清除状态
      token.value = ''
      userInfo.value = null
      isLoggedIn.value = false
      
      // 安全地清除localStorage
      clearStoredToken()
    }
  }

  // 获取用户信息
  const getUserInfoAction = async () => {
    try {
      const response = await getUserInfo()
      
      // 响应验证
      if (!response?.data?.data) {
        throw new Error('用户信息响应数据无效')
      }
      
      userInfo.value = response.data.data
      
      // 安全地保存用户信息
      try {
        localStorage.setItem('userInfo', JSON.stringify(response.data.data))
      } catch (error) {
        console.error('保存用户信息失败:', error)
      }
      
      return response
    } catch (error) {
      // 如果获取用户信息失败，清除登录状态
      await logoutAction()
      throw error
    }
  }

  // 初始化用户信息
  const initUserInfo = async () => {
    try {
      const savedUserInfo = localStorage.getItem('userInfo')
      if (savedUserInfo) {
        const parsedUserInfo = JSON.parse(savedUserInfo)
        if (parsedUserInfo && typeof parsedUserInfo === 'object') {
          userInfo.value = parsedUserInfo
        } else {
          // 数据格式无效，清除
          localStorage.removeItem('userInfo')
        }
      }
    } catch (error) {
      console.error('解析用户信息失败:', error)
      localStorage.removeItem('userInfo')
    }
  }

  return {
    token,
    userInfo,
    isLoggedIn,
    loginAction,
    logoutAction,
    getUserInfoAction,
    initUserInfo
  }
})
