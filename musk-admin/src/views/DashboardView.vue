<template>
  <div class="dashboard">
    <el-row :gutter="20">
      <el-col :span="6">
        <el-card class="stat-card">
          <div class="stat-content">
            <div class="stat-icon user-icon">
              <el-icon><User /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-number">{{ stats.totalUsers }}</div>
              <div class="stat-label">管理员数量</div>
            </div>
          </div>
        </el-card>
      </el-col>
      
      <el-col :span="6">
        <el-card class="stat-card">
          <div class="stat-content">
            <div class="stat-icon role-icon">
              <el-icon><UserFilled /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-number">{{ stats.totalRoles }}</div>
              <div class="stat-label">角色数量</div>
            </div>
          </div>
        </el-card>
      </el-col>
      
      <el-col :span="6">
        <el-card class="stat-card">
          <div class="stat-content">
            <div class="stat-icon permission-icon">
              <el-icon><Key /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-number">{{ stats.totalPermissions }}</div>
              <div class="stat-label">权限数量</div>
            </div>
          </div>
        </el-card>
      </el-col>
      
      <el-col :span="6">
        <el-card class="stat-card">
          <div class="stat-content">
            <div class="stat-icon log-icon">
              <el-icon><Document /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-number">{{ stats.totalLogs }}</div>
              <div class="stat-label">操作日志</div>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <el-row :gutter="20" style="margin-top: 20px;">
      <el-col :span="12">
        <el-card>
          <template #header>
            <div class="card-header">
              <span>系统信息</span>
            </div>
          </template>
          <div class="system-info">
            <div class="info-item">
              <span class="label">系统名称:</span>
              <span class="value">Musk管理系统</span>
            </div>
            <div class="info-item">
              <span class="label">当前用户:</span>
              <span class="value">{{ userInfo?.username }}</span>
            </div>
            <div class="info-item">
              <span class="label">用户角色:</span>
              <span class="value">{{ getRoleName(userInfo?.role_id) }}</span>
            </div>
            <div class="info-item">
              <span class="label">登录时间:</span>
              <span class="value">{{ formatTime(new Date()) }}</span>
            </div>
          </div>
        </el-card>
      </el-col>
      
      <el-col :span="12">
        <el-card>
          <template #header>
            <div class="card-header">
              <span>快速操作</span>
            </div>
          </template>
          <div class="quick-actions">
            <el-button type="primary" @click="goToPage('/system/roles')">
              <el-icon><UserFilled /></el-icon>
              角色管理
            </el-button>
            <el-button type="success" @click="goToPage('/system/permissions')">
              <el-icon><Key /></el-icon>
              权限管理
            </el-button>
            <el-button type="warning" @click="goToPage('/system/logs')">
              <el-icon><Document /></el-icon>
              操作日志
            </el-button>
            <el-button type="info" @click="goToPage('/system/performance')">
              <el-icon><Monitor /></el-icon>
              性能监控
            </el-button>
          </div>
        </el-card>
      </el-col>
    </el-row>
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
  Monitor 
} from '@element-plus/icons-vue'
import { useAuthStore } from '@/stores/auth'
import { getRoleList } from '@/api/role'
import { getPermissionList } from '@/api/permission'
import { getOperationLogList } from '@/api/operationLog'

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

// 获取统计数据
const getStats = async () => {
  try {
    const [roleRes, permissionRes, logRes] = await Promise.all([
      getRoleList({ page: 1, limit: 1000 }),
      getPermissionList({ page: 1, limit: 1000 }),
      getOperationLogList({ page: 1, limit: 1 })
    ])
    
    stats.value.totalRoles = roleRes.data.data.length
    stats.value.totalPermissions = permissionRes.data.data.length
    stats.value.totalLogs = logRes.data.pagination.total
    
    // 构建角色映射
    roleRes.data.data.forEach((role: any) => {
      roleMap.value[role.id] = role.role_name
    })
    
    // 模拟用户数量
    stats.value.totalUsers = 4
  } catch (error) {
    console.error('获取统计数据失败:', error)
  }
}

// 获取角色名称
const getRoleName = (roleId?: number) => {
  if (!roleId) return '未知'
  return roleMap.value[roleId] || '未知角色'
}

// 格式化时间
const formatTime = (date: Date) => {
  return date.toLocaleString('zh-CN')
}

// 跳转页面
const goToPage = (path: string) => {
  router.push(path)
}

onMounted(() => {
  getStats()
})
</script>

<style scoped>
.dashboard {
  padding: 20px;
}

.stat-card {
  margin-bottom: 20px;
}

.stat-content {
  display: flex;
  align-items: center;
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  color: #fff;
  margin-right: 16px;
}

.user-icon {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.role-icon {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.permission-icon {
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.log-icon {
  background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.stat-info {
  flex: 1;
}

.stat-number {
  font-size: 28px;
  font-weight: bold;
  color: #2c3e50;
  line-height: 1;
  margin-bottom: 4px;
}

.stat-label {
  font-size: 14px;
  color: #7f8c8d;
}

.card-header {
  font-size: 16px;
  font-weight: 600;
  color: #2c3e50;
}

.system-info {
  padding: 16px 0;
}

.info-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid #f0f0f0;
}

.info-item:last-child {
  border-bottom: none;
}

.label {
  color: #7f8c8d;
  font-size: 14px;
}

.value {
  color: #2c3e50;
  font-weight: 500;
}

.quick-actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  padding: 16px 0;
}

.quick-actions .el-button {
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}
</style>