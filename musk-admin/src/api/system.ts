import request from '@/utils/request'

// 系统信息接口
export interface SystemInfo {
  system: {
    name: string
    version: string
    php_version: string
    webman_version: string
    server_software: string
    server_os: string
    timezone: string
    upload_max_filesize: string
    post_max_size: string
    memory_limit: string
    max_execution_time: string
  }
  database: {
    type: string
    version: string
    charset: string
    collation: string
  }
  environment: {
    app_env: string
    app_debug: boolean
    timezone: string
    locale: string
  }
  storage: {
    total_space: string
    free_space: string
    used_space: string
  }
  statistics: {
    total_admins: number
    total_roles: number
    total_permissions: number
    total_logs: number
  }
}

// 系统配置接口
export interface SystemConfig {
  app: {
    name: string
    debug: boolean
    timezone: string
    locale: string
  }
  database: {
    host: string
    port: number
    database: string
    charset: string
  }
  jwt: {
    secret: string | null
    expire: number
    algorithm: string
  }
  cache: {
    driver: string
    ttl: number
  }
  session: {
    driver: string
    lifetime: number
    secure: boolean
  }
  cors: {
    allowed_origins: string[]
    allowed_methods: string[]
    allowed_headers: string[]
  }
}

// 系统状态接口
export interface SystemStatus {
  system: {
    status: string
    uptime: string
    load_average: {
      '1min': number
      '5min': number
      '15min': number
    }
    memory_usage: {
      current: string
      peak: string
      limit: string
      usage_percent: number
    }
    cpu_usage: number
  }
  database: {
    status: string
    connections: number
    slow_queries: number
  }
  cache: {
    status: string
    hit_rate: number
  }
  storage: {
    status: string
    usage_percent: number
  }
}

// 系统健康检查接口
export interface SystemHealth {
  status: 'healthy' | 'unhealthy' | 'critical'
  checks: {
    database: {
      status: string
      message: string
    }
    cache: {
      status: string
      message: string
    }
    storage: {
      status: string
      message: string
    }
    memory: {
      status: string
      message: string
    }
  }
  timestamp: string
  uptime: string
}

// 获取系统信息
export function getSystemInfo(): Promise<{ data: SystemInfo }> {
  return request.get('/system/info')
}

// 获取系统配置
export function getSystemConfig(): Promise<{ data: SystemConfig }> {
  return request.get('/system/config')
}

// 更新系统配置
export function updateSystemConfig(config: Partial<SystemConfig>): Promise<{ data: null }> {
  return request.post('/system/config', { config })
}

// 获取系统状态
export function getSystemStatus(): Promise<{ data: SystemStatus }> {
  return request.get('/system/status')
}

// 系统健康检查
export function getSystemHealth(): Promise<{ data: SystemHealth }> {
  return request.get('/system/health')
}

// 清理系统缓存
export function clearSystemCache(type: 'all' | 'permission' | 'menu' | 'system' = 'all'): Promise<{ data: { cleared: string[] } }> {
  return request.post('/system/clear-cache', { type })
}

// 获取系统统计
export function getSystemStats(): Promise<{ data: any }> {
  return request.get('/system/stats')
}

// 系统备份
export function createSystemBackup(): Promise<{ data: { backup_id: string; message: string } }> {
  return request.post('/system/backup')
}

// 获取备份列表
export function getBackupList(): Promise<{ data: Array<{ id: string; name: string; size: string; created_at: string }> }> {
  return request.get('/system/backups')
}

// 恢复系统备份
export function restoreSystemBackup(backupId: string): Promise<{ data: null }> {
  return request.post('/system/restore', { backup_id: backupId })
}

// 删除系统备份
export function deleteSystemBackup(backupId: string): Promise<{ data: null }> {
  return request.delete(`/system/backups/${backupId}`)
}

// 获取系统日志
export function getSystemLogs(params?: { 
  level?: string
  start_date?: string
  end_date?: string
  page?: number
  limit?: number
}): Promise<{ data: Array<{ level: string; message: string; timestamp: string; context: any }> }> {
  return request.get('/system/logs', { params })
}

// 下载系统日志
export function downloadSystemLogs(params?: { 
  level?: string
  start_date?: string
  end_date?: string
}): Promise<Blob> {
  return request.get('/system/logs/download', { 
    params, 
    responseType: 'blob' 
  })
}

// 获取系统通知
export function getSystemNotifications(): Promise<{ data: Array<{ id: string; title: string; message: string; type: string; created_at: string; read: boolean }> }> {
  return request.get('/system/notifications')
}

// 标记通知为已读
export function markNotificationRead(notificationId: string): Promise<{ data: null }> {
  return request.put(`/system/notifications/${notificationId}/read`)
}

// 标记所有通知为已读
export function markAllNotificationsRead(): Promise<{ data: null }> {
  return request.put('/system/notifications/read-all')
}

// 删除通知
export function deleteNotification(notificationId: string): Promise<{ data: null }> {
  return request.delete(`/system/notifications/${notificationId}`)
}

// 获取系统设置
export function getSystemSettings(): Promise<{ data: any }> {
  return request.get('/system/settings')
}

// 更新系统设置
export function updateSystemSettings(settings: any): Promise<{ data: null }> {
  return request.post('/system/settings', { settings })
}

// 重置系统设置
export function resetSystemSettings(): Promise<{ data: null }> {
  return request.post('/system/settings/reset')
}

// 获取系统版本信息
export function getSystemVersion(): Promise<{ data: { current: string; latest: string; update_available: boolean } }> {
  return request.get('/system/version')
}

// 检查系统更新
export function checkSystemUpdate(): Promise<{ data: { update_available: boolean; latest_version: string; changelog: string } }> {
  return request.get('/system/check-update')
}

// 执行系统更新
export function performSystemUpdate(): Promise<{ data: { success: boolean; message: string } }> {
  return request.post('/system/update')
}
