import request from '@/utils/request'

// 角色接口
export interface Role {
  id: number
  role_name: string
  order_no: number
  description: string
  is_del: number
}

export interface RoleListParams {
  page?: number
  limit?: number
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
  role_name: string
  order_no?: number
  description?: string
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
