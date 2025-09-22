import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
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
  }
  
  // 如果已登录用户访问登录页，重定向到首页
  if (to.path === '/login' && authStore.isLoggedIn) {
    next('/')
    return
  }
  
  next()
})

export default router