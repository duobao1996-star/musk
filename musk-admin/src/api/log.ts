import request from '@/utils/request'

// 权限日志接口
export interface PermissionLog {
  id: number
  admin_id: number
  admin_name: string
  operation_type: string
  operation_module: string
  operation_desc: string
  request_method: string
  request_url: string
  request_params: string
  response_code: number
  response_msg: string
  ip_address: string
  user_agent: string
  created_at: string
  status: number
}

export interface PermissionLogListParams {
  page?: number
  limit?: number
  admin_id?: number
  operation_type?: string
  operation_module?: string
  admin_name?: string
  dateRange?: string[]
  status?: number
  start_time?: string
  end_time?: string
}

export interface PermissionLogListResponse {
  data: PermissionLog[]
  total: number
  page: number
  limit: number
  pages: number
}

export interface PermissionLogStats {
  create: number
  update: number
  delete: number
  assign: number
  total: number
}

// 获取权限操作日志列表
export function getOperationLogList(params: PermissionLogListParams) {
  return request.get<PermissionLogListResponse>('/operation-logs', { params })
}

// 获取权限日志统计
export function getPermissionLogStats() {
  return request.get<{ data: PermissionLogStats }>('/operation-logs/stats')
}

// 获取权限变更历史
export function getPermissionChangeHistory(permissionId: number) {
  return request.get(`/permissions/${permissionId}/history`)
}

// 获取角色权限分配记录
export function getRolePermissionHistory(roleId: number) {
  return request.get(`/roles/${roleId}/permission-history`)
}

// 导出权限日志
export function exportPermissionLogs(params: PermissionLogListParams) {
  return request.get('/operation-logs/export', { 
    params,
    responseType: 'blob'
  })
}

// 清理权限日志
export function cleanPermissionLogs(days: number) {
  return request.post('/operation-logs/clean', { days })
}

// 获取权限使用统计
export function getPermissionUsageStats() {
  return request.get('/permissions/usage-stats')
}

// 获取权限冲突检测
export function getPermissionConflicts() {
  return request.get('/permissions/conflicts')
}

// 获取权限依赖关系
export function getPermissionDependencies() {
  return request.get('/permissions/dependencies')
}
