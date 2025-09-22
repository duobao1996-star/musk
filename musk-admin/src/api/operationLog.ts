import request from '@/utils/request'

// 操作日志接口
export interface OperationLog {
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
  operation_time: string
  is_del: number
}

export interface OperationLogListParams {
  page?: number
  limit?: number
  admin_id?: number
  operation_type?: string
  operation_module?: string
  status?: number
  start_time?: string
  end_time?: string
}

export interface OperationLogListResponse {
  data: OperationLog[]
  pagination: {
    total: number
    page: number
    limit: number
    pages: number
  }
}

export interface OperationLogStats {
  total_operations: number
  today_operations: number
  login_count: number
  error_count: number
  module_stats: Array<{
    module: string
    count: number
  }>
}

// 获取操作日志列表
export function getOperationLogList(params: OperationLogListParams) {
  return request.get<OperationLogListResponse>('/operation-logs', { params })
}

// 获取操作日志统计
export function getOperationLogStats() {
  return request.get<{ data: OperationLogStats }>('/operation-logs/stats')
}

// 获取登录日志
export function getLoginLogs() {
  return request.get('/operation-logs/login')
}

// 清理旧日志
export function cleanOldLogs(days: number) {
  return request.post('/operation-logs/clean', { days })
}

// 获取已删除日志
export function getDeletedLogs(params: OperationLogListParams) {
  return request.get<OperationLogListResponse>('/soft-delete/logs', { params })
}

// 恢复日志
export function restoreLog(id: number) {
  return request.post('/soft-delete/logs/restore', { id })
}

// 彻底删除日志
export function forceDeleteLog(id: number) {
  return request.delete('/soft-delete/logs/force', { data: { id } })
}

// 清理回收站
export function cleanupDeletedLogs(days: number = 7) {
  return request.post('/soft-delete/cleanup', { days })
}
