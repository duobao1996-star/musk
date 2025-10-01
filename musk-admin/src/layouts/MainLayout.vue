<template>
  <div class="main-layout">
    <!-- 侧边栏 -->
    <el-aside :width="isCollapse ? '64px' : '260px'" class="sidebar">
      <div class="logo">
        <div class="logo-icon-wrapper">
          <img src="/favicon.svg" alt="Musk" class="logo-image" />
        </div>
        <div v-if="!isCollapse" class="logo-text">
          <h1 class="brand-name">Musk</h1>
          <p class="brand-subtitle">管理系统</p>
        </div>
      </div>
      
        <el-menu
          :default-active="activeMenu"
          :collapse="false"
          :unique-opened="true"
          class="sidebar-menu"
          router
          :collapse-transition="false"
        >
          <!-- 动态渲染菜单 -->
          <template v-for="menu in menuList" :key="menu.id">
            <!-- 有子菜单的情况 -->
            <el-sub-menu 
              v-if="menu.children && menu.children.length > 0" 
              :index="`group-${menu.id}`" 
              popper-class="dark-submenu"
            >
              <template #title>
                <el-icon v-if="menu.icon">
                  <component :is="getMenuIcon(menu.icon)" />
                </el-icon>
                <span>{{ menu.title }}</span>
              </template>
              <el-menu-item 
                v-for="child in menu.children" 
                :key="child.id" 
                :index="child.path"
              >
                <el-icon v-if="child.icon">
                  <component :is="getMenuIcon(child.icon)" />
                </el-icon>
                <template #title>{{ child.title }}</template>
              </el-menu-item>
            </el-sub-menu>
            
            <!-- 没有子菜单的情况 -->
            <el-menu-item v-else :index="menu.path">
              <el-icon v-if="menu.icon">
                <component :is="getMenuIcon(menu.icon)" />
              </el-icon>
              <template #title>{{ menu.title }}</template>
            </el-menu-item>
          </template>
        </el-menu>
    </el-aside>

    <!-- 主内容区 -->
    <el-container class="main-container">
      <!-- 顶部导航 -->
      <el-header class="header">
        <div class="header-content">
          <div class="header-left">
            <el-button 
              type="text" 
              @click="toggleCollapse"
              class="collapse-btn"
            >
              <el-icon><Fold v-if="!isCollapse" /><Expand v-else /></el-icon>
            </el-button>
            
            <div class="breadcrumb-container">
              <el-breadcrumb separator="/" class="breadcrumb">
                <el-breadcrumb-item 
                  v-for="item in breadcrumbList" 
                  :key="item.path"
                  :to="item.path"
                >
                  {{ item.title }}
                </el-breadcrumb-item>
              </el-breadcrumb>
            </div>
          </div>

          <div class="header-right">
            <!-- 通知按钮 -->
            <el-button type="text" class="header-btn">
              <el-icon><Bell /></el-icon>
            </el-button>
            
            <!-- 全屏按钮 -->
            <el-button type="text" class="header-btn" @click="toggleFullscreen">
              <el-icon><FullScreen /></el-icon>
            </el-button>
            
            <!-- 用户信息 -->
            <el-dropdown @command="handleCommand" class="user-dropdown">
              <div class="user-info">
                <el-avatar :size="36" class="user-avatar">
                  {{ userInfo?.username?.charAt(0).toUpperCase() }}
                </el-avatar>
                <div v-if="!isCollapse" class="user-details">
                  <span class="username">{{ userInfo?.username }}</span>
                  <span class="user-role">管理员</span>
                </div>
                <el-icon class="dropdown-icon"><ArrowDown /></el-icon>
              </div>
              <template #dropdown>
                <el-dropdown-menu>
                  <el-dropdown-item command="profile">
                    <el-icon><User /></el-icon>
                    个人中心
                  </el-dropdown-item>
                  <el-dropdown-item command="settings">
                    <el-icon><Setting /></el-icon>
                    系统设置
                  </el-dropdown-item>
                  <el-dropdown-item command="logout" divided>
                    <el-icon><SwitchButton /></el-icon>
                    退出登录
                  </el-dropdown-item>
                </el-dropdown-menu>
              </template>
            </el-dropdown>
          </div>
        </div>
      </el-header>

      <!-- 标签页导航 -->
      <div class="tabs-container" @click="hideContextMenu">
        <el-tabs
          v-model="activeTab"
          type="card"
          closable
          @tab-remove="removeTab"
          @tab-click="handleTabClick"
          class="page-tabs"
        >
          <el-tab-pane
            v-for="tab in tabList"
            :key="tab.path"
            :name="tab.path"
          >
            <template #label>
              <span class="tab-label" @contextmenu.prevent.stop="openContextMenu($event, tab)">
                <el-icon class="tab-icon"><component :is="getMenuIcon(tab.icon)" /></el-icon>
                <span class="tab-text">{{ tab.title }}</span>
              </span>
            </template>
          </el-tab-pane>
        </el-tabs>
        <ul
          v-if="contextMenu.visible"
          class="tab-context-menu"
          :style="{ top: contextMenu.top + 'px', left: contextMenu.left + 'px' }"
        >
          <li class="menu-item" @click="closeOthers(contextMenu.tabPath)">关闭其他</li>
          <li class="menu-item" @click="closeRight(contextMenu.tabPath)">关闭右侧</li>
          <li class="menu-item" @click="closeAll()">关闭全部</li>
        </ul>
      </div>

      <!-- 主内容 -->
      <el-main class="main-content">
        <router-view v-slot="{ Component }">
          <keep-alive :include="keepAliveList">
            <component :is="Component" />
          </keep-alive>
        </router-view>
      </el-main>
    </el-container>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { 
  House, 
  Fold, 
  Expand, 
  ArrowDown,
  Setting,
  User,
  Monitor,
  Key,
  UserFilled,
  Document,
  Operation,
  Bell,
  FullScreen,
  SwitchButton
} from '@element-plus/icons-vue'
import { useAuthStore } from '@/stores/auth'
import { useMenuStore } from '@/stores/menu'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const menuStore = useMenuStore()

const isCollapse = ref(false)
const activeTab = ref('/')
const tabList = ref([
  { title: '仪表盘', path: '/', icon: 'House' }
])
const keepAliveList = ref<string[]>([])
const contextMenu = ref({ visible: false, top: 0, left: 0, tabPath: '/' })

const userInfo = computed(() => authStore.userInfo)
const menuList = computed(() => {
  console.log('布局组件菜单数据:', menuStore.menuList)
  return menuStore.menuList
})

// 图标映射函数
const getMenuIcon = (iconName: string) => {
  const iconMap: Record<string, any> = {
    'ri:settings-line': Setting,
    'ri:shield-check-line': Monitor,
    'ri:user-settings-line': UserFilled,
    'ri:file-list-line': Document,
    'ri:dashboard-line': Operation,
    'ri:user-line': User,
    'ri:key-line': Key,
    'ri:monitor-line': Monitor
  }
  return iconMap[iconName] || Document
}

// 当前激活的菜单
const activeMenu = computed(() => route.path)

// 面包屑导航
const breadcrumbList = computed(() => {
  const matched = route.matched.filter(item => item.meta && item.meta.title)
  return matched.map(item => ({
    title: item.meta?.title as string,
    path: item.path
  }))
})

// 切换侧边栏
const toggleCollapse = () => {
  isCollapse.value = !isCollapse.value
}

// 切换全屏
const toggleFullscreen = () => {
  if (!document.fullscreenElement) {
    document.documentElement.requestFullscreen()
  } else {
    if (document.exitFullscreen) {
      document.exitFullscreen()
    }
  }
}

// 处理用户下拉菜单
const handleCommand = async (command: string) => {
  switch (command) {
    case 'profile':
      ElMessage.info('个人中心功能开发中...')
      break
    case 'logout':
      try {
        await ElMessageBox.confirm('确定要退出登录吗？', '提示', {
          confirmButtonText: '确定',
          cancelButtonText: '取消',
          type: 'warning'
        })
        await authStore.logoutAction()
        router.push('/login')
        ElMessage.success('退出成功')
      } catch (error) {
        // 用户取消
      }
      break
  }
}

// 移除标签页
const removeTab = (targetName: string) => {
  if (targetName === '/') return // 不能关闭首页
  
  const tabs = tabList.value
  let activeName = activeTab.value
  
  if (activeName === targetName) {
    tabs.forEach((tab, index) => {
      if (tab.path === targetName) {
        const nextTab = tabs[index + 1] || tabs[index - 1]
        if (nextTab) {
          activeName = nextTab.path
        }
      }
    })
  }
  
  activeTab.value = activeName
  tabList.value = tabs.filter(tab => tab.path !== targetName)
  
  if (activeName !== route.path) {
    router.push(activeName)
  }
}

// 点击标签页
const handleTabClick = (tab: any) => {
  router.push(tab.paneName)
}

// 右键菜单
const openContextMenu = (e: MouseEvent, tab: any) => {
  contextMenu.value = {
    visible: true,
    top: e.clientY,
    left: e.clientX,
    tabPath: tab.path
  }
}

const hideContextMenu = () => {
  if (contextMenu.value.visible) contextMenu.value.visible = false
}

const closeOthers = (path: string) => {
  tabList.value = tabList.value.filter(t => t.path === '/' || t.path === path)
  activeTab.value = path
  hideContextMenu()
}

const closeRight = (path: string) => {
  const index = tabList.value.findIndex(t => t.path === path)
  if (index !== -1) {
    tabList.value = tabList.value.filter((_, i) => i <= index || tabList.value[i].path === '/')
    activeTab.value = path
  }
  hideContextMenu()
}

const closeAll = () => {
  tabList.value = tabList.value.filter(t => t.path === '/')
  activeTab.value = '/'
  if (route.path !== '/') router.push('/')
  hideContextMenu()
}

// 添加标签页
const addTab = (route: any) => {
  const title = route.meta?.title || route.name || '未命名页面'
  const path = route.path
  
  // 检查是否已存在
  const existingTab = tabList.value.find(tab => tab.path === path)
  if (!existingTab && path !== '/login') {
    tabList.value.push({
      title,
      path,
      icon: route.meta?.icon || 'FileList'
    })
  }
  
  activeTab.value = path
}

// 监听路由变化
watch(route, (newRoute) => {
  addTab(newRoute)
}, { immediate: true })

// 初始化
onMounted(async () => {
  // 初始化用户信息
  authStore.initUserInfo()
  
  // 获取菜单数据
  await menuStore.getMenuList()
})
</script>

<style scoped>
.main-layout {
  height: 100vh;
  display: flex;
}

.sidebar {
  background: #0b0f19;
  transition: width 0.3s;
}

.logo {
  height: 80px;
  display: flex;
  align-items: center;
  padding: 0 20px;
  color: #fff;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  transition: all 0.3s ease;
}

.logo-icon-wrapper {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 12px;
}

.logo-image {
  width: 24px;
  height: 24px;
  filter: brightness(0) invert(1);
}

.logo-text {
  flex: 1;
}

.brand-name {
  font-size: 18px;
  font-weight: 700;
  color: #fff;
  margin: 0;
  line-height: 1.2;
}

.brand-subtitle {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.7);
  margin: 0;
  line-height: 1;
}

.sidebar-menu {
  border: none;
  height: calc(100vh - 64px);
  overflow-y: auto;
  background: transparent;
  /* Element Plus 菜单暗色主题变量 */
  --el-menu-bg-color: #0b0f19;
  --el-menu-hover-bg-color: rgba(24, 144, 255, 0.22);
  --el-menu-text-color: #8ac7ff;
  --el-menu-active-color: #ffffff;
  --el-menu-border-color: rgba(0, 212, 255, 0.18);
}

.sidebar-menu :deep(.el-menu-item),
.sidebar-menu :deep(.el-sub-menu__title) {
  color: #ffffff !important;
  font-size: 14px;
  font-weight: 500;
}

.sidebar-menu :deep(.el-menu-item:hover),
.sidebar-menu :deep(.el-sub-menu__title:hover) {
  background: linear-gradient(90deg, rgba(24,144,255,0.22), rgba(0,212,255,0.00)) !important;
  color: #ffffff !important;
  text-shadow: 0 0 6px rgba(24,144,255,0.8);
  border-left: 2px solid #00d4ff;
}

.sidebar-menu :deep(.el-menu-item.is-active) {
  background: linear-gradient(90deg, #1890ff, #00d4ff) !important;
  color: #ffffff !important;
  border-left: 3px solid #00e0ff;
  box-shadow: inset 0 0 0 1px rgba(255,255,255,0.05), 0 8px 24px rgba(0,212,255,0.18);
}

.sidebar-menu :deep(.el-menu-item span),
.sidebar-menu :deep(.el-sub-menu__title span) {
  color: #ffffff !important;
}

.sidebar-menu :deep(.el-menu-item .el-icon),
.sidebar-menu :deep(.el-sub-menu__title .el-icon) {
  color: #ffffff !important;
  transition: color .2s ease, filter .2s ease;
}

.sidebar-menu :deep(.el-menu-item:hover .el-icon),
.sidebar-menu :deep(.el-sub-menu__title:hover .el-icon) {
  color: #ffffff !important;
  filter: drop-shadow(0 0 6px rgba(0,212,255,0.6));
}

/* Element Plus 菜单样式 - 确保菜单项始终可见 */
.sidebar-menu :deep(.el-menu-item) {
  color: #ffffff !important;
  font-size: 14px !important;
  font-weight: 500 !important;
  transition: background .25s ease, color .2s ease, border-left .2s ease;
}

.sidebar-menu :deep(.el-menu-item span) {
  color: #ffffff !important;
  display: inline !important;
  opacity: 1 !important;
  visibility: visible !important;
}

.sidebar-menu :deep(.el-menu-item .el-icon) {
  color: #ffffff !important;
  margin-right: 8px !important;
}

.main-container {
  flex: 1;
  display: flex;
  flex-direction: column;
}

/* 子菜单展开态标题微光与轮廓 */
.sidebar-menu :deep(.el-sub-menu.is-opened > .el-sub-menu__title) {
  background: linear-gradient(90deg, rgba(0,212,255,0.18), rgba(0,212,255,0.00));
  color: #ffffff !important;
  border-left: 2px solid #00d4ff;
}

/* 自定义滚动条，科技蓝 */
.sidebar-menu::-webkit-scrollbar {
  width: 8px;
}
.sidebar-menu::-webkit-scrollbar-thumb {
  background: linear-gradient(180deg, #1890ff, #00d4ff);
  border-radius: 8px;
}
.sidebar-menu::-webkit-scrollbar-track {
  background: #0b0f19;
}

/* 子菜单（弹出/下拉）深色主题 */
.sidebar-menu :deep(.el-menu--popup),
.sidebar-menu :deep(.el-menu--popup-container) {
  background: #0b0f19 !important;
  border: 1px solid rgba(0, 212, 255, 0.18) !important;
  box-shadow: 0 10px 30px rgba(0, 212, 255, 0.12), inset 0 0 0 1px rgba(255,255,255,0.03) !important;
}
.sidebar-menu :deep(.el-menu--popup .el-menu-item) {
  background: transparent !important;
  color: #8ac7ff !important;
}
.sidebar-menu :deep(.el-menu--popup .el-menu-item:hover) {
  background: linear-gradient(90deg, rgba(24,144,255,0.22), rgba(0,212,255,0.00)) !important;
  color: #ffffff !important;
}
.sidebar-menu :deep(.el-menu--popup .el-menu-item.is-active) {
  background: linear-gradient(90deg, #1890ff, #00d4ff) !important;
  color: #ffffff !important;
}

/* 内联嵌套子菜单背景（非弹出场景） */
.sidebar-menu :deep(.el-menu .el-menu) {
  background: #0b0f19 !important;
}
/* 强制所有菜单容器背景为暗色，覆盖默认白底 */
.sidebar-menu :deep(.el-menu) {
  background: #0b0f19 !important;
}

.header {
  background: #fff;
  border-bottom: 1px solid #e2e8f0;
  height: 70px;
  padding: 0;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
}

.header-content {
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 24px;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 16px;
}

.breadcrumb-container {
  display: flex;
  align-items: center;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 8px;
}

.header-btn {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  color: #64748b;
  transition: all 0.2s ease;
}

.header-btn:hover {
  background: #f1f5f9;
  color: #1e40af;
}

.user-dropdown {
  margin-left: 8px;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 12px;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.user-info:hover {
  background: #f8fafc;
}

.user-avatar {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  font-weight: 600;
}

.user-details {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 2px;
}

.username {
  font-size: 14px;
  font-weight: 600;
  color: #1a202c;
  line-height: 1;
}

.user-role {
  font-size: 12px;
  color: #64748b;
  line-height: 1;
}

.dropdown-icon {
  color: #94a3b8;
  font-size: 14px;
  transition: transform 0.2s ease;
}

.user-dropdown:hover .dropdown-icon {
  transform: rotate(180deg);
}

.collapse-btn {
  margin-right: 20px;
  font-size: 18px;
}

.breadcrumb {
  font-size: 14px;
}

.header-right {
  display: flex;
  align-items: center;
}

.user-info {
  display: flex;
  align-items: center;
  cursor: pointer;
  padding: 8px 12px;
  border-radius: 6px;
  transition: background-color 0.3s;
}

.user-info:hover {
  background-color: #f5f5f5;
}

.user-avatar {
  margin-right: 8px;
  background: #1890ff;
}

.username {
  margin-right: 8px;
  font-size: 14px;
}

.tabs-container {
  background: #fff;
  border-bottom: 1px solid #e8e8e8;
  padding: 0 20px;
}

.page-tabs {
  margin: 0;
}

.page-tabs :deep(.el-tabs__header) {
  margin: 0;
}

/* 标签页样式 */
.tab-label {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.tab-icon {
  display: inline-flex;
  align-items: center;
}

.tab-text {
  font-size: 13px;
}

.tab-context-menu {
  position: fixed;
  z-index: 3000;
  min-width: 140px;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.12);
  padding: 6px 0;
}

.tab-context-menu .menu-item {
  padding: 8px 12px;
  font-size: 13px;
  color: #333;
  cursor: pointer;
}

.tab-context-menu .menu-item:hover {
  background: #f5f7fa;
}

.page-tabs :deep(.el-tabs__nav-wrap) {
  padding: 8px 0;
}

.main-content {
  background: #f5f5f5;
  padding: 20px;
  overflow-y: auto;
}
</style>

<style>
.dark-submenu {
  background: #0b0f19 !important;
  border: 1px solid rgba(0, 212, 255, 0.18) !important;
  box-shadow: 0 10px 30px rgba(0, 212, 255, 0.12), inset 0 0 0 1px rgba(255,255,255,0.03) !important;
}
.dark-submenu .el-menu-item {
  background: transparent !important;
  color: #8ac7ff !important;
}
.dark-submenu .el-menu-item:hover {
  background: linear-gradient(90deg, rgba(24,144,255,0.22), rgba(0,212,255,0.00)) !important;
  color: #ffffff !important;
}
.dark-submenu .el-menu-item.is-active {
  background: linear-gradient(90deg, #1890ff, #00d4ff) !important;
  color: #ffffff !important;
}
</style>
