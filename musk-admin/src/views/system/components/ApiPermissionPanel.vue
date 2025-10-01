<template>
  <div class="api-permission-panel">
    <!-- 操作栏 -->
    <div class="panel-header">
      <div class="header-left">
        <h3>API权限管理</h3>
        <p>管理API接口的访问权限，控制接口调用权限</p>
      </div>
      <div class="header-right">
        <el-button type="primary" @click="handleAdd">
          <el-icon><Plus /></el-icon>
          添加API权限
        </el-button>
        <el-button @click="handleBatchImport">
          <el-icon><Upload /></el-icon>
          批量导入
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
        <el-form-item label="模块">
          <el-select v-model="searchForm.module" placeholder="选择模块" clearable>
            <el-option label="全部" value="" />
            <el-option label="认证模块" value="auth" />
            <el-option label="用户管理" value="user" />
            <el-option label="角色管理" value="role" />
            <el-option label="权限管理" value="permission" />
          </el-select>
        </el-form-item>
        
        <el-form-item label="请求方法">
          <el-select v-model="searchForm.method" placeholder="选择方法" clearable>
            <el-option label="全部" value="" />
            <el-option label="GET" value="GET" />
            <el-option label="POST" value="POST" />
            <el-option label="PUT" value="PUT" />
            <el-option label="DELETE" value="DELETE" />
          </el-select>
        </el-form-item>
        
        <el-form-item label="关键词">
          <el-input 
            v-model="searchForm.keyword" 
            placeholder="搜索权限名称、路径或描述"
            clearable
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

    <!-- API权限表格 -->
    <div class="api-table-container">
      <el-table
        :data="filteredApiPermissions"
        style="width: 100%"
        row-key="id"
        :tree-props="{ children: 'children', hasChildren: 'hasChildren' }"
        default-expand-all
      >
        <el-table-column prop="id" label="ID" width="80" />
        
        <el-table-column prop="right_name" label="权限名称" width="200">
          <template #default="{ row }">
            <span class="permission-name">{{ row.right_name }}</span>
          </template>
        </el-table-column>
        
        <el-table-column prop="description" label="描述" min-width="150" />
        
        <el-table-column prop="path" label="API路径" min-width="200">
          <template #default="{ row }">
            <el-text type="info" class="api-path">{{ row.path }}</el-text>
          </template>
        </el-table-column>
        
        <el-table-column prop="method" label="请求方法" width="100">
          <template #default="{ row }">
            <el-tag :type="getMethodType(row.method)" size="small">
              {{ row.method }}
            </el-tag>
          </template>
        </el-table-column>
        
        <el-table-column prop="sort" label="排序" width="80" />
        
        <el-table-column prop="is_del" label="状态" width="80">
          <template #default="{ row }">
            <el-tag :type="row.is_del ? 'success' : 'danger'" size="small">
              {{ row.is_del ? '启用' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="{ row }">
            <el-button size="small" type="primary" @click="handleEdit(row)">
              编辑
            </el-button>
            <el-button size="small" type="danger" @click="handleDelete(row)">
              删除
            </el-button>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <!-- 添加/编辑API权限对话框 -->
    <el-dialog
      v-model="dialogVisible"
      :title="dialogTitle"
      width="600px"
      :before-close="handleDialogClose"
    >
      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-width="100px"
      >
        <el-form-item label="权限名称" prop="right_name">
          <el-input v-model="form.right_name" placeholder="请输入权限名称，如：api.user.list" />
        </el-form-item>
        
        <el-form-item label="权限描述" prop="description">
          <el-input v-model="form.description" placeholder="请输入权限描述" />
        </el-form-item>
        
        <el-form-item label="API路径" prop="path">
          <el-input v-model="form.path" placeholder="请输入API路径，如：/api/users" />
        </el-form-item>
        
        <el-form-item label="请求方法" prop="method">
          <el-select v-model="form.method" placeholder="选择请求方法">
            <el-option label="GET" value="GET" />
            <el-option label="POST" value="POST" />
            <el-option label="PUT" value="PUT" />
            <el-option label="DELETE" value="DELETE" />
            <el-option label="PATCH" value="PATCH" />
          </el-select>
        </el-form-item>
        
        <el-form-item label="父级权限" prop="pid">
          <el-tree-select
            v-model="form.pid"
            :data="apiPermissionOptions"
            :props="{ label: 'right_name', value: 'id' }"
            placeholder="选择父级权限"
            clearable
            check-strictly
          />
        </el-form-item>
        
        <el-form-item label="排序" prop="sort">
          <el-input-number v-model="form.sort" :min="0" :max="999" />
        </el-form-item>
        
        <el-form-item label="状态">
          <el-switch v-model="form.is_del" active-value="1" inactive-value="0" />
          <span class="form-tip">启用后该权限将生效</span>
        </el-form-item>
      </el-form>
      
      <template #footer>
        <el-button @click="handleDialogClose">取消</el-button>
        <el-button type="primary" @click="handleSubmit">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus, Upload, Refresh, Search, RefreshLeft } from '@element-plus/icons-vue'
import { getPermissionList, createPermission, updatePermission, deletePermission } from '@/api/permission'

// 响应式数据
const dialogVisible = ref(false)
const dialogTitle = ref('')
const apiPermissions = ref([])
const apiPermissionOptions = ref([])

// 搜索表单
const searchForm = reactive({
  module: '',
  method: '',
  keyword: ''
})

// 表单数据
const form = reactive({
  id: null,
  right_name: '',
  description: '',
  path: '',
  method: 'GET',
  pid: null,
  sort: 0,
  is_del: 1
})

// 表单验证规则
const rules = {
  right_name: [{ required: true, message: '请输入权限名称', trigger: 'blur' }],
  description: [{ required: true, message: '请输入权限描述', trigger: 'blur' }],
  path: [{ required: true, message: '请输入API路径', trigger: 'blur' }],
  method: [{ required: true, message: '请选择请求方法', trigger: 'change' }]
}

// 计算属性：过滤后的API权限
const filteredApiPermissions = computed(() => {
  let filtered = apiPermissions.value.filter(item => item.is_menu === 0)
  
  if (searchForm.module) {
    filtered = filtered.filter(item => 
      item.right_name.includes(searchForm.module) || 
      item.path.includes(searchForm.module)
    )
  }
  
  if (searchForm.method) {
    filtered = filtered.filter(item => item.method === searchForm.method)
  }
  
  if (searchForm.keyword) {
    const keyword = searchForm.keyword.toLowerCase()
    filtered = filtered.filter(item => 
      item.right_name.toLowerCase().includes(keyword) ||
      item.description.toLowerCase().includes(keyword) ||
      item.path.toLowerCase().includes(keyword)
    )
  }
  
  return filtered
})

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

// 获取API权限数据
const getApiPermissions = async () => {
  try {
    const response = await getPermissionList({ page: 1, limit: 1000 })
    apiPermissions.value = response.data.data || []
    buildApiPermissionOptions()
  } catch (error) {
    ElMessage.error('获取API权限数据失败')
  }
}

// 构建API权限选项
const buildApiPermissionOptions = () => {
  const options = []
  const buildOptions = (nodes: any[], level = 0) => {
    nodes.forEach(node => {
      if (node.is_menu === 0) {
        options.push({
          id: node.id,
          right_name: '　'.repeat(level) + node.right_name,
          pid: node.pid
        })
      }
      if (node.children && node.children.length > 0) {
        buildOptions(node.children, level + 1)
      }
    })
  }
  buildOptions(apiPermissions.value)
  apiPermissionOptions.value = options
}

// 操作方法
const handleAdd = () => {
  dialogTitle.value = '添加API权限'
  resetForm()
  dialogVisible.value = true
}

const handleEdit = (data: any) => {
  dialogTitle.value = '编辑API权限'
  Object.assign(form, data)
  dialogVisible.value = true
}

const handleDelete = async (data: any) => {
  try {
    await ElMessageBox.confirm('确定要删除这个API权限吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })
    
    await deletePermission(data.id)
    ElMessage.success('删除成功')
    getApiPermissions()
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('删除失败')
    }
  }
}

const handleBatchImport = () => {
  ElMessage.info('批量导入功能开发中...')
}

const handleSearch = () => {
  // 搜索逻辑已在计算属性中实现
}

const handleReset = () => {
  Object.assign(searchForm, {
    module: '',
    method: '',
    keyword: ''
  })
}

const handleRefresh = () => {
  getApiPermissions()
}

const handleSubmit = async () => {
  try {
    if (form.id) {
      await updatePermission(form.id, form)
      ElMessage.success('更新成功')
    } else {
      await createPermission(form)
      ElMessage.success('添加成功')
    }
    dialogVisible.value = false
    getApiPermissions()
  } catch (error) {
    ElMessage.error('操作失败')
  }
}

const handleDialogClose = () => {
  dialogVisible.value = false
  resetForm()
}

const resetForm = () => {
  Object.assign(form, {
    id: null,
    right_name: '',
    description: '',
    path: '',
    method: 'GET',
    pid: null,
    sort: 0,
    is_del: 1
  })
}

// 初始化
onMounted(() => {
  getApiPermissions()
})
</script>

<style scoped>
.api-permission-panel {
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

.api-table-container {
  background: white;
  border-radius: 8px;
  border: 1px solid #e4e7ed;
  padding: 16px;
}

.permission-name {
  font-family: monospace;
  font-size: 12px;
  color: #606266;
}

.api-path {
  font-family: monospace;
  font-size: 12px;
}

.form-tip {
  margin-left: 8px;
  color: #909399;
  font-size: 12px;
}
</style>
