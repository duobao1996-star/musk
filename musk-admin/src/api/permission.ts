import request from '@/utils/request'

// 权限接口
export interface Permission {
  id: number
  pid: string
  right_name: string
  description: string
  menu: number
  sort: number
  icon: string
  path: string
  method: string
  is_del: number
  children?: Permission[]
}

export interface PermissionListParams {
  page?: number
  limit?: number
}

export interface PermissionListResponse {
  data: Permission[]
  pagination: {
    total: number
    page: number
    limit: number
    pages: number
  }
}

export interface CreatePermissionData {
  right_name: string
  description: string
  pid?: string
  menu?: number
  sort?: number
  icon?: string
  path?: string
  method?: string
}

// 获取权限列表
export function getPermissionList(params: PermissionListParams) {
  return request.get<PermissionListResponse>('/permissions', { params })
}

// 获取权限详情
export function getPermissionDetail(id: number) {
  return request.get<{ data: Permission }>(`/permissions/${id}`)
}

// 创建权限
export function createPermission(data: CreatePermissionData) {
  return request.post('/permissions', data)
}

// 更新权限
export function updatePermission(id: number, data: Partial<CreatePermissionData>) {
  return request.put(`/permissions/${id}`, data)
}

// 删除权限
export function deletePermission(id: number) {
  return request.delete(`/permissions/${id}`)
}

// 获取权限树
export function getPermissionTree() {
  return request.get<{ data: Permission[] }>('/permissions/tree')
}

// 获取菜单权限
export function getMenuPermissions() {
  return request.get<{ data: Permission[] }>('/permissions/menu')
}
