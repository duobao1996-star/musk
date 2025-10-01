import request from '@/utils/request'

// 角色接口
export interface Role {
  id: number
  name: string
  role_name: string
  order_no: number
  sort: number
  description: string
  status: number
  is_del: number
  created_at?: string
  updated_at?: string
}

export interface RoleListParams {
  page?: number
  limit?: number
  keyword?: string
  status?: number
}

export interface RoleListResponse {
  data: Role[]
  pagination: {
    total: number
    page: number
    limit: number
    pages: number
  }
}

export interface CreateRoleData {
  name: string
  role_name: string
  order_no?: number
  sort?: number
  description?: string
  status?: number
}

// 获取角色列表
export function getRoleList(params: RoleListParams) {
  return request.get<RoleListResponse>('/roles', { params })
}

// 获取角色详情
export function getRoleDetail(id: number) {
  return request.get<{ data: Role }>(`/roles/${id}`)
}

// 创建角色
export function createRole(data: CreateRoleData) {
  return request.post('/roles', data)
}

// 更新角色
export function updateRole(id: number, data: CreateRoleData) {
  return request.put(`/roles/${id}`, data)
}

// 删除角色
export function deleteRole(id: number) {
  return request.delete(`/roles/${id}`)
}

// 获取角色权限
export function getRoleRights(id: number) {
  return request.get(`/roles/${id}/rights`)
}

// 设置角色权限
export function setRoleRights(id: number, right_ids: number[]) {
  return request.post(`/roles/${id}/rights`, { right_ids })
}

// 获取所有权限树
export function getAllRightsTree() {
  return request.get('/roles/all-rights-tree')
}

// 获取角色选项（用于下拉选择）
export function getRoleOptions() {
  return request.get<{ data: Role[] }>('/roles/options')
}

// 批量创建角色
export function batchCreateRoles(data: CreateRoleData[]) {
  return request.post('/roles/batch', { roles: data })
}

// 批量更新角色
export function batchUpdateRoles(data: { id: number; data: CreateRoleData }[]) {
  return request.put('/roles/batch', { roles: data })
}

// 批量删除角色
export function batchDeleteRoles(ids: number[]) {
  return request.delete('/roles/batch', { data: { ids } })
}

// 复制角色
export function copyRole(id: number, newName: string) {
  return request.post(`/roles/${id}/copy`, { name: newName })
}

// 获取角色统计
export function getRoleStats() {
  return request.get<{ data: any }>('/roles/stats')
}
