<template>
  <div class="system-management">
    <!-- 页面头部 -->
    <div class="page-header">
      <div class="header-content">
        <div class="title-section">
          <h1 class="page-title">系统管理</h1>
          <p class="page-subtitle">系统信息、配置和状态监控</p>
        </div>
        <div class="header-actions">
          <el-button type="primary" @click="refreshData" :loading="loading">
            <el-icon><Refresh /></el-icon>
            刷新数据
          </el-button>
          <el-button type="success" @click="clearCache" :loading="cacheLoading">
            <el-icon><Delete /></el-icon>
            清理缓存
          </el-button>
        </div>
      </div>
    </div>

    <!-- 主要内容 -->
    <div class="content-grid">
      <!-- 系统状态卡片 -->
      <div class="status-card">
        <div class="card-header">
          <h3 class="card-title">
            <el-icon><Monitor /></el-icon>
            系统状态
          </h3>
          <el-tag :type="getStatusType(systemStatus?.system?.status)" size="small">
            {{ getStatusText(systemStatus?.system?.status) }}
          </el-tag>
        </div>
        <div class="status-content">
          <div class="status-item">
            <div class="status-label">运行状态</div>
            <div class="status-value">{{ systemStatus?.system?.status || 'Unknown' }}</div>
          </div>
          <div class="status-item">
            <div class="status-label">运行时间</div>
            <div class="status-value">{{ systemStatus?.system?.uptime || 'Unknown' }}</div>
          </div>
          <div class="status-item">
            <div class="status-label">内存使用率</div>
            <div class="status-value">{{ systemStatus?.system?.memory_usage?.usage_percent || 0 }}%</div>
          </div>
          <div class="status-item">
            <div class="status-label">CPU使用率</div>
            <div class="status-value">{{ systemStatus?.system?.cpu_usage || 0 }}%</div>
          </div>
        </div>
      </div>

      <!-- 数据库状态卡片 -->
      <div class="status-card">
        <div class="card-header">
          <h3 class="card-title">
            <el-icon><Database /></el-icon>
            数据库状态
          </h3>
          <el-tag :type="getStatusType(systemStatus?.database?.status)" size="small">
            {{ getStatusText(systemStatus?.database?.status) }}
          </el-tag>
        </div>
        <div class="status-content">
          <div class="status-item">
            <div class="status-label">连接状态</div>
            <div class="status-value">{{ systemStatus?.database?.status || 'Unknown' }}</div>
          </div>
          <div class="status-item">
            <div class="status-label">活跃连接</div>
            <div class="status-value">{{ systemStatus?.database?.connections || 0 }}</div>
          </div>
          <div class="status-item">
            <div class="status-label">慢查询(1小时)</div>
            <div class="status-value">{{ systemStatus?.database?.slow_queries || 0 }}</div>
          </div>
        </div>
      </div>

      <!-- 存储状态卡片 -->
      <div class="status-card">
        <div class="card-header">
          <h3 class="card-title">
            <el-icon><Folder /></el-icon>
            存储状态
          </h3>
          <el-tag :type="getStorageStatusType(systemStatus?.storage?.usage_percent)" size="small">
            {{ systemStatus?.storage?.usage_percent || 0 }}% 已使用
          </el-tag>
        </div>
        <div class="status-content">
          <div class="status-item">
            <div class="status-label">使用率</div>
            <div class="status-value">{{ systemStatus?.storage?.usage_percent || 0 }}%</div>
          </div>
          <div class="status-item">
            <div class="status-label">状态</div>
            <div class="status-value">{{ getStatusText(systemStatus?.storage?.status) }}</div>
          </div>
        </div>
      </div>

      <!-- 缓存状态卡片 -->
      <div class="status-card">
        <div class="card-header">
          <h3 class="card-title">
            <el-icon><Coin /></el-icon>
            缓存状态
          </h3>
          <el-tag :type="getStatusType(systemStatus?.cache?.status)" size="small">
            {{ getStatusText(systemStatus?.cache?.status) }}
          </el-tag>
        </div>
        <div class="status-content">
          <div class="status-item">
            <div class="status-label">状态</div>
            <div class="status-value">{{ getStatusText(systemStatus?.cache?.status) }}</div>
          </div>
          <div class="status-item">
            <div class="status-label">命中率</div>
            <div class="status-value">{{ systemStatus?.cache?.hit_rate || 0 }}%</div>
          </div>
        </div>
      </div>
    </div>

    <!-- 系统信息卡片 -->
    <div class="info-card">
      <div class="card-header">
        <h3 class="card-title">
          <el-icon><Setting /></el-icon>
          系统信息
        </h3>
      </div>
      <div class="info-content">
        <el-row :gutter="20">
          <el-col :span="12">
            <div class="info-section">
              <h4>系统环境</h4>
              <div class="info-item">
                <span class="info-label">系统名称:</span>
                <span class="info-value">{{ systemInfo?.system?.name || 'Unknown' }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">系统版本:</span>
                <span class="info-value">{{ systemInfo?.system?.version || 'Unknown' }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">PHP版本:</span>
                <span class="info-value">{{ systemInfo?.system?.php_version || 'Unknown' }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">服务器软件:</span>
                <span class="info-value">{{ systemInfo?.system?.server_software || 'Unknown' }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">操作系统:</span>
                <span class="info-value">{{ systemInfo?.system?.server_os || 'Unknown' }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">时区:</span>
                <span class="info-value">{{ systemInfo?.system?.timezone || 'Unknown' }}</span>
              </div>
            </div>
          </el-col>
          <el-col :span="12">
            <div class="info-section">
              <h4>数据库信息</h4>
              <div class="info-item">
                <span class="info-label">数据库类型:</span>
                <span class="info-value">{{ systemInfo?.database?.type || 'Unknown' }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">数据库版本:</span>
                <span class="info-value">{{ systemInfo?.database?.version || 'Unknown' }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">字符集:</span>
                <span class="info-value">{{ systemInfo?.database?.charset || 'Unknown' }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">排序规则:</span>
                <span class="info-value">{{ systemInfo?.database?.collation || 'Unknown' }}</span>
              </div>
            </div>
          </el-col>
        </el-row>
      </div>
    </div>

    <!-- 统计信息卡片 -->
    <div class="stats-card">
      <div class="card-header">
        <h3 class="card-title">
          <el-icon><DataAnalysis /></el-icon>
          数据统计
        </h3>
      </div>
      <div class="stats-content">
        <el-row :gutter="20">
          <el-col :span="6">
            <div class="stat-item">
              <div class="stat-icon admin-icon">
                <el-icon><User /></el-icon>
              </div>
              <div class="stat-info">
                <div class="stat-number">{{ systemInfo?.statistics?.total_admins || 0 }}</div>
                <div class="stat-label">管理员数量</div>
              </div>
            </div>
          </el-col>
          <el-col :span="6">
            <div class="stat-item">
              <div class="stat-icon role-icon">
                <el-icon><UserFilled /></el-icon>
              </div>
              <div class="stat-info">
                <div class="stat-number">{{ systemInfo?.statistics?.total_roles || 0 }}</div>
                <div class="stat-label">角色数量</div>
              </div>
            </div>
          </el-col>
          <el-col :span="6">
            <div class="stat-item">
              <div class="stat-icon permission-icon">
                <el-icon><Key /></el-icon>
              </div>
              <div class="stat-info">
                <div class="stat-number">{{ systemInfo?.statistics?.total_permissions || 0 }}</div>
                <div class="stat-label">权限数量</div>
              </div>
            </div>
          </el-col>
          <el-col :span="6">
            <div class="stat-item">
              <div class="stat-icon log-icon">
                <el-icon><Document /></el-icon>
              </div>
              <div class="stat-info">
                <div class="stat-number">{{ systemInfo?.statistics?.total_logs || 0 }}</div>
                <div class="stat-label">日志数量</div>
              </div>
            </div>
          </el-col>
        </el-row>
      </div>
    </div>

    <!-- 系统配置卡片 -->
    <div class="config-card">
      <div class="card-header">
        <h3 class="card-title">
          <el-icon><Tools /></el-icon>
          系统配置
        </h3>
        <el-button type="primary" size="small" @click="editConfig">
          <el-icon><Edit /></el-icon>
          编辑配置
        </el-button>
      </div>
      <div class="config-content">
        <el-row :gutter="20">
          <el-col :span="8">
            <div class="config-section">
              <h4>应用配置</h4>
              <div class="config-item">
                <span class="config-label">应用名称:</span>
                <span class="config-value">{{ systemConfig?.app?.name || 'Unknown' }}</span>
              </div>
              <div class="config-item">
                <span class="config-label">调试模式:</span>
                <el-tag :type="systemConfig?.app?.debug ? 'danger' : 'success'" size="small">
                  {{ systemConfig?.app?.debug ? '开启' : '关闭' }}
                </el-tag>
              </div>
              <div class="config-item">
                <span class="config-label">时区:</span>
                <span class="config-value">{{ systemConfig?.app?.timezone || 'Unknown' }}</span>
              </div>
            </div>
          </el-col>
          <el-col :span="8">
            <div class="config-section">
              <h4>JWT配置</h4>
              <div class="config-item">
                <span class="config-label">密钥:</span>
                <span class="config-value">{{ systemConfig?.jwt?.secret || '***' }}</span>
              </div>
              <div class="config-item">
                <span class="config-label">过期时间:</span>
                <span class="config-value">{{ systemConfig?.jwt?.expire || 0 }}秒</span>
              </div>
              <div class="config-item">
                <span class="config-label">算法:</span>
                <span class="config-value">{{ systemConfig?.jwt?.algorithm || 'Unknown' }}</span>
              </div>
            </div>
          </el-col>
          <el-col :span="8">
            <div class="config-section">
              <h4>缓存配置</h4>
              <div class="config-item">
                <span class="config-label">驱动:</span>
                <span class="config-value">{{ systemConfig?.cache?.driver || 'Unknown' }}</span>
              </div>
              <div class="config-item">
                <span class="config-label">TTL:</span>
                <span class="config-value">{{ systemConfig?.cache?.ttl || 0 }}秒</span>
              </div>
            </div>
          </el-col>
        </el-row>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { 
  Refresh, 
  Delete, 
  Monitor, 
  Database, 
  Folder, 
  Coin, 
  Setting, 
  DataAnalysis, 
  User, 
  UserFilled, 
  Key, 
  Document, 
  Tools, 
  Edit 
} from '@element-plus/icons-vue'
import { 
  getSystemInfo, 
  getSystemConfig, 
  getSystemStatus, 
  clearSystemCache 
} from '@/api/system'

const loading = ref(false)
const cacheLoading = ref(false)
const systemInfo = ref<any>(null)
const systemConfig = ref<any>(null)
const systemStatus = ref<any>(null)

// 获取系统信息
const fetchSystemInfo = async () => {
  try {
    const response = await getSystemInfo()
    systemInfo.value = response.data
  } catch (error) {
    console.error('获取系统信息失败:', error)
  }
}

// 获取系统配置
const fetchSystemConfig = async () => {
  try {
    const response = await getSystemConfig()
    systemConfig.value = response.data
  } catch (error) {
    console.error('获取系统配置失败:', error)
  }
}

// 获取系统状态
const fetchSystemStatus = async () => {
  try {
    const response = await getSystemStatus()
    systemStatus.value = response.data
  } catch (error) {
    console.error('获取系统状态失败:', error)
  }
}

// 刷新数据
const refreshData = async () => {
  loading.value = true
  try {
    await Promise.all([
      fetchSystemInfo(),
      fetchSystemConfig(),
      fetchSystemStatus()
    ])
    ElMessage.success('数据刷新成功')
  } catch (error) {
    ElMessage.error('数据刷新失败')
  } finally {
    loading.value = false
  }
}

// 清理缓存
const clearCache = async () => {
  cacheLoading.value = true
  try {
    await clearSystemCache('all')
    ElMessage.success('缓存清理成功')
    await fetchSystemStatus()
  } catch (error) {
    ElMessage.error('缓存清理失败')
  } finally {
    cacheLoading.value = false
  }
}

// 编辑配置
const editConfig = () => {
  ElMessage.info('配置编辑功能开发中...')
}

// 获取状态类型
const getStatusType = (status: string) => {
  switch (status) {
    case 'running':
    case 'connected':
    case 'healthy':
    case 'normal':
    case 'available':
      return 'success'
    case 'warning':
      return 'warning'
    case 'critical':
    case 'disconnected':
    case 'unhealthy':
    case 'unavailable':
      return 'danger'
    default:
      return 'info'
  }
}

// 获取状态文本
const getStatusText = (status: string) => {
  switch (status) {
    case 'running':
      return '运行中'
    case 'connected':
      return '已连接'
    case 'healthy':
      return '健康'
    case 'normal':
      return '正常'
    case 'available':
      return '可用'
    case 'warning':
      return '警告'
    case 'critical':
      return '严重'
    case 'disconnected':
      return '未连接'
    case 'unhealthy':
      return '不健康'
    case 'unavailable':
      return '不可用'
    default:
      return status || '未知'
  }
}

// 获取存储状态类型
const getStorageStatusType = (usagePercent: number) => {
  if (usagePercent >= 90) return 'danger'
  if (usagePercent >= 80) return 'warning'
  return 'success'
}

onMounted(() => {
  refreshData()
})
</script>

<style scoped>
.system-management {
  padding: 24px;
  background: #f8fafc;
  min-height: calc(100vh - 60px);
}

/* 页面头部 */
.page-header {
  margin-bottom: 24px;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  background: white;
  padding: 24px;
  border-radius: 16px;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
  border: 1px solid #e2e8f0;
}

.title-section {
  flex: 1;
}

.page-title {
  font-size: 24px;
  font-weight: 700;
  color: #1a202c;
  margin: 0 0 8px 0;
  line-height: 1.2;
}

.page-subtitle {
  font-size: 14px;
  color: #64748b;
  margin: 0;
  line-height: 1.5;
}

.header-actions {
  display: flex;
  gap: 12px;
}

/* 内容网格 */
.content-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 24px;
  margin-bottom: 24px;
}

/* 状态卡片 */
.status-card, .info-card, .stats-card, .config-card {
  background: white;
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
  border: 1px solid #e2e8f0;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding-bottom: 16px;
  border-bottom: 1px solid #e2e8f0;
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

/* 状态内容 */
.status-content {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.status-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 0;
}

.status-label {
  font-size: 14px;
  color: #64748b;
  font-weight: 500;
}

.status-value {
  font-size: 14px;
  color: #1a202c;
  font-weight: 600;
}

/* 信息内容 */
.info-content {
  padding: 16px 0;
}

.info-section {
  margin-bottom: 24px;
}

.info-section h4 {
  font-size: 16px;
  font-weight: 600;
  color: #1a202c;
  margin: 0 0 16px 0;
  padding-bottom: 8px;
  border-bottom: 1px solid #e2e8f0;
}

.info-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
}

.info-label {
  font-size: 14px;
  color: #64748b;
  font-weight: 500;
}

.info-value {
  font-size: 14px;
  color: #1a202c;
  font-weight: 600;
}

/* 统计内容 */
.stats-content {
  padding: 16px 0;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  border-radius: 12px;
  background: #f8fafc;
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 20px;
}

.admin-icon { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.role-icon { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
.permission-icon { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
.log-icon { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }

.stat-info {
  flex: 1;
}

.stat-number {
  font-size: 24px;
  font-weight: 800;
  color: #1a202c;
  line-height: 1;
  margin-bottom: 4px;
}

.stat-label {
  font-size: 14px;
  color: #64748b;
  font-weight: 500;
}

/* 配置内容 */
.config-content {
  padding: 16px 0;
}

.config-section {
  margin-bottom: 24px;
}

.config-section h4 {
  font-size: 16px;
  font-weight: 600;
  color: #1a202c;
  margin: 0 0 16px 0;
  padding-bottom: 8px;
  border-bottom: 1px solid #e2e8f0;
}

.config-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
}

.config-label {
  font-size: 14px;
  color: #64748b;
  font-weight: 500;
}

.config-value {
  font-size: 14px;
  color: #1a202c;
  font-weight: 600;
}

/* 响应式设计 */
@media (max-width: 768px) {
  .system-management {
    padding: 16px;
  }
  
  .header-content {
    flex-direction: column;
    gap: 16px;
    align-items: stretch;
  }
  
  .content-grid {
    grid-template-columns: 1fr;
    gap: 16px;
  }
  
  .stat-item {
    flex-direction: column;
    text-align: center;
    gap: 12px;
  }
}
</style>