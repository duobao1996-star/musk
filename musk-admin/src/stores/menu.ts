import { defineStore } from 'pinia'
import { ref } from 'vue'
import { getMenuPermissions, type Permission } from '@/api/permission'

export interface MenuItem {
  id: number
  title: string
  path: string
  icon: string
  component?: string
  redirect?: string
  hidden?: boolean
  alwaysShow?: boolean
  noCache?: boolean
  affix?: boolean
  breadcrumb?: boolean
  activeMenu?: string
  children?: MenuItem[]
}

export const useMenuStore = defineStore('menu', () => {
  const menuList = ref<MenuItem[]>([])
  const loading = ref(false)

  // 获取菜单数据
  const getMenuList = async () => {
    try {
      loading.value = true
      const response = await getMenuPermissions()
      
      // 验证响应数据
      if (!response?.data) {
        throw new Error('菜单API响应数据无效')
      }
      
      // 后端已经返回了完整的菜单树结构，直接使用
      if (response.data.data && Array.isArray(response.data.data)) {
        menuList.value = validateMenuData(response.data.data)
        return menuList.value
      }
      
      // 兼容旧格式：如果是平铺的权限列表，需要构建树结构
      const permissions = response.data.data || response.data || []
      if (Array.isArray(permissions)) {
        const menuPermissions = permissions.filter((item: Permission) => 
          item && typeof item === 'object' && (item.is_menu === 1 || item.menu === 1)
        )
        menuList.value = buildMenuTree(menuPermissions)
        return menuList.value
      }
      
      console.warn('菜单数据格式不正确:', response.data)
      menuList.value = []
      return []
    } catch (error) {
      console.error('获取菜单失败:', error)
      menuList.value = []
      return []
    } finally {
      loading.value = false
    }
  }

  // 验证菜单数据
  const validateMenuData = (data: any[]): MenuItem[] => {
    if (!Array.isArray(data)) return []
    
    return data.filter(item => {
      return item && 
             typeof item === 'object' && 
             typeof item.id === 'number' && 
             typeof item.title === 'string' && 
             typeof item.path === 'string'
    }).map(item => ({
      id: item.id,
      title: item.title,
      path: item.path,
      icon: item.icon || 'ri:file-list-line',
      component: item.component,
      redirect: item.redirect,
      hidden: Boolean(item.hidden),
      alwaysShow: item.alwaysShow !== false,
      noCache: Boolean(item.noCache),
      affix: Boolean(item.affix),
      breadcrumb: item.breadcrumb !== false,
      activeMenu: item.activeMenu,
      children: item.children ? validateMenuData(item.children) : []
    }))
  }

  // 构建菜单树（兼容旧格式）
  const buildMenuTree = (permissions: Permission[], pid = 0): MenuItem[] => {
    if (!Array.isArray(permissions)) return []
    
    const tree: MenuItem[] = []
    
    permissions.forEach(permission => {
      if (permission && 
          typeof permission === 'object' && 
          typeof permission.pid === 'number' && 
          typeof permission.id === 'number' &&
          permission.pid === pid) {
        
        const children = buildMenuTree(permissions, permission.id)
        const menuItem: MenuItem = {
          id: permission.id,
          title: permission.description || '未命名菜单',
          path: permission.path || '#',
          icon: permission.icon || 'ri:file-list-line',
          component: permission.component,
          redirect: permission.redirect,
          hidden: Boolean(permission.hidden),
          alwaysShow: permission.always_show !== false,
          noCache: Boolean(permission.no_cache),
          affix: Boolean(permission.affix),
          breadcrumb: permission.breadcrumb !== false,
          activeMenu: permission.active_menu,
          children: children
        }
        tree.push(menuItem)
      }
    })
    
    return tree.sort((a, b) => (a.id || 0) - (b.id || 0))
  }

  // 清空菜单
  const clearMenuList = () => {
    menuList.value = []
  }

  return {
    menuList,
    loading,
    getMenuList,
    clearMenuList
  }
})
