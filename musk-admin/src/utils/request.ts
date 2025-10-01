import axios, { AxiosRequestConfig, AxiosResponse } from 'axios'
import { ElMessage } from 'element-plus'

// 创建axios实例
const request = axios.create({
  baseURL: '/api',
  timeout: 30000, // 增加超时时间
  headers: {
    'Content-Type': 'application/json'
  },
  // 安全配置
  withCredentials: false, // 不发送cookies
  maxRedirects: 3, // 最大重定向次数
  validateStatus: (status) => status >= 200 && status < 300 // 默认只接受2xx状态码
})

// 请求拦截器
request.interceptors.request.use(
  (config: AxiosRequestConfig) => {
    try {
      // 安全地从localStorage获取token
      const token = localStorage.getItem('token')
      if (token && typeof token === 'string' && token.trim()) {
        config.headers = config.headers || {}
        config.headers.Authorization = `Bearer ${token.trim()}`
      }
      
      // 添加请求ID用于追踪
      config.headers = config.headers || {}
      config.headers['X-Request-ID'] = `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`
      
      // 添加时间戳防止缓存
      if (config.method === 'get') {
        config.params = {
          ...config.params,
          _t: Date.now()
        }
      }
      
      return config
    } catch (error) {
      console.error('请求拦截器错误:', error)
      return config
    }
  },
  (error) => {
    console.error('请求拦截器错误:', error)
    return Promise.reject(error)
  }
)

// 响应拦截器
request.interceptors.response.use(
  (response: AxiosResponse) => {
    try {
      const { data } = response
      
      // 验证响应数据格式
      if (!data || typeof data !== 'object') {
        throw new Error('响应数据格式无效')
      }
      
      // 统一处理响应格式
      if (data.code === 200) {
        return response
      } else if (data.code === 401) {
        // token过期，清除本地存储
        const url = response.config?.url || ''
        try {
          localStorage.removeItem('token')
          localStorage.removeItem('userInfo')
        } catch (error) {
          console.error('清除本地存储失败:', error)
        }
        
        // 避免与登录页自己的错误提示重复
        if (!/\/login$/.test(url)) {
          ElMessage.error('登录已过期，请重新登录')
        }
        return Promise.reject(new Error(data.message || '登录已过期'))
      } else if (data.code === 403) {
        ElMessage.error(data.message || '权限不足')
        return Promise.reject(new Error(data.message || '权限不足'))
      } else {
        ElMessage.error(data.message || '请求失败')
        return Promise.reject(new Error(data.message || '请求失败'))
      }
    } catch (error) {
      console.error('响应拦截器错误:', error)
      ElMessage.error('响应数据解析失败')
      return Promise.reject(error)
    }
  },
  (error) => {
    console.error('请求错误:', error)
    
    // 处理不同类型的错误
    if (error.response) {
      // 服务器响应了错误状态码
      const status = error.response.status
      const message = error.response.data?.message || `服务器错误 (${status})`
      
      switch (status) {
        case 401:
          ElMessage.error('未授权，请重新登录')
          break
        case 403:
          ElMessage.error('权限不足')
          break
        case 404:
          ElMessage.error('请求的资源不存在')
          break
        case 500:
          ElMessage.error('服务器内部错误')
          break
        case 502:
          ElMessage.error('网关错误')
          break
        case 503:
          ElMessage.error('服务不可用')
          break
        default:
          ElMessage.error(message)
      }
    } else if (error.request) {
      // 请求已发出但没有收到响应
      ElMessage.error('网络连接超时，请检查网络连接')
    } else {
      // 其他错误
      ElMessage.error(error.message || '请求失败')
    }
    
    return Promise.reject(error)
  }
)

export default request
