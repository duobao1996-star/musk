<template>
  <div class="main-layout">
    <!-- 侧边栏 -->
    <el-aside :width="isCollapse ? '64px' : '240px'" class="sidebar">
      <div class="logo">
        <img src="/favicon.svg" alt="Musk" v-if="!isCollapse" />
        <span v-if="!isCollapse" class="logo-text">Musk</span>
        <span v-else class="logo-icon">M</span>
      </div>
      
      <el-menu
        :default-active="activeMenu"
        :collapse="false"
        :unique-opened="true"
        class="sidebar-menu"
        router
        :collapse-transition="false"
      >
        <el-menu-item index="/">
          <el-icon><House /></el-icon>
          <template #title>仪表盘</template>
        </el-menu-item>
        
        <el-menu-item v-for="menu in menuList" :key="menu.id" :index="menu.path">
          <el-icon><component :is="getMenuIcon(menu.icon)" /></el-icon>
          <template #title>{{ menu.title }}</template>
        </el-menu-item>
      </el-menu>
    </el-aside>

    <!-- 主内容区 -->
    <el-container class="main-container">
      <!-- 顶部导航 -->
      <el-header class="header">
        <div class="header-left">
          <el-button 
            type="text" 
            @click="toggleCollapse"
            class="collapse-btn"
          >
            <el-icon><Fold v-if="!isCollapse" /><Expand v-else /></el-icon>
          </el-button>
          
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

        <div class="header-right">
          <el-dropdown @command="handleCommand">
            <span class="user-info">
              <el-avatar :size="32" class="user-avatar" :src="'/favicon.svg'">
                {{ userInfo?.username?.charAt(0).toUpperCase() }}
              </el-avatar>
              <span class="username">{{ userInfo?.username }}</span>
              <el-icon><ArrowDown /></el-icon>
            </span>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item command="profile">个人中心</el-dropdown-item>
                <el-dropdown-item command="logout" divided>退出登录</el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
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
  Operation
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
  background: #000000;
  transition: width 0.3s;
}

.logo {
  height: 64px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 20px;
  font-weight: bold;
  border-bottom: 1px solid #1f1f1f;
}

.logo img {
  height: 32px;
  margin-right: 8px;
}

.logo-text {
  color: #fff;
}

.logo-icon {
  font-size: 24px;
  color: #1890ff;
}

.sidebar-menu {
  border: none;
  height: calc(100vh - 64px);
  overflow-y: auto;
  background: #000000;
}

.sidebar-menu :deep(.el-menu-item),
.sidebar-menu :deep(.el-sub-menu__title) {
  color: #fff !important;
  font-size: 14px;
  font-weight: 500;
}

.sidebar-menu :deep(.el-menu-item:hover),
.sidebar-menu :deep(.el-sub-menu__title:hover) {
  background-color: #1890ff !important;
  color: #fff !important;
}

.sidebar-menu :deep(.el-menu-item.is-active) {
  background-color: #1890ff !important;
  color: #fff !important;
}

.sidebar-menu :deep(.el-menu-item span),
.sidebar-menu :deep(.el-sub-menu__title span) {
  color: #fff !important;
}

.sidebar-menu :deep(.el-menu-item .el-icon),
.sidebar-menu :deep(.el-sub-menu__title .el-icon) {
  color: #fff !important;
}

/* Element Plus 菜单样式 - 确保菜单项始终可见 */
.sidebar-menu :deep(.el-menu-item) {
  color: #fff !important;
  font-size: 14px !important;
  font-weight: 500 !important;
}

.sidebar-menu :deep(.el-menu-item span) {
  color: #fff !important;
  display: inline !important;
  opacity: 1 !important;
  visibility: visible !important;
}

.sidebar-menu :deep(.el-menu-item .el-icon) {
  color: #fff !important;
  margin-right: 8px !important;
}

.main-container {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.header {
  background: #fff;
  border-bottom: 1px solid #e8e8e8;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 20px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.header-left {
  display: flex;
  align-items: center;
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
