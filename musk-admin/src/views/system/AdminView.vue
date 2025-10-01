<template>
  <div class="admin-management">
    <!-- 页面头部 -->
    <div class="page-header">
      <div class="header-content">
        <div class="title-section">
          <h1 class="page-title">管理员账号</h1>
          <p class="page-subtitle">管理系统管理员账户信息</p>
        </div>
        <el-button type="primary" @click="openCreate" class="add-btn">
          <el-icon><Plus /></el-icon>
          新增管理员
        </el-button>
      </div>
    </div>

    <!-- 主要内容卡片 -->
    <div class="main-card">
      <!-- 搜索区域 -->
      <div class="search-section">
        <div class="search-form">
          <div class="form-item">
            <label class="form-label">搜索关键词</label>
            <el-input 
              v-model="query.keyword" 
              placeholder="用户名/邮箱" 
              clearable 
              class="search-input"
              @keyup.enter="fetchList"
            >
              <template #prefix>
                <el-icon><Search /></el-icon>
              </template>
            </el-input>
          </div>
          <div class="form-item">
            <label class="form-label">账户状态</label>
            <el-select v-model="query.status" placeholder="请选择状态" clearable class="status-select">
              <el-option label="全部" :value="''" />
              <el-option label="正常" :value="1" />
              <el-option label="禁用" :value="0" />
            </el-select>
          </div>
          <div class="form-actions">
            <el-button type="primary" @click="fetchList" :loading="loading">
              <el-icon><Search /></el-icon>
              搜索
            </el-button>
            <el-button @click="resetSearch">
              <el-icon><Refresh /></el-icon>
              重置
            </el-button>
          </div>
        </div>
      </div>

      <!-- 表格区域 -->
      <div class="table-section">
        <div class="table-header">
          <div class="table-title">
            <h3>管理员列表</h3>
            <span class="table-count">共 {{ total }} 条记录</span>
          </div>
          <div class="table-actions">
            <el-button size="small" @click="fetchList">
              <el-icon><Refresh /></el-icon>
              刷新
            </el-button>
          </div>
        </div>

        <el-table 
          :data="list" 
          row-key="id" 
          v-loading="loading" 
          class="data-table"
          stripe
          border
        >
          <el-table-column prop="id" label="ID" width="80" align="center">
            <template #default="{ row }">
              <span class="id-badge">{{ row.id }}</span>
            </template>
          </el-table-column>
          
          <el-table-column prop="user_name" label="用户名" min-width="120">
            <template #default="{ row }">
              <div class="user-info">
                <div class="user-avatar">
                  <el-icon><User /></el-icon>
                </div>
                <span class="user-name">{{ row.user_name }}</span>
              </div>
            </template>
          </el-table-column>
          
          <el-table-column prop="email" label="邮箱地址" min-width="180">
            <template #default="{ row }">
              <div class="email-info">
                <el-icon><Message /></el-icon>
                <span>{{ row.email }}</span>
              </div>
            </template>
          </el-table-column>
          
          <el-table-column prop="role_id" label="角色" width="120" align="center">
            <template #default="{ row }">
              <el-tag type="info" size="small">角色 {{ row.role_id }}</el-tag>
            </template>
          </el-table-column>
          
          <el-table-column label="状态" width="100" align="center">
            <template #default="{ row }">
              <el-tag 
                :type="row.status === 1 ? 'success' : 'danger'" 
                size="small"
                effect="light"
              >
                {{ row.status === 1 ? '正常' : '禁用' }}
              </el-tag>
            </template>
          </el-table-column>

          <el-table-column label="创建时间" width="160" align="center">
            <template #default="{ row }">
              <span class="time-text">{{ formatTime(row.ctime) }}</span>
            </template>
          </el-table-column>
          
          <el-table-column label="操作" width="200" fixed="right" align="center">
            <template #default="{ row }">
              <div class="action-buttons">
                <el-tooltip content="编辑" placement="top">
                  <el-button size="small" type="primary" @click="openEdit(row)">
                    <el-icon><Edit /></el-icon>
                  </el-button>
                </el-tooltip>
                <el-tooltip content="重置密码" placement="top">
                  <el-button size="small" type="warning" @click="handleResetPwd(row)">
                    <el-icon><Key /></el-icon>
                  </el-button>
                </el-tooltip>
                <el-tooltip :content="row.status === 1 ? '禁用' : '启用'" placement="top">
                  <el-button 
                    size="small" 
                    :type="row.status === 1 ? 'info' : 'success'" 
                    @click="handleToggle(row)"
                  >
                    <el-icon><Switch /></el-icon>
                  </el-button>
                </el-tooltip>
                <el-tooltip content="删除" placement="top">
                  <el-button size="small" type="danger" @click="handleDelete(row)">
                    <el-icon><Delete /></el-icon>
                  </el-button>
                </el-tooltip>
              </div>
            </template>
          </el-table-column>
        </el-table>

        <!-- 分页 -->
        <div class="pagination-container">
          <el-pagination
            v-model:current-page="query.page"
            v-model:page-size="query.limit"
          :page-sizes="[10,20,50]"
          layout="total, sizes, prev, pager, next, jumper"
          :total="total"
          @current-change="fetchList"
          @size-change="fetchList"
        />
      </div>
    </el-card>

    <!-- 新增/编辑弹窗 -->
    <el-dialog v-model="dialogVisible" :title="dialogTitle" width="520px">
      <el-form :model="form" label-width="100px" ref="formRef">
        <el-form-item label="用户名" prop="user_name">
          <el-input v-model="form.user_name" />
        </el-form-item>
        <el-form-item label="邮箱" prop="email">
          <el-input v-model="form.email" />
        </el-form-item>
        <el-form-item label="密码" v-if="isCreate">
          <el-input type="password" v-model="form.password" />
        </el-form-item>
        <el-form-item label="角色ID">
          <el-input v-model.number="form.role_id" />
        </el-form-item>
        <el-form-item label="状态">
          <el-switch v-model="form.status" :active-value="1" :inactive-value="0" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible=false">取消</el-button>
        <el-button type="primary" @click="handleSubmit">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { 
  Plus, 
  Search, 
  Refresh, 
  User, 
  Message, 
  Edit, 
  Key, 
  Switch, 
  Delete 
} from '@element-plus/icons-vue'
import { getAdminList, createAdmin, updateAdmin, resetAdminPassword, toggleAdminStatus, deleteAdmin } from '@/api/admins'

const loading = ref(false)
const list = ref<any[]>([])
const total = ref(0)

const query = reactive({ page: 1, limit: 10, keyword: '', status: '' as any })

// 格式化时间
const formatTime = (timeStr: string) => {
  if (!timeStr || timeStr === '0000-00-00 00:00:00') return '-'
  return new Date(timeStr).toLocaleString('zh-CN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const dialogVisible = ref(false)
const dialogTitle = ref('')
const isCreate = ref(true)
const formRef = ref()
const form = reactive<any>({ id: 0, user_name: '', email: '', password: '', role_id: 1, status: 1 })

const fetchList = async () => {
  try {
    loading.value = true
    const params: any = { ...query }
    if (params.status === '') delete params.status
    const res = await getAdminList(params)
    list.value = res.data.data
    total.value = res.data.pagination.total
  } finally {
    loading.value = false
  }
}

const resetSearch = () => {
  query.keyword = ''
  query.status = undefined as any
  query.page = 1
  fetchList()
}

const openCreate = () => {
  isCreate.value = true
  dialogTitle.value = '新增管理员'
  Object.assign(form, { id: 0, user_name: '', email: '', password: '', role_id: 1, status: 1 })
  dialogVisible.value = true
}

const openEdit = (row: any) => {
  isCreate.value = false
  dialogTitle.value = '编辑管理员'
  Object.assign(form, { ...row, password: '' })
  dialogVisible.value = true
}

const handleSubmit = async () => {
  try {
    if (isCreate.value) {
      await createAdmin({ user_name: form.user_name, email: form.email, password: form.password, role_id: form.role_id, status: form.status })
      ElMessage.success('创建成功')
    } else {
      await updateAdmin(form.id, { user_name: form.user_name, email: form.email, role_id: form.role_id, status: form.status })
      ElMessage.success('更新成功')
    }
    dialogVisible.value = false
    fetchList()
  } catch (e) {
    ElMessage.error('提交失败')
  }
}

const handleResetPwd = async (row: any) => {
  try {
    const { value } = await ElMessageBox.prompt('输入新密码（至少6位）', '重置密码', { inputType: 'password' })
    await resetAdminPassword(row.id, value)
    ElMessage.success('密码已重置')
  } catch (e) {}
}

const handleToggle = async (row: any) => {
  await toggleAdminStatus(row.id)
  ElMessage.success('已更新状态')
  fetchList()
}

const handleDelete = async (row: any) => {
  try {
    await ElMessageBox.confirm('确定删除该管理员吗？', '提示', { type: 'warning' })
    await deleteAdmin(row.id)
    ElMessage.success('删除成功')
    fetchList()
  } catch (e) {}
}

onMounted(fetchList)
</script>

<style scoped>
.admin-management {
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

.add-btn {
  height: 44px;
  padding: 0 24px;
  font-weight: 600;
  border-radius: 8px;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
}

/* 主卡片 */
.main-card {
  background: white;
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
  border: 1px solid #e2e8f0;
}

/* 搜索区域 */
.search-section {
  margin-bottom: 24px;
  padding-bottom: 20px;
  border-bottom: 1px solid #e2e8f0;
}

.search-form {
  display: grid;
  grid-template-columns: 1fr 200px auto;
  gap: 16px;
  align-items: end;
}

.form-item {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-label {
  font-size: 14px;
  font-weight: 600;
  color: #374151;
}

.search-input, .status-select {
  width: 100%;
}

.form-actions {
  display: flex;
  gap: 8px;
  align-items: end;
}

/* 表格区域 */
.table-section {
  margin-top: 24px;
}

.table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.table-title h3 {
  font-size: 18px;
  font-weight: 600;
  color: #1a202c;
  margin: 0 0 4px 0;
}

.table-count {
  font-size: 14px;
  color: #64748b;
}

.table-actions {
  display: flex;
  gap: 8px;
}

/* 表格样式 */
.data-table {
  border-radius: 12px;
  overflow: hidden;
}

.id-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  background: #f1f5f9;
  color: #475569;
  border-radius: 6px;
  font-weight: 600;
  font-size: 12px;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 8px;
}

.user-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 14px;
}

.user-name {
  font-weight: 600;
  color: #1a202c;
}

.email-info {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #64748b;
  font-size: 14px;
}

.time-text {
  font-size: 12px;
  color: #64748b;
}

.action-buttons {
  display: flex;
  gap: 4px;
  justify-content: center;
}

.action-buttons .el-button {
  width: 32px;
  height: 32px;
  padding: 0;
  border-radius: 6px;
}

/* 分页 */
.pagination-container {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}

/* 响应式设计 */
@media (max-width: 768px) {
  .admin-management {
    padding: 16px;
  }
  
  .header-content {
    flex-direction: column;
    gap: 16px;
    align-items: stretch;
  }
  
  .search-form {
    grid-template-columns: 1fr;
    gap: 12px;
  }
  
  .table-header {
    flex-direction: column;
    gap: 12px;
    align-items: stretch;
  }
  
  .action-buttons {
    flex-wrap: wrap;
  }
}
</style>


