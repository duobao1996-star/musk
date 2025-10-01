<template>
  <div class="performance-monitoring">
    <el-row :gutter="20">
      <!-- 性能统计卡片 -->
      <el-col :span="6">
        <el-card class="stat-card">
          <div class="stat-content">
            <div class="stat-icon requests-icon">
              <el-icon><TrendCharts /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-number">{{ performanceStats.total_requests }}</div>
              <div class="stat-label">总请求数</div>
            </div>
          </div>
        </el-card>
      </el-col>
      
      <el-col :span="6">
        <el-card class="stat-card">
          <div class="stat-content">
            <div class="stat-icon time-icon">
              <el-icon><Timer /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-number">{{ performanceStats.avg_response_time }}ms</div>
              <div class="stat-label">平均响应时间</div>
            </div>
          </div>
        </el-card>
      </el-col>
      
      <el-col :span="6">
        <el-card class="stat-card">
          <div class="stat-content">
            <div class="stat-icon slow-icon">
              <el-icon><Warning /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-number">{{ performanceStats.slow_requests }}</div>
              <div class="stat-label">慢请求数</div>
            </div>
          </div>
        </el-card>
      </el-col>
      
      <el-col :span="6">
        <el-card class="stat-card">
          <div class="stat-content">
            <div class="stat-icon error-icon">
              <el-icon><CircleClose /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-number">{{ formatPercent(performanceStats.error_rate) }}</div>
              <div class="stat-label">错误率</div>
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
              <span>内存使用情况</span>
            </div>
          </template>
          <div class="memory-info">
            <div class="memory-item">
              <div class="memory-label">当前内存使用</div>
              <div class="memory-value">{{ formatMemory(performanceStats.memory_usage) }}</div>
              <el-progress 
                :percentage="getMemoryPercent(performanceStats.memory_usage, performanceStats.peak_memory)" 
                :color="getMemoryColor(getSafeRatio(performanceStats.memory_usage, performanceStats.peak_memory))"
              />
            </div>
            <div class="memory-item">
              <div class="memory-label">峰值内存使用</div>
              <div class="memory-value">{{ formatMemory(performanceStats.peak_memory) }}</div>
            </div>
          </div>
        </el-card>
      </el-col>
      
      <el-col :span="12">
        <el-card>
          <template #header>
            <div class="card-header">
              <span>系统健康状态</span>
            </div>
          </template>
          <div class="health-status">
            <div class="health-item">
              <el-icon class="health-icon success"><CircleCheck /></el-icon>
              <span>API服务</span>
              <el-tag type="success">正常</el-tag>
            </div>
            <div class="health-item">
              <el-icon class="health-icon success"><CircleCheck /></el-icon>
              <span>数据库连接</span>
              <el-tag type="success">正常</el-tag>
            </div>
            <div class="health-item">
              <el-icon class="health-icon warning"><Warning /></el-icon>
              <span>内存使用</span>
              <el-tag :type="getMemoryTagType(getSafeRatio(performanceStats.memory_usage, performanceStats.peak_memory))">
                {{ getMemoryStatus(getSafeRatio(performanceStats.memory_usage, performanceStats.peak_memory)) }}
              </el-tag>
            </div>
            <div class="health-item">
              <el-icon class="health-icon success"><CircleCheck /></el-icon>
              <span>响应时间</span>
              <el-tag :type="getResponseTimeTagType(performanceStats.avg_response_time)">
                {{ getResponseTimeStatus(performanceStats.avg_response_time) }}
              </el-tag>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 慢查询列表 -->
    <el-card style="margin-top: 20px;">
      <template #header>
        <div class="card-header">
          <span>慢查询监控</span>
          <div>
            <el-input-number 
              v-model="slowQueryThreshold" 
              :min="100" 
              :max="10000" 
              placeholder="响应时间阈值(ms)"
              style="margin-right: 10px;"
            />
            <el-button type="primary" @click="getSlowQueries">
              <el-icon><Search /></el-icon>
              查询
            </el-button>
            <el-button @click="handleRefresh">
              <el-icon><Refresh /></el-icon>
              刷新
            </el-button>
          </div>
        </div>
      </template>

      <el-table 
        v-loading="slowQueryLoading" 
        :data="slowQueryData" 
        style="width: 100%"
        max-height="400"
      >
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="endpoint" label="接口路径" min-width="200" />
        <el-table-column prop="method" label="方法" width="100">
          <template #default="{ row }">
            <el-tag :type="getMethodType(row.method)">
              {{ row.method }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="response_time" label="响应时间" width="120">
          <template #default="{ row }">
            <span :style="{ color: getResponseTimeColor(row.response_time) }">
              {{ row.response_time }}ms
            </span>
          </template>
        </el-table-column>
        <el-table-column prop="memory_usage" label="内存使用" width="120">
          <template #default="{ row }">
            {{ formatMemory(row.memory_usage) }}
          </template>
        </el-table-column>
        <el-table-column prop="status_code" label="状态码" width="100">
          <template #default="{ row }">
            <el-tag :type="row.status_code === 200 ? 'success' : 'danger'">
              {{ row.status_code }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="时间" width="180">
          <template #default="{ row }">
            {{ formatTime(row.created_at) }}
          </template>
        </el-table-column>
      </el-table>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { 
  TrendCharts, 
  Timer, 
  Warning, 
  CircleClose, 
  CircleCheck,
  Search,
  Refresh
} from '@element-plus/icons-vue'
import {
  getPerformanceStats,
  getSlowQueries as getSlowQueriesApi,
  type PerformanceStats,
  type SlowQuery
} from '@/api/performance'

const performanceStats = ref<PerformanceStats>({
  total_requests: 0,
  avg_response_time: 0,
  slow_requests: 0,
  error_rate: 0,
  memory_usage: 0,
  peak_memory: 0
})

const slowQueryData = ref<SlowQuery[]>([])
const slowQueryLoading = ref(false)
const slowQueryThreshold = ref(1000)

// 格式化内存大小
const formatMemory = (bytes: number) => {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

// 百分比格式化（0-1 -> 0-100%），容错
const formatPercent = (ratio: number) => {
  const n = Number(ratio)
  const value = isFinite(n) ? n : 0
  return (Math.min(Math.max(value, 0), 1) * 100).toFixed(2) + '%'
}

// 安全比值（分母为0时返回0）
const getSafeRatio = (num: number, den: number) => {
  const a = Number(num)
  const b = Number(den)
  if (!isFinite(a) || !isFinite(b) || b <= 0) return 0
  const r = a / b
  return r > 0 ? r : 0
}

// 进度条百分比(0-100 范围内)
const getMemoryPercent = (used: number, peak: number) => {
  const percent = getSafeRatio(used, peak) * 100
  return Math.min(Math.max(Math.round(percent), 0), 100)
}

// 获取内存使用颜色
const getMemoryColor = (ratio: number) => {
  if (ratio < 0.5) return '#67c23a'
  if (ratio < 0.8) return '#e6a23c'
  return '#f56c6c'
}

// 获取方法类型颜色
const getMethodType = (method: string) => {
  const types: Record<string, string> = {
    GET: 'success',
    POST: 'primary',
    PUT: 'warning',
    DELETE: 'danger',
    PATCH: 'info'
  }
  return types[method] || 'info'
}

// 获取响应时间颜色
const getResponseTimeColor = (time: number) => {
  if (time < 500) return '#67c23a'
  if (time < 1000) return '#e6a23c'
  return '#f56c6c'
}

// 获取内存状态标签类型
const getMemoryTagType = (ratio: number) => {
  if (ratio < 0.5) return 'success'
  if (ratio < 0.8) return 'warning'
  return 'danger'
}

// 获取内存状态文本
const getMemoryStatus = (ratio: number) => {
  if (ratio < 0.5) return '正常'
  if (ratio < 0.8) return '警告'
  return '危险'
}

// 获取响应时间标签类型
const getResponseTimeTagType = (time: number) => {
  if (time < 500) return 'success'
  if (time < 1000) return 'warning'
  return 'danger'
}

// 获取响应时间状态文本
const getResponseTimeStatus = (time: number) => {
  if (time < 500) return '优秀'
  if (time < 1000) return '良好'
  return '需要优化'
}

// 格式化时间
const formatTime = (time: string) => {
  return new Date(time).toLocaleString('zh-CN')
}

// 获取性能统计
const getPerformanceStatsData = async () => {
  try {
    const response = await getPerformanceStats()
    const d = response.data.data || {}
    // 强制数值化并提供默认值
    performanceStats.value = {
      total_requests: Number(d.total_requests) || 0,
      avg_response_time: Number(d.avg_response_time) || 0,
      slow_requests: Number(d.slow_requests) || 0,
      error_rate: Number(d.error_rate) || 0,
      memory_usage: Number(d.memory_usage) || 0,
      peak_memory: Number(d.peak_memory) || 0
    }
  } catch (error) {
    console.error('获取性能统计失败:', error)
  }
}

// 获取慢查询
const getSlowQueries = async () => {
  try {
    slowQueryLoading.value = true
    const response = await getSlowQueriesApi({
      threshold: slowQueryThreshold.value,
      limit: 50
    })
    const list = response?.data?.data
    slowQueryData.value = Array.isArray(list) ? list : []
  } catch (error) {
    ElMessage.error('获取慢查询失败')
  } finally {
    slowQueryLoading.value = false
  }
}

// 刷新
const handleRefresh = () => {
  getPerformanceStatsData()
  getSlowQueries()
}

onMounted(() => {
  getPerformanceStatsData()
  getSlowQueries()
})
</script>

<style scoped>
.performance-monitoring {
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

.requests-icon {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.time-icon {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.slow-icon {
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.error-icon {
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
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 16px;
  font-weight: 600;
}

.memory-info {
  padding: 16px 0;
}

.memory-item {
  margin-bottom: 20px;
}

.memory-item:last-child {
  margin-bottom: 0;
}

.memory-label {
  font-size: 14px;
  color: #7f8c8d;
  margin-bottom: 8px;
}

.memory-value {
  font-size: 18px;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 8px;
}

.health-status {
  padding: 16px 0;
}

.health-item {
  display: flex;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid #f0f0f0;
}

.health-item:last-child {
  border-bottom: none;
}

.health-icon {
  margin-right: 12px;
  font-size: 18px;
}

.health-icon.success {
  color: #67c23a;
}

.health-icon.warning {
  color: #e6a23c;
}

.health-icon.error {
  color: #f56c6c;
}

.health-item span {
  flex: 1;
  font-size: 14px;
  color: #2c3e50;
}
</style>
