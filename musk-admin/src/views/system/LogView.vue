<template>
  <div class="log-management">
    <el-card>
      <template #header>
        <div class="card-header">
          <span>操作日志</span>
          <div>
            <el-button @click="handleRefresh">
              <el-icon><Refresh /></el-icon>
              刷新
            </el-button>
            <el-button type="warning" @click="handleClean">
              <el-icon><Delete /></el-icon>
              清理旧日志
            </el-button>
            <el-button type="info" @click="showStats = true">
              <el-icon><DataAnalysis /></el-icon>
              统计信息
            </el-button>
          </div>
        </div>
      </template>

      <!-- 搜索表单 -->
      <el-form :model="searchForm" inline class="search-form">
        <el-form-item label="操作人">
          <el-input v-model="searchForm.admin_name" placeholder="请输入操作人" clearable />
        </el-form-item>
        <el-form-item label="操作类型">
          <el-select v-model="searchForm.operation_type" placeholder="请选择操作类型" clearable>
            <el-option label="登录" value="login" />
            <el-option label="登出" value="logout" />
            <el-option label="查看" value="view" />
            <el-option label="创建" value="create" />
            <el-option label="更新" value="update" />
            <el-option label="删除" value="delete" />
          </el-select>
        </el-form-item>
        <el-form-item label="操作模块">
          <el-select v-model="searchForm.operation_module" placeholder="请选择操作模块" clearable>
            <el-option label="权限管理" value="permission" />
            <el-option label="角色管理" value="role" />
            <el-option label="操作日志" value="operation_log" />
            <el-option label="性能监控" value="performance" />
          </el-select>
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

      <!-- 表格 -->
      <el-table 
        v-loading="loading" 
        :data="tableData" 
        style="width: 100%"
        max-height="600"
      >
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="admin_name" label="操作人" width="120" />
        <el-table-column prop="operation_desc" label="操作描述" min-width="200" />
        <el-table-column prop="operation_type" label="操作类型" width="100">
          <template #default="{ row }">
            <el-tag :type="getOperationType(row.operation_type)">
              {{ getOperationTypeText(row.operation_type) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="operation_module" label="操作模块" width="120" />
        <el-table-column prop="request_method" label="请求方法" width="100">
          <template #default="{ row }">
            <el-tag :type="getMethodType(row.request_method)">
              {{ row.request_method }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="request_url" label="请求URL" min-width="200" />
        <el-table-column prop="ip_address" label="IP地址" width="140" />
        <el-table-column prop="response_code" label="状态码" width="100">
          <template #default="{ row }">
            <el-tag :type="row.response_code === 200 ? 'success' : 'danger'">
              {{ row.response_code }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="operation_time" label="操作时间" width="180">
          <template #default="{ row }">
            {{ formatTime(row.operation_time) }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="120" fixed="right">
          <template #default="{ row }">
            <el-button type="info" size="small" @click="handleViewDetail(row)">
              详情
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
      <div class="pagination-container">
        <el-pagination
          v-model:current-page="pagination.page"
          v-model:page-size="pagination.limit"
          :page-sizes="[10, 20, 50, 100]"
          :total="pagination.total"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
        />
      </div>
    </el-card>

    <!-- 统计信息对话框 -->
    <el-dialog v-model="showStats" title="操作日志统计" width="800px">
      <el-row :gutter="20">
        <el-col :span="6">
          <el-statistic title="总操作数" :value="stats.total_operations" />
        </el-col>
        <el-col :span="6">
          <el-statistic title="今日操作" :value="stats.today_operations" />
        </el-col>
        <el-col :span="6">
          <el-statistic title="登录次数" :value="stats.login_count" />
        </el-col>
        <el-col :span="6">
          <el-statistic title="错误次数" :value="stats.error_count" />
        </el-col>
      </el-row>
      
      <el-divider />
      
      <h4>模块统计</h4>
      <el-table :data="stats.module_stats" style="width: 100%">
        <el-table-column prop="module" label="模块" />
        <el-table-column prop="count" label="操作次数" />
      </el-table>
    </el-dialog>

    <!-- 详情对话框 -->
    <el-dialog v-model="showDetail" title="操作详情" width="600px">
      <div v-if="detailData">
        <el-descriptions :column="2" border>
          <el-descriptions-item label="操作ID">{{ detailData.id }}</el-descriptions-item>
          <el-descriptions-item label="操作人">{{ detailData.admin_name }}</el-descriptions-item>
          <el-descriptions-item label="操作描述">{{ detailData.operation_desc }}</el-descriptions-item>
          <el-descriptions-item label="操作类型">{{ getOperationTypeText(detailData.operation_type) }}</el-descriptions-item>
          <el-descriptions-item label="操作模块">{{ detailData.operation_module }}</el-descriptions-item>
          <el-descriptions-item label="请求方法">{{ detailData.request_method }}</el-descriptions-item>
          <el-descriptions-item label="请求URL" span="2">{{ detailData.request_url }}</el-descriptions-item>
          <el-descriptions-item label="请求参数" span="2">
            <pre>{{ detailData.request_params }}</pre>
          </el-descriptions-item>
          <el-descriptions-item label="响应状态码">{{ detailData.response_code }}</el-descriptions-item>
          <el-descriptions-item label="响应消息">{{ detailData.response_msg }}</el-descriptions-item>
          <el-descriptions-item label="IP地址">{{ detailData.ip_address }}</el-descriptions-item>
          <el-descriptions-item label="操作时间">{{ formatTime(detailData.operation_time) }}</el-descriptions-item>
          <el-descriptions-item label="User Agent" span="2">{{ detailData.user_agent }}</el-descriptions-item>
        </el-descriptions>
      </div>
    </el-dialog>

    <!-- 清理日志对话框 -->
    <el-dialog v-model="showCleanDialog" title="清理旧日志" width="400px">
      <el-form :model="cleanForm" label-width="100px">
        <el-form-item label="保留天数">
          <el-input-number v-model="cleanForm.days" :min="1" :max="365" />
          <div class="form-tip">将删除指定天数之前的日志记录</div>
        </el-form-item>
      </el-form>
      
      <template #footer>
        <el-button @click="showCleanDialog = false">取消</el-button>
        <el-button type="danger" @click="handleConfirmClean">确定清理</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { 
  Refresh, 
  Delete, 
  DataAnalysis, 
  Search, 
  RefreshLeft 
} from '@element-plus/icons-vue'
import {
  getOperationLogList,
  getOperationLogStats,
  cleanOldLogs,
  type OperationLog,
  type OperationLogStats
} from '@/api/operationLog'

const loading = ref(false)
const tableData = ref<OperationLog[]>([])
const showStats = ref(false)
const showDetail = ref(false)
const showCleanDialog = ref(false)
const detailData = ref<OperationLog | null>(null)
const stats = ref<OperationLogStats>({
  total_operations: 0,
  today_operations: 0,
  login_count: 0,
  error_count: 0,
  module_stats: []
})

const pagination = reactive({
  page: 1,
  limit: 10,
  total: 0
})

const searchForm = reactive({
  admin_name: '',
  operation_type: '',
  operation_module: '',
  dateRange: null as [string, string] | null
})

const cleanForm = reactive({
  days: 30
})

// 获取操作类型颜色
const getOperationType = (type: string) => {
  const types: Record<string, string> = {
    login: 'success',
    logout: 'info',
    view: 'primary',
    create: 'success',
    update: 'warning',
    delete: 'danger'
  }
  return types[type] || 'info'
}

// 获取操作类型文本
const getOperationTypeText = (type: string) => {
  const types: Record<string, string> = {
    login: '登录',
    logout: '登出',
    view: '查看',
    create: '创建',
    update: '更新',
    delete: '删除'
  }
  return types[type] || type
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

// 格式化时间
const formatTime = (time: string) => {
  return new Date(time).toLocaleString('zh-CN')
}

// 获取列表数据
const getList = async () => {
  try {
    loading.value = true
    const params: any = {
      page: pagination.page,
      limit: pagination.limit
    }
    
    // 添加搜索条件
    if (searchForm.admin_name) params.admin_name = searchForm.admin_name
    if (searchForm.operation_type) params.operation_type = searchForm.operation_type
    if (searchForm.operation_module) params.operation_module = searchForm.operation_module
    if (searchForm.dateRange) {
      params.start_time = searchForm.dateRange[0]
      params.end_time = searchForm.dateRange[1]
    }
    
    const response = await getOperationLogList(params)
    tableData.value = response.data.data
    pagination.total = response.data.pagination.total
  } catch (error) {
    ElMessage.error('获取列表失败')
  } finally {
    loading.value = false
  }
}

// 获取统计信息
const getStats = async () => {
  try {
    const response = await getOperationLogStats()
    stats.value = response.data.data
  } catch (error) {
    console.error('获取统计信息失败:', error)
  }
}

// 搜索
const handleSearch = () => {
  pagination.page = 1
  getList()
}

// 重置
const handleReset = () => {
  Object.assign(searchForm, {
    admin_name: '',
    operation_type: '',
    operation_module: '',
    dateRange: null
  })
  pagination.page = 1
  getList()
}

// 刷新
const handleRefresh = () => {
  getList()
}

// 查看详情
const handleViewDetail = (row: OperationLog) => {
  detailData.value = row
  showDetail.value = true
}

// 清理日志
const handleClean = () => {
  showCleanDialog.value = true
}

// 确认清理
const handleConfirmClean = async () => {
  try {
    await cleanOldLogs(cleanForm.days)
    ElMessage.success('清理完成')
    showCleanDialog.value = false
    getList()
  } catch (error) {
    ElMessage.error('清理失败')
  }
}

// 分页相关
const handleSizeChange = (size: number) => {
  pagination.limit = size
  pagination.page = 1
  getList()
}

const handleCurrentChange = (page: number) => {
  pagination.page = page
  getList()
}

onMounted(() => {
  getList()
  getStats()
})
</script>

<style scoped>
.log-management {
  padding: 20px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 16px;
  font-weight: 600;
}

.search-form {
  margin-bottom: 20px;
  padding: 20px;
  background: #f8f9fa;
  border-radius: 8px;
}

.pagination-container {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}

.form-tip {
  font-size: 12px;
  color: #999;
  margin-top: 4px;
}

pre {
  white-space: pre-wrap;
  word-break: break-all;
  font-size: 12px;
  color: #666;
  background: #f5f5f5;
  padding: 8px;
  border-radius: 4px;
  margin: 0;
}
</style>
