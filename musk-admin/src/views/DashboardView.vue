<template>
  <div class="dashboard">
    <!-- 页面标题 -->
    <div class="page-header">
      <h1 class="page-title">仪表盘</h1>
      <p class="page-subtitle">系统概览与快速操作</p>
    </div>

    <!-- 统计卡片 -->
    <div class="stats-grid">
      <div class="stat-card user-card">
        <div class="stat-content">
          <div class="stat-icon-wrapper">
            <div class="stat-icon user-icon">
              <el-icon><User /></el-icon>
            </div>
            <div class="stat-trend up">
              <el-icon><TrendCharts /></el-icon>
            </div>
          </div>
          <div class="stat-info">
            <div class="stat-number">{{ stats.totalUsers }}</div>
            <div class="stat-label">管理员数量</div>
            <div class="stat-change">+12% 本月</div>
          </div>
        </div>
      </div>
      
      <div class="stat-card role-card">
        <div class="stat-content">
          <div class="stat-icon-wrapper">
            <div class="stat-icon role-icon">
              <el-icon><UserFilled /></el-icon>
            </div>
            <div class="stat-trend stable">
              <el-icon><Minus /></el-icon>
            </div>
          </div>
          <div class="stat-info">
            <div class="stat-number">{{ stats.totalRoles }}</div>
            <div class="stat-label">角色数量</div>
            <div class="stat-change">稳定</div>
          </div>
        </div>
      </div>
      
      <div class="stat-card permission-card">
        <div class="stat-content">
          <div class="stat-icon-wrapper">
            <div class="stat-icon permission-icon">
              <el-icon><Key /></el-icon>
            </div>
            <div class="stat-trend up">
              <el-icon><TrendCharts /></el-icon>
            </div>
          </div>
          <div class="stat-info">
            <div class="stat-number">{{ stats.totalPermissions }}</div>
            <div class="stat-label">权限数量</div>
            <div class="stat-change">+5% 本月</div>
          </div>
        </div>
      </div>
      
      <div class="stat-card log-card">
        <div class="stat-content">
          <div class="stat-icon-wrapper">
            <div class="stat-icon log-icon">
              <el-icon><Document /></el-icon>
            </div>
            <div class="stat-trend up">
              <el-icon><TrendCharts /></el-icon>
            </div>
          </div>
          <div class="stat-info">
            <div class="stat-number">{{ stats.totalLogs }}</div>
            <div class="stat-label">操作日志</div>
            <div class="stat-change">+8% 今日</div>
          </div>
        </div>
      </div>
    </div>

    <!-- 内容区域 -->
    <div class="content-grid">
      <!-- 系统信息卡片 -->
      <div class="content-card">
        <div class="card-header">
          <div class="header-content">
            <h3 class="card-title">
              <el-icon><Setting /></el-icon>
              系统信息
            </h3>
            <el-tag type="success" size="small">运行正常</el-tag>
          </div>
        </div>
        <div class="system-info">
          <div class="info-item">
            <div class="info-icon">
              <el-icon><Monitor /></el-icon>
            </div>
            <div class="info-content">
              <span class="label">系统名称</span>
              <span class="value">Musk管理系统</span>
            </div>
          </div>
          <div class="info-item">
            <div class="info-icon">
              <el-icon><User /></el-icon>
            </div>
            <div class="info-content">
              <span class="label">当前用户</span>
              <span class="value">{{ userInfo?.username }}</span>
            </div>
          </div>
          <div class="info-item">
            <div class="info-icon">
              <el-icon><UserFilled /></el-icon>
            </div>
            <div class="info-content">
              <span class="label">用户角色</span>
              <span class="value role-tag">{{ getRoleName(userInfo?.role_id) }}</span>
            </div>
          </div>
          <div class="info-item">
            <div class="info-icon">
              <el-icon><Clock /></el-icon>
            </div>
            <div class="info-content">
              <span class="label">登录时间</span>
              <span class="value">{{ formatTime(new Date()) }}</span>
            </div>
          </div>
        </div>
      </div>
      
      <!-- 快速操作卡片 -->
      <div class="content-card">
        <div class="card-header">
          <div class="header-content">
            <h3 class="card-title">
              <el-icon><Operation /></el-icon>
              快速操作
            </h3>
            <el-button type="text" size="small">查看全部</el-button>
          </div>
        </div>
        <div class="quick-actions">
          <div class="action-item" @click="goToPage('/system/roles')">
            <div class="action-icon role-action">
              <el-icon><UserFilled /></el-icon>
            </div>
            <div class="action-content">
              <span class="action-title">角色管理</span>
              <span class="action-desc">管理系统角色</span>
            </div>
            <el-icon class="action-arrow"><ArrowRight /></el-icon>
          </div>
          
          <div class="action-item" @click="goToPage('/system/permissions')">
            <div class="action-icon permission-action">
              <el-icon><Key /></el-icon>
            </div>
            <div class="action-content">
              <span class="action-title">权限管理</span>
              <span class="action-desc">配置系统权限</span>
            </div>
            <el-icon class="action-arrow"><ArrowRight /></el-icon>
          </div>
          
          <div class="action-item" @click="goToPage('/system/logs')">
            <div class="action-icon log-action">
              <el-icon><Document /></el-icon>
            </div>
            <div class="action-content">
              <span class="action-title">操作日志</span>
              <span class="action-desc">查看系统日志</span>
            </div>
            <el-icon class="action-arrow"><ArrowRight /></el-icon>
          </div>
          
          <div class="action-item" @click="goToPage('/system/performance')">
            <div class="action-icon performance-action">
              <el-icon><Monitor /></el-icon>
            </div>
            <div class="action-content">
              <span class="action-title">性能监控</span>
              <span class="action-desc">系统性能分析</span>
            </div>
            <el-icon class="action-arrow"><ArrowRight /></el-icon>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { 
  User, 
  UserFilled, 
  Key, 
  Document, 
  Monitor,
  Setting,
  Operation,
  TrendCharts,
  Minus,
  Clock,
  ArrowRight
} from '@element-plus/icons-vue'
import { useAuthStore } from '@/stores/auth'
import { getRoleList } from '@/api/role'
import { getPermissionList } from '@/api/permission'
import { getOperationLogList } from '@/api/operationLog'
import { getAdminList } from '@/api/admins'

const router = useRouter()
const authStore = useAuthStore()

const userInfo = computed(() => authStore.userInfo)
const stats = ref({
  totalUsers: 0,
  totalRoles: 0,
  totalPermissions: 0,
  totalLogs: 0
})

const roleMap = ref<Record<number, string>>({})

// 格式化时间
const formatTime = (date: Date) => {
  return date.toLocaleString('zh-CN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  })
}

// 页面跳转
const goToPage = (path: string) => {
  router.push(path)
}

// 获取统计数据
const getStats = async () => {
  try {
    const [roleRes, permissionRes, logRes, adminRes] = await Promise.all([
      getRoleList({ page: 1, limit: 1000 }),
      getPermissionList({ page: 1, limit: 1000 }),
      getOperationLogList({ page: 1, limit: 1 }),
      getAdminList({ page: 1, limit: 1000 })
    ])
    
    stats.value.totalRoles = roleRes.data.data.length
    stats.value.totalPermissions = permissionRes.data.data.length
    stats.value.totalLogs = logRes.data.pagination.total
    stats.value.totalUsers = adminRes.data.pagination.total
    
    // 构建角色映射
    roleRes.data.data.forEach((role: any) => {
      roleMap.value[role.id] = role.role_name
    })
  } catch (error) {
    console.error('获取统计数据失败:', error)
  }
}

// 获取角色名称
const getRoleName = (roleId?: number) => {
  if (!roleId) return '未知'
  return roleMap.value[roleId] || '未知角色'
}

onMounted(() => {
  getStats()
})
</script>

<style scoped>
.dashboard {
  padding: 24px;
  background: #f8fafc;
  min-height: calc(100vh - 60px);
}

/* 页面标题 */
.page-header {
  margin-bottom: 32px;
}

.page-title {
  font-size: 28px;
  font-weight: 700;
  color: #1a202c;
  margin: 0 0 8px 0;
  line-height: 1.2;
}

.page-subtitle {
  font-size: 16px;
  color: #64748b;
  margin: 0;
  line-height: 1.5;
}

/* 统计卡片网格 */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 24px;
  margin-bottom: 32px;
}

.stat-card {
  background: white;
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
  border: 1px solid #e2e8f0;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #667eea, #764ba2);
}

.user-card::before { background: linear-gradient(90deg, #667eea, #764ba2); }
.role-card::before { background: linear-gradient(90deg, #f093fb, #f5576c); }
.permission-card::before { background: linear-gradient(90deg, #4facfe, #00f2fe); }
.log-card::before { background: linear-gradient(90deg, #43e97b, #38f9d7); }

.stat-content {
  display: flex;
  align-items: center;
  gap: 16px;
}

.stat-icon-wrapper {
  position: relative;
}

.stat-icon {
  width: 56px;
  height: 56px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  color: white;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.user-icon { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.role-icon { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
.permission-icon { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
.log-icon { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }

.stat-trend {
  position: absolute;
  top: -4px;
  right: -4px;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  color: white;
}

.stat-trend.up { background: #10b981; }
.stat-trend.stable { background: #6b7280; }
.stat-trend.down { background: #ef4444; }

.stat-info {
  flex: 1;
}

.stat-number {
  font-size: 32px;
  font-weight: 800;
  color: #1a202c;
  line-height: 1;
  margin-bottom: 4px;
}

.stat-label {
  font-size: 14px;
  color: #64748b;
  font-weight: 500;
  margin-bottom: 4px;
}

.stat-change {
  font-size: 12px;
  color: #10b981;
  font-weight: 600;
}

/* 内容网格 */
.content-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
}

.content-card {
  background: white;
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
  border: 1px solid #e2e8f0;
}

.card-header {
  margin-bottom: 20px;
  padding-bottom: 16px;
  border-bottom: 1px solid #e2e8f0;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-title {
  font-size: 18px;
  font-weight: 600;
  color: #1a202c;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 8px;
}

/* 系统信息 */
.system-info {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.info-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 0;
}

.info-icon {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  background: #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #475569;
  font-size: 18px;
}

.info-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.label {
  font-size: 12px;
  color: #64748b;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.value {
  font-size: 14px;
  color: #1a202c;
  font-weight: 600;
}

.role-tag {
  background: #dbeafe;
  color: #1e40af;
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 600;
}

/* 快速操作 */
.quick-actions {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.action-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  cursor: pointer;
  transition: all 0.2s ease;
}

.action-item:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
  transform: translateX(4px);
}

.action-icon {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 18px;
}

.role-action { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
.permission-action { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
.log-action { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
.performance-action { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }

.action-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.action-title {
  font-size: 14px;
  font-weight: 600;
  color: #1a202c;
}

.action-desc {
  font-size: 12px;
  color: #64748b;
}

.action-arrow {
  color: #94a3b8;
  font-size: 16px;
  transition: transform 0.2s ease;
}

.action-item:hover .action-arrow {
  transform: translateX(4px);
}

/* 响应式设计 */
@media (max-width: 1024px) {
  .content-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .dashboard {
    padding: 16px;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
    gap: 16px;
  }
  
  .page-title {
    font-size: 24px;
  }
  
  .stat-card {
    padding: 20px;
  }
  
  .content-card {
    padding: 20px;
  }
}

@media (max-width: 480px) {
  .stat-content {
    flex-direction: column;
    text-align: center;
    gap: 12px;
  }
  
  .stat-icon-wrapper {
    align-self: center;
  }
}
</style>