import request from '@/utils/request'

// 性能监控接口
export interface PerformanceStats {
  total_requests: number
  avg_response_time: number
  slow_requests: number
  error_rate: number
  memory_usage: number
  peak_memory: number
}

export interface SlowQuery {
  id: number
  endpoint: string
  method: string
  response_time: number
  memory_usage: number
  status_code: number
  created_at: string
}

export interface SlowQueryParams {
  threshold?: number
  limit?: number
}

// 获取性能统计
export function getPerformanceStats() {
  return request.get<{ data: PerformanceStats }>('/performance/stats')
}

// 获取慢查询列表
export function getSlowQueries(params: SlowQueryParams) {
  return request.get<{ data: SlowQuery[] }>('/performance/slow-queries', { params })
}
