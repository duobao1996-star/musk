import { defineStore } from 'pinia'
import { ref } from 'vue'
import { login, logout, getUserInfo, type LoginData, type UserInfo } from '@/api/auth'

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string>(localStorage.getItem('token') || '')
  const userInfo = ref<UserInfo | null>(null)
  const isLoggedIn = ref<boolean>(!!token.value)

  // 登录
  const loginAction = async (data: LoginData) => {
    try {
      const response = await login(data)
      token.value = response.data.data.token
      userInfo.value = response.data.data.user
      isLoggedIn.value = true
      
      // 保存到localStorage
      localStorage.setItem('token', response.data.data.token)
      localStorage.setItem('userInfo', JSON.stringify(response.data.data.user))
      
      return response
    } catch (error) {
      throw error
    }
  }

  // 登出
  const logoutAction = async () => {
    try {
      await logout()
    } catch (error) {
      console.error('登出失败:', error)
    } finally {
      // 清除状态
      token.value = ''
      userInfo.value = null
      isLoggedIn.value = false
      
      // 清除localStorage
      localStorage.removeItem('token')
      localStorage.removeItem('userInfo')
    }
  }

  // 获取用户信息
  const getUserInfoAction = async () => {
    try {
      const response = await getUserInfo()
      userInfo.value = response.data.data
      localStorage.setItem('userInfo', JSON.stringify(response.data.data))
      return response
    } catch (error) {
      // 如果获取用户信息失败，清除登录状态
      logoutAction()
      throw error
    }
  }

  // 初始化用户信息
  const initUserInfo = () => {
    const savedUserInfo = localStorage.getItem('userInfo')
    if (savedUserInfo) {
      try {
        userInfo.value = JSON.parse(savedUserInfo)
      } catch (error) {
        console.error('解析用户信息失败:', error)
        localStorage.removeItem('userInfo')
      }
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
