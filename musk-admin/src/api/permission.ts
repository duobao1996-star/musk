import request from '@/utils/request'

// 权限接口
export interface Permission {
  id: number
  pid: number
  right_name: string
  description: string
  menu: number
  is_menu: number
  sort: number
  icon: string
  path: string
  method: string
  is_del: number
  component?: string
  redirect?: string
  hidden?: boolean
  always_show?: boolean
  no_cache?: boolean
  affix?: boolean
  breadcrumb?: boolean
  active_menu?: string
  children?: Permission[]
}

export interface PermissionListParams {
  page?: number
  limit?: number
  module?: string
  method?: string
  keyword?: string
  is_menu?: number
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
  pid?: number
  menu?: number
  is_menu?: number
  sort?: number
  icon?: string
  path?: string
  method?: string
  component?: string
  redirect?: string
  hidden?: boolean
  always_show?: boolean
  no_cache?: boolean
  affix?: boolean
  breadcrumb?: boolean
  active_menu?: string
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

// 获取权限选项（用于树形选择器）
export function getPermissionOptions() {
  return request.get<{ data: Permission[] }>('/permissions/options')
}

// 批量创建权限
export function batchCreatePermissions(data: CreatePermissionData[]) {
  return request.post('/permissions/batch', { permissions: data })
}

// 批量更新权限
export function batchUpdatePermissions(data: { id: number; data: Partial<CreatePermissionData> }[]) {
  return request.put('/permissions/batch', { permissions: data })
}

// 批量删除权限
export function batchDeletePermissions(ids: number[]) {
  return request.delete('/permissions/batch', { data: { ids } })
}

// 获取权限统计
export function getPermissionStats() {
  return request.get<{ data: any }>('/permissions/stats')
}
