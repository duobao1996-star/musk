<template>
  <div class="permission-management">
    <el-card>
      <template #header>
        <div class="card-header">
          <span>权限管理</span>
          <div>
            <el-button @click="handleRefresh">
              <el-icon><Refresh /></el-icon>
              刷新
            </el-button>
            <el-button type="primary" @click="handleAdd">
              <el-icon><Plus /></el-icon>
              新增权限
            </el-button>
          </div>
        </div>
      </template>

      <!-- 表格 -->
      <el-table 
        v-loading="loading" 
        :data="tableData" 
        style="width: 100%"
        row-key="id"
        :tree-props="{ children: 'children', hasChildren: 'hasChildren' }"
        default-expand-all
      >
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="right_name" label="权限名称" />
        <el-table-column prop="description" label="描述" />
        <el-table-column prop="path" label="路径" />
        <el-table-column prop="method" label="方法" width="100">
          <template #default="{ row }">
            <el-tag :type="getMethodType(row.method)">
              {{ row.method }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="menu" label="类型" width="100">
          <template #default="{ row }">
            <el-tag :type="row.menu === 1 ? 'success' : 'info'">
              {{ row.menu === 1 ? '菜单' : '接口' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="sort" label="排序" width="80" />
        <el-table-column prop="is_del" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="row.is_del === 1 ? 'success' : 'danger'">
              {{ row.is_del === 1 ? '正常' : '已删除' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" size="small" @click="handleEdit(row)">
              编辑
            </el-button>
            <el-button type="danger" size="small" @click="handleDelete(row)">
              删除
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

    <!-- 新增/编辑对话框 -->
    <el-dialog
      v-model="dialogVisible"
      :title="dialogTitle"
      width="600px"
      @close="resetForm"
    >
      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-width="100px"
      >
        <el-form-item label="权限名称" prop="right_name">
          <el-input v-model="form.right_name" placeholder="请输入权限名称" />
        </el-form-item>
        <el-form-item label="描述" prop="description">
          <el-input
            v-model="form.description"
            type="textarea"
            :rows="3"
            placeholder="请输入权限描述"
          />
        </el-form-item>
        <el-form-item label="父级权限" prop="pid">
          <el-select v-model="form.pid" placeholder="请选择父级权限" clearable>
            <el-option label="无（顶级权限）" value="" />
            <el-option
              v-for="item in parentOptions"
              :key="item.id"
              :label="item.description"
              :value="item.id.toString()"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="权限类型" prop="menu">
          <el-radio-group v-model="form.menu">
            <el-radio :value="1">菜单权限</el-radio>
            <el-radio :value="0">接口权限</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="路径" prop="path">
          <el-input v-model="form.path" placeholder="请输入路径，如：/api/users" />
        </el-form-item>
        <el-form-item label="方法" prop="method">
          <el-select v-model="form.method" placeholder="请选择HTTP方法">
            <el-option label="GET" value="GET" />
            <el-option label="POST" value="POST" />
            <el-option label="PUT" value="PUT" />
            <el-option label="DELETE" value="DELETE" />
            <el-option label="PATCH" value="PATCH" />
          </el-select>
        </el-form-item>
        <el-form-item label="图标" prop="icon">
          <el-input v-model="form.icon" placeholder="请输入图标名称，如：ri:user-line" />
        </el-form-item>
        <el-form-item label="排序" prop="sort">
          <el-input-number v-model="form.sort" :min="0" :max="999" />
        </el-form-item>
      </el-form>
      
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus, Refresh } from '@element-plus/icons-vue'
import {
  getPermissionList,
  createPermission,
  updatePermission,
  deletePermission,
  getPermissionTree,
  type Permission
} from '@/api/permission'

const loading = ref(false)
const tableData = ref<Permission[]>([])
const parentOptions = ref<Permission[]>([])
const dialogVisible = ref(false)
const dialogTitle = ref('')
const formRef = ref()

const pagination = reactive({
  page: 1,
  limit: 10,
  total: 0
})

const form = reactive({
  id: 0,
  right_name: '',
  description: '',
  pid: '',
  menu: 1,
  path: '',
  method: 'GET',
  icon: '',
  sort: 0
})

const rules = {
  right_name: [
    { required: true, message: '请输入权限名称', trigger: 'blur' }
  ],
  description: [
    { required: true, message: '请输入权限描述', trigger: 'blur' }
  ]
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

// 获取列表数据
const getList = async () => {
  try {
    loading.value = true
    const response = await getPermissionList({
      page: pagination.page,
      limit: pagination.limit
    })
    
    // 构建树形结构
    tableData.value = buildTree(response.data.data)
    pagination.total = response.data.pagination.total
  } catch (error) {
    ElMessage.error('获取列表失败')
  } finally {
    loading.value = false
  }
}

// 构建树形结构
const buildTree = (data: Permission[]) => {
  const map = new Map()
  const roots: Permission[] = []
  
  // 创建映射
  data.forEach(item => {
    map.set(item.id, { ...item, children: [] })
  })
  
  // 构建树
  data.forEach(item => {
    const node = map.get(item.id)
    if (item.pid && map.has(parseInt(item.pid))) {
      map.get(parseInt(item.pid)).children.push(node)
    } else {
      roots.push(node)
    }
  })
  
  return roots
}

// 获取父级选项
const getParentOptions = async () => {
  try {
    const response = await getPermissionTree()
    parentOptions.value = response.data.data
  } catch (error) {
    console.error('获取父级选项失败:', error)
  }
}

// 刷新
const handleRefresh = () => {
  getList()
  getParentOptions()
}

// 新增
const handleAdd = () => {
  dialogTitle.value = '新增权限'
  dialogVisible.value = true
  resetForm()
}

// 编辑
const handleEdit = (row: Permission) => {
  dialogTitle.value = '编辑权限'
  dialogVisible.value = true
  Object.assign(form, row)
}

// 删除
const handleDelete = async (row: Permission) => {
  try {
    await ElMessageBox.confirm('确定要删除这个权限吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })
    
    await deletePermission(row.id)
    ElMessage.success('删除成功')
    getList()
  } catch (error: any) {
    if (error !== 'cancel') {
      ElMessage.error('删除失败')
    }
  }
}

// 提交表单
const handleSubmit = async () => {
  if (!formRef.value) return
  
  try {
    const valid = await formRef.value.validate()
    if (!valid) return
    
    if (form.id) {
      await updatePermission(form.id, form)
      ElMessage.success('更新成功')
    } else {
      await createPermission(form)
      ElMessage.success('创建成功')
    }
    
    dialogVisible.value = false
    getList()
    getParentOptions()
  } catch (error) {
    ElMessage.error('操作失败')
  }
}

// 重置表单
const resetForm = () => {
  Object.assign(form, {
    id: 0,
    right_name: '',
    description: '',
    pid: '',
    menu: 1,
    path: '',
    method: 'GET',
    icon: '',
    sort: 0
  })
  if (formRef.value) {
    formRef.value.resetFields()
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
  getParentOptions()
})
</script>

<style scoped>
.permission-management {
  padding: 20px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 16px;
  font-weight: 600;
}

.pagination-container {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}
</style>
