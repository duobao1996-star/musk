import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useMenuStore } from '@/stores/menu'
import { ElMessage } from 'element-plus'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/login',
      name: 'Login',
      component: () => import('@/views/LoginView.vue'),
      meta: {
        title: '登录',
        requiresAuth: false
      }
    },
    {
      path: '/',
      component: () => import('@/layouts/MainLayout.vue'),
      meta: {
        title: '首页',
        requiresAuth: true
      },
      children: [
        {
          path: '',
          name: 'Dashboard',
          component: () => import('@/views/DashboardView.vue'),
          meta: {
            title: '仪表盘',
            requiresAuth: true
          }
        },
        {
          path: '/system',
          name: 'SystemManagement',
          component: () => import('@/views/system/SystemView.vue'),
          meta: {
            title: '系统管理',
            requiresAuth: true
          }
        },
        {
          path: '/system/roles',
          name: 'RoleManagement',
          component: () => import('@/views/system/RoleView.vue'),
          meta: {
            title: '角色管理',
            requiresAuth: true
          }
        },
        {
          path: '/system/permissions',
          name: 'PermissionManagement',
          component: () => import('@/views/system/PermissionView.vue'),
          meta: {
            title: '权限管理',
            requiresAuth: true
          }
        },
        {
          path: '/system/logs',
          name: 'LogManagement',
          component: () => import('@/views/system/LogView.vue'),
          meta: {
            title: '操作日志',
            requiresAuth: true
          }
        },
        {
          path: '/system/performance',
          name: 'PerformanceManagement',
          component: () => import('@/views/system/PerformanceView.vue'),
          meta: {
            title: '性能监控',
            requiresAuth: true
          }
        },
        {
          path: '/system/admins',
          name: 'AdminAccounts',
          component: () => import('@/views/system/AdminView.vue'),
          meta: {
            title: '管理员账号',
            requiresAuth: true
          }
        },
        {
          path: '/system/info',
          name: 'SystemInfo',
          component: () => import('@/views/system/SystemInfoView.vue'),
          meta: {
            title: '系统信息',
            requiresAuth: true
          }
        },
        {
          path: '/system/config',
          name: 'SystemConfig',
          component: () => import('@/views/system/SystemConfigView.vue'),
          meta: {
            title: '系统配置',
            requiresAuth: true
          }
        },
        {
          path: '/system/performance/trends',
          name: 'PerformanceTrends',
          component: () => import('@/views/system/PerformanceTrendsView.vue'),
          meta: {
            title: '性能趋势',
            requiresAuth: true
          }
        },
        {
          path: '/system/performance/slow',
          name: 'PerformanceSlowQuery',
          component: () => import('@/views/system/PerformanceSlowQueryView.vue'),
          meta: {
            title: '慢查询',
            requiresAuth: true
          }
        },
        {
          path: '/system/logs/stats',
          name: 'LogStats',
          component: () => import('@/views/system/LogStatsView.vue'),
          meta: {
            title: '日志统计',
            requiresAuth: true
          }
        }
      ]
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'NotFound',
      redirect: '/'
    }
  ]
})

// 路由守卫
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()
  const menuStore = useMenuStore()
  
  // 设置页面标题
  document.title = to.meta.title ? `${to.meta.title} - Musk管理系统` : 'Musk管理系统'
  
  // 检查是否需要认证
  if (to.meta.requiresAuth) {
    if (!authStore.isLoggedIn) {
      ElMessage.warning('请先登录')
      next('/login')
      return
    }
    
    // 如果有token但没有用户信息，尝试获取用户信息
    if (authStore.token && !authStore.userInfo) {
      try {
        await authStore.getUserInfoAction()
      } catch (error) {
        ElMessage.error('获取用户信息失败，请重新登录')
        next('/login')
        return
      }
    }
    
    // 权限验证：检查用户是否有访问当前页面的权限
    if (to.path !== '' && to.path !== '/') {
      try {
        // 获取用户菜单权限
        let menuList = menuStore.menuList
        if (menuList.length === 0) {
          // 如果菜单为空，尝试获取菜单
          await menuStore.getMenuList()
          menuList = menuStore.menuList
        }
        
        // 如果菜单仍然为空，说明用户没有权限，强制登出
        if (menuList.length === 0) {
          ElMessage.error('您没有权限访问系统')
          authStore.logout()
          next('/login')
          return
        }
        
        // 检查当前路径是否在用户权限范围内
        const hasPermission = checkRoutePermission(to.path, menuList)
        if (!hasPermission) {
          ElMessage.error('您没有权限访问此页面')
          next('/')
          return
        }
      } catch (error) {
        console.error('权限检查失败:', error)
        // 如果权限检查失败，为了安全起见，清除登录状态并重定向到登录页
        ElMessage.error('权限验证失败，请重新登录')
        authStore.logout()
        next('/login')
        return
      }
    }
  }
  
  // 如果已登录用户访问登录页，重定向到首页
  if (to.path === '/login' && authStore.isLoggedIn) {
    next('/')
    return
  }
  
  next()
})

// 检查路由权限
function checkRoutePermission(path: string, menuList: any[]): boolean {
  // 首页总是可以访问
  if (path === '/' || path === '') return true
  
  // 输入验证
  if (!path || !Array.isArray(menuList)) {
    return false
  }
  
  // 递归检查菜单权限
  function checkMenu(menus: any[]): boolean {
    if (!Array.isArray(menus)) return false
    
    for (const menu of menus) {
      if (!menu || typeof menu !== 'object') continue
      
      // 检查当前菜单路径
      if (menu.path === path) {
        return true
      }
      
      // 检查子菜单
      if (menu.children && Array.isArray(menu.children) && menu.children.length > 0) {
        if (checkMenu(menu.children)) {
          return true
        }
      }
    }
    return false
  }
  
  return checkMenu(menuList)
}

export default router