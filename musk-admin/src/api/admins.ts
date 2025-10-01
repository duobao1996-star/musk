import request from '@/utils/request'

export interface AdminItem {
  id: number
  user_name: string
  email: string
  role_id: number
  role_name?: string
  status: number
  created_at?: string
  updated_at?: string
}

export interface AdminQuery {
  page?: number
  limit?: number
  keyword?: string
  status?: number
  role_id?: number
}

export function getAdminList(params: AdminQuery) {
  return request.get<{ data: AdminItem[]; pagination: { total: number; page: number; limit: number; pages: number } }>('/admins', { params })
}

export function createAdmin(data: { user_name: string; email: string; password: string; role_id?: number; status?: number }) {
  return request.post('/admins', data)
}

export function updateAdmin(id: number, data: Partial<{ user_name: string; email: string; role_id: number; status: number }>) {
  return request.put(`/admins/${id}`, data)
}

export function resetAdminPassword(id: number, password: string) {
  return request.post(`/admins/${id}/reset-password`, { password })
}

export function toggleAdminStatus(id: number) {
  return request.post(`/admins/${id}/toggle-status`)
}

export function deleteAdmin(id: number) {
  return request.delete(`/admins/${id}`)
}

// 获取管理员选项（用于下拉选择）
export function getAdminOptions() {
  return request.get<{ data: AdminItem[] }>('/admins/options')
}

// 批量创建管理员
export function batchCreateAdmins(data: { user_name: string; email: string; password: string; role_id?: number; status?: number }[]) {
  return request.post('/admins/batch', { admins: data })
}

// 批量更新管理员
export function batchUpdateAdmins(data: { id: number; data: Partial<{ user_name: string; email: string; role_id: number; status: number }> }[]) {
  return request.put('/admins/batch', { admins: data })
}

// 批量删除管理员
export function batchDeleteAdmins(ids: number[]) {
  return request.delete('/admins/batch', { data: { ids } })
}

// 获取管理员统计
export function getAdminStats() {
  return request.get<{ data: any }>('/admins/stats')
}

// 获取管理员详情
export function getAdminDetail(id: number) {
  return request.get<{ data: AdminItem }>(`/admins/${id}`)
}

// 更新管理员头像
export function updateAdminAvatar(id: number, avatar: string) {
  return request.put(`/admins/${id}/avatar`, { avatar })
}

// 获取管理员选项列表
export function getAdminOptions(params?: { keyword?: string; role_id?: number; status?: number }) {
  return request.get<{ data: Array<{ value: number; label: string; email: string; role_name: string }> }>('/admins/options', { params })
}

// 导出管理员列表
export function exportAdmins(params?: AdminQuery) {
  return request.get('/admins/export', { 
    params, 
    responseType: 'blob' 
  })
}

// 导入管理员
export function importAdmins(file: File) {
  const formData = new FormData()
  formData.append('file', file)
  return request.post<{ data: { imported_count: number; errors: string[] } }>('/admins/import', formData, {
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  })
}
