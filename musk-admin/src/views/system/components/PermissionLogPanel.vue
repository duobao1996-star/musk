<template>
  <div class="permission-log-panel">
    <!-- 操作栏 -->
    <div class="panel-header">
      <div class="header-left">
        <h3>权限操作日志</h3>
        <p>查看权限相关的操作记录和变更历史</p>
      </div>
      <div class="header-right">
        <el-button @click="handleExport">
          <el-icon><Download /></el-icon>
          导出日志
        </el-button>
        <el-button @click="handleRefresh">
          <el-icon><Refresh /></el-icon>
          刷新
        </el-button>
      </div>
    </div>

    <!-- 筛选栏 -->
    <div class="filter-bar">
      <el-form :model="searchForm" inline>
        <el-form-item label="操作类型">
          <el-select v-model="searchForm.operation_type" placeholder="选择操作类型" clearable>
            <el-option label="全部" value="" />
            <el-option label="权限创建" value="create" />
            <el-option label="权限更新" value="update" />
            <el-option label="权限删除" value="delete" />
            <el-option label="角色权限分配" value="assign" />
            <el-option label="权限复制" value="copy" />
          </el-select>
        </el-form-item>
        
        <el-form-item label="操作模块">
          <el-select v-model="searchForm.operation_module" placeholder="选择模块" clearable>
            <el-option label="全部" value="" />
            <el-option label="权限管理" value="权限管理" />
            <el-option label="角色管理" value="角色管理" />
            <el-option label="管理员" value="管理员" />
          </el-select>
        </el-form-item>
        
        <el-form-item label="操作人">
          <el-input 
            v-model="searchForm.admin_name" 
            placeholder="输入操作人姓名"
            clearable
          />
        </el-form-item>
        
        <el-form-item label="时间范围">
          <el-date-picker
            v-model="searchForm.dateRange"
            type="datetimerange"
            range-separator="至"
            start-placeholder="开始时间"
            end-placeholder="结束时间"
            format="YYYY-MM-DD HH:mm:ss"
            value-format="YYYY-MM-DD HH:mm:ss"
          />
        </el-form-item>
        
        <el-form-item>
          <el-button type="primary" @click="handleSearch">
            <el-icon><Search /></el-icon>
            搜索
          </el-button>
          <el-button @click="handleReset">
            <el-icon><RefreshLeft /></el-icon>
            重置
          </el-button>
        </el-form-item>
      </el-form>
    </div>

    <!-- 日志统计 -->
    <div class="log-stats">
      <el-row :gutter="20">
        <el-col :span="6">
          <div class="stat-card">
            <div class="stat-icon create">
              <el-icon><Plus /></el-icon>
            </div>
            <div class="stat-content">
              <div class="stat-value">{{ stats.create }}</div>
              <div class="stat-label">新增权限</div>
            </div>
          </div>
        </el-col>
        <el-col :span="6">
          <div class="stat-card">
            <div class="stat-icon update">
              <el-icon><Edit /></el-icon>
            </div>
            <div class="stat-content">
              <div class="stat-value">{{ stats.update }}</div>
              <div class="stat-label">更新权限</div>
            </div>
          </div>
        </el-col>
        <el-col :span="6">
          <div class="stat-card">
            <div class="stat-icon delete">
              <el-icon><Delete /></el-icon>
            </div>
            <div class="stat-content">
              <div class="stat-value">{{ stats.delete }}</div>
              <div class="stat-label">删除权限</div>
            </div>
          </div>
        </el-col>
        <el-col :span="6">
          <div class="stat-card">
            <div class="stat-icon assign">
              <el-icon><User /></el-icon>
            </div>
            <div class="stat-content">
              <div class="stat-value">{{ stats.assign }}</div>
              <div class="stat-label">权限分配</div>
            </div>
          </div>
        </el-col>
      </el-row>
    </div>

    <!-- 日志表格 -->
    <div class="log-table-container">
      <el-table
        :data="filteredLogs"
        style="width: 100%"
        v-loading="loading"
        element-loading-text="加载中..."
      >
        <el-table-column prop="id" label="ID" width="80" />
        
        <el-table-column prop="admin_name" label="操作人" width="120" />
        
        <el-table-column prop="operation_type" label="操作类型" width="100">
          <template #default="{ row }">
            <el-tag :type="getOperationType(row.operation_type)" size="small">
              {{ getOperationTypeText(row.operation_type) }}
            </el-tag>
          </template>
        </el-table-column>
        
        <el-table-column prop="operation_module" label="操作模块" width="120" />
        
        <el-table-column prop="operation_desc" label="操作描述" min-width="200" />
        
        <el-table-column prop="request_method" label="请求方法" width="100">
          <template #default="{ row }">
            <el-tag :type="getMethodType(row.request_method)" size="small">
              {{ row.request_method }}
            </el-tag>
          </template>
        </el-table-column>
        
        <el-table-column prop="response_code" label="状态码" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusCodeType(row.response_code)" size="small">
              {{ row.response_code || '未知' }}
            </el-tag>
          </template>
        </el-table-column>
        
        <el-table-column prop="ip_address" label="IP地址" width="120" />
        
        <el-table-column prop="created_at" label="操作时间" width="160">
          <template #default="{ row }">
            {{ formatDateTime(row.created_at) }}
          </template>
        </el-table-column>
        
        <el-table-column label="操作" width="100" fixed="right">
          <template #default="{ row }">
            <el-button size="small" type="primary" @click="handleViewDetails(row)">
              详情
            </el-button>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <!-- 分页 -->
    <div class="pagination-container">
      <el-pagination
        v-model:current-page="currentPage"
        v-model:page-size="pageSize"
        :page-sizes="[10, 20, 50, 100]"
        :total="total"
        layout="total, sizes, prev, pager, next, jumper"
        @size-change="handleSizeChange"
        @current-change="handleCurrentChange"
      />
    </div>

    <!-- 日志详情对话框 -->
    <el-dialog
      v-model="detailDialogVisible"
      title="操作日志详情"
      width="800px"
    >
      <div v-if="selectedLog" class="log-details">
        <el-descriptions :column="2" border>
          <el-descriptions-item label="日志ID">
            {{ selectedLog.id }}
          </el-descriptions-item>
          <el-descriptions-item label="操作人">
            {{ selectedLog.admin_name }}
          </el-descriptions-item>
          <el-descriptions-item label="操作类型">
            <el-tag :type="getOperationType(selectedLog.operation_type)">
              {{ getOperationTypeText(selectedLog.operation_type) }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="操作模块">
            {{ selectedLog.operation_module }}
          </el-descriptions-item>
          <el-descriptions-item label="操作描述" :span="2">
            {{ selectedLog.operation_desc }}
          </el-descriptions-item>
          <el-descriptions-item label="请求方法">
            <el-tag :type="getMethodType(selectedLog.request_method)">
              {{ selectedLog.request_method }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="响应状态码">
            <el-tag :type="getStatusCodeType(selectedLog.response_code)">
              {{ selectedLog.response_code || '未知' }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="请求URL" :span="2">
            {{ selectedLog.request_url }}
          </el-descriptions-item>
          <el-descriptions-item label="请求参数" :span="2">
            <pre class="json-content">{{ formatJson(selectedLog.request_params) }}</pre>
          </el-descriptions-item>
          <el-descriptions-item label="响应消息" :span="2">
            {{ selectedLog.response_msg }}
          </el-descriptions-item>
          <el-descriptions-item label="IP地址">
            {{ selectedLog.ip_address }}
          </el-descriptions-item>
          <el-descriptions-item label="User Agent" :span="2">
            {{ selectedLog.user_agent }}
          </el-descriptions-item>
          <el-descriptions-item label="操作时间">
            {{ formatDateTime(selectedLog.created_at) }}
          </el-descriptions-item>
          <el-descriptions-item label="状态">
            <el-tag :type="selectedLog.status ? 'success' : 'danger'">
              {{ selectedLog.status ? '成功' : '失败' }}
            </el-tag>
          </el-descriptions-item>
        </el-descriptions>
      </div>
      
      <template #footer>
        <el-button @click="detailDialogVisible = false">关闭</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { 
  Download, Refresh, Search, RefreshLeft, Plus, Edit, Delete, User 
} from '@element-plus/icons-vue'
import { getOperationLogList } from '@/api/log'

// 响应式数据
const loading = ref(false)
const detailDialogVisible = ref(false)
const selectedLog = ref(null)
const logs = ref([])
const currentPage = ref(1)
const pageSize = ref(20)
const total = ref(0)

// 搜索表单
const searchForm = reactive({
  operation_type: '',
  operation_module: '',
  admin_name: '',
  dateRange: []
})

// 统计数据
const stats = reactive({
  create: 0,
  update: 0,
  delete: 0,
  assign: 0
})

// 过滤后的日志
const filteredLogs = computed(() => {
  let filtered = logs.value
  
  if (searchForm.operation_type) {
    filtered = filtered.filter(log => log.operation_type === searchForm.operation_type)
  }
  
  if (searchForm.operation_module) {
    filtered = filtered.filter(log => log.operation_module === searchForm.operation_module)
  }
  
  if (searchForm.admin_name) {
    filtered = filtered.filter(log => 
      log.admin_name?.toLowerCase().includes(searchForm.admin_name.toLowerCase())
    )
  }
  
  if (searchForm.dateRange && searchForm.dateRange.length === 2) {
    const [startDate, endDate] = searchForm.dateRange
    filtered = filtered.filter(log => {
      const logDate = new Date(log.created_at)
      return logDate >= new Date(startDate) && logDate <= new Date(endDate)
    })
  }
  
  return filtered
})

// 获取操作类型标签类型
const getOperationType = (type: string) => {
  const typeMap = {
    'create': 'success',
    'update': 'warning',
    'delete': 'danger',
    'assign': 'primary',
    'copy': 'info'
  }
  return typeMap[type] || 'info'
}

// 获取操作类型文本
const getOperationTypeText = (type: string) => {
  const typeMap = {
    'create': '创建',
    'update': '更新',
    'delete': '删除',
    'assign': '分配',
    'copy': '复制'
  }
  return typeMap[type] || type
}

// 获取请求方法类型
const getMethodType = (method: string) => {
  const typeMap = {
    'GET': 'success',
    'POST': 'primary',
    'PUT': 'warning',
    'DELETE': 'danger',
    'PATCH': 'info'
  }
  return typeMap[method] || 'info'
}

// 获取状态码类型
const getStatusCodeType = (code: number | null | undefined) => {
  if (!code) return 'info'
  if (code === 200) return 'success'
  if (code >= 400) return 'danger'
  return 'warning'
}

// 格式化日期时间
const formatDateTime = (dateTime: string) => {
  if (!dateTime) return ''
  return new Date(dateTime).toLocaleString('zh-CN')
}

// 格式化JSON
const formatJson = (jsonStr: string) => {
  if (!jsonStr) return ''
  try {
    return JSON.stringify(JSON.parse(jsonStr), null, 2)
  } catch {
    return jsonStr
  }
}

// 获取日志数据
const getLogs = async () => {
  try {
    loading.value = true
    const response = await getOperationLogList({
      page: currentPage.value,
      limit: pageSize.value,
      ...searchForm
    })
    
    logs.value = response.data.data || []
    total.value = response.data.total || 0
    
    // 计算统计数据
    calculateStats()
  } catch (error) {
    ElMessage.error('获取日志数据失败')
  } finally {
    loading.value = false
  }
}

// 计算统计数据
const calculateStats = () => {
  const statsData = {
    create: 0,
    update: 0,
    delete: 0,
    assign: 0
  }
  
  logs.value.forEach(log => {
    if (log.operation_type === 'create') statsData.create++
    else if (log.operation_type === 'update') statsData.update++
    else if (log.operation_type === 'delete') statsData.delete++
    else if (log.operation_type === 'assign') statsData.assign++
  })
  
  Object.assign(stats, statsData)
}

// 操作方法
const handleSearch = () => {
  currentPage.value = 1
  getLogs()
}

const handleReset = () => {
  Object.assign(searchForm, {
    operation_type: '',
    operation_module: '',
    admin_name: '',
    dateRange: []
  })
  currentPage.value = 1
  getLogs()
}

const handleRefresh = () => {
  getLogs()
}

const handleExport = () => {
  ElMessage.info('导出功能开发中...')
}

const handleViewDetails = (log: any) => {
  selectedLog.value = log
  detailDialogVisible.value = true
}

const handleSizeChange = (size: number) => {
  pageSize.value = size
  getLogs()
}

const handleCurrentChange = (page: number) => {
  currentPage.value = page
  getLogs()
}

// 初始化
onMounted(() => {
  getLogs()
})
</script>

<style scoped>
.permission-log-panel {
  height: 100%;
}

.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding-bottom: 16px;
  border-bottom: 1px solid #e4e7ed;
}

.header-left h3 {
  margin: 0 0 4px 0;
  color: #303133;
  font-size: 18px;
  font-weight: 600;
}

.header-left p {
  margin: 0;
  color: #909399;
  font-size: 14px;
}

.filter-bar {
  background: #f8f9fa;
  padding: 16px;
  border-radius: 8px;
  margin-bottom: 20px;
}

.log-stats {
  margin-bottom: 20px;
}

.stat-card {
  display: flex;
  align-items: center;
  padding: 16px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.stat-icon {
  width: 40px;
  height: 40px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 12px;
  font-size: 20px;
  color: white;
}

.stat-icon.create {
  background: linear-gradient(135deg, #67c23a 0%, #85ce61 100%);
}

.stat-icon.update {
  background: linear-gradient(135deg, #e6a23c 0%, #f0c78a 100%);
}

.stat-icon.delete {
  background: linear-gradient(135deg, #f56c6c 0%, #f89898 100%);
}

.stat-icon.assign {
  background: linear-gradient(135deg, #409eff 0%, #79bbff 100%);
}

.stat-content {
  flex: 1;
}

.stat-value {
  font-size: 20px;
  font-weight: 600;
  color: #303133;
  line-height: 1;
  margin-bottom: 4px;
}

.stat-label {
  font-size: 12px;
  color: #909399;
}

.log-table-container {
  background: white;
  border-radius: 8px;
  border: 1px solid #e4e7ed;
  padding: 16px;
  margin-bottom: 20px;
}

.pagination-container {
  display: flex;
  justify-content: center;
}

.log-details {
  padding: 16px 0;
}

.json-content {
  background: #f5f7fa;
  padding: 8px;
  border-radius: 4px;
  font-family: monospace;
  font-size: 12px;
  max-height: 200px;
  overflow-y: auto;
  white-space: pre-wrap;
  word-break: break-all;
}
</style>
