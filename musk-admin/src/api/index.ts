// 统一导出所有API
export * from './auth'
export * from './role'
export * from './permission'
export * from './operationLog'
export * from './performance'

// 管理员相关API
import request from '@/utils/request'

export interface Admin {
  id: number
  username: string
  email: string
  role_id: number
  role_name: string
  status: number
  created_at: string
  updated_at: string
}

export interface AdminListParams {
  page?: number
  limit?: number
  username?: string
  email?: string
  role_id?: number
  status?: number
}

export interface AdminListResponse {
  data: Admin[]
  pagination: {
    total: number
    page: number
    limit: number
    pages: number
  }
}

// 获取管理员列表
export function getAdminList(params: AdminListParams) {
  return request.get<AdminListResponse>('/admins', { params })
}

// 获取管理员详情
export function getAdminDetail(id: number) {
  return request.get<{ data: Admin }>(`/admins/${id}`)
}

// 创建管理员
export function createAdmin(data: Partial<Admin>) {
  return request.post<{ data: Admin }>('/admins', data)
}

// 更新管理员
export function updateAdmin(id: number, data: Partial<Admin>) {
  return request.put<{ data: Admin }>(`/admins/${id}`, data)
}

// 删除管理员
export function deleteAdmin(id: number) {
  return request.delete(`/admins/${id}`)
}
