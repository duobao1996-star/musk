<template>
  <div class="role-management">
    <el-card>
      <template #header>
        <div class="card-header">
          <span>角色管理</span>
          <el-button type="primary" @click="handleAdd">
            <el-icon><Plus /></el-icon>
            新增角色
          </el-button>
        </div>
      </template>

      <!-- 表格 -->
      <el-table 
        v-loading="loading" 
        :data="tableData" 
        style="width: 100%"
        row-key="id"
      >
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="role_name" label="角色名称" />
        <el-table-column prop="order_no" label="排序" width="100" />
        <el-table-column prop="description" label="描述" />
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
            <el-button type="success" size="small" @click="handlePermission(row)">
              权限
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
      width="500px"
      @close="resetForm"
    >
      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-width="100px"
      >
        <el-form-item label="角色名称" prop="role_name">
          <el-input v-model="form.role_name" placeholder="请输入角色名称" />
        </el-form-item>
        <el-form-item label="排序" prop="order_no">
          <el-input-number v-model="form.order_no" :min="1" :max="999" />
        </el-form-item>
        <el-form-item label="描述" prop="description">
          <el-input
            v-model="form.description"
            type="textarea"
            :rows="3"
            placeholder="请输入角色描述"
          />
        </el-form-item>
      </el-form>
      
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit">确定</el-button>
      </template>
    </el-dialog>

    <!-- 权限设置对话框 -->
    <el-dialog
      v-model="permissionDialogVisible"
      title="设置权限"
      width="600px"
    >
      <el-tree
        ref="treeRef"
        :data="permissionTree"
        :props="treeProps"
        show-checkbox
        node-key="id"
        :default-checked-keys="checkedKeys"
        check-strictly
      />
      
      <template #footer>
        <el-button @click="permissionDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSavePermission">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, nextTick } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import {
  getRoleList,
  createRole,
  updateRole,
  deleteRole,
  getRoleRights,
  setRoleRights,
  getAllRightsTree,
  type Role
} from '@/api/role'

const loading = ref(false)
const tableData = ref<Role[]>([])
const dialogVisible = ref(false)
const permissionDialogVisible = ref(false)
const dialogTitle = ref('')
const formRef = ref()
const treeRef = ref()

const pagination = reactive({
  page: 1,
  limit: 10,
  total: 0
})

const form = reactive({
  id: 0,
  role_name: '',
  order_no: 1,
  description: ''
})

const rules = {
  role_name: [
    { required: true, message: '请输入角色名称', trigger: 'blur' }
  ],
  order_no: [
    { required: true, message: '请输入排序', trigger: 'blur' }
  ]
}

const permissionTree = ref([])
const checkedKeys = ref<number[]>([])
const currentRoleId = ref(0)

const treeProps = {
  children: 'children',
  label: 'description'
}

// 获取列表数据
const getList = async () => {
  try {
    loading.value = true
    const response = await getRoleList({
      page: pagination.page,
      limit: pagination.limit
    })
    
    tableData.value = response.data.data
    pagination.total = response.data.pagination.total
  } catch (error) {
    ElMessage.error('获取列表失败')
  } finally {
    loading.value = false
  }
}

// 新增
const handleAdd = () => {
  dialogTitle.value = '新增角色'
  dialogVisible.value = true
  resetForm()
}

// 编辑
const handleEdit = (row: Role) => {
  dialogTitle.value = '编辑角色'
  dialogVisible.value = true
  Object.assign(form, row)
}

// 删除
const handleDelete = async (row: Role) => {
  try {
    await ElMessageBox.confirm('确定要删除这个角色吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })
    
    await deleteRole(row.id)
    ElMessage.success('删除成功')
    getList()
  } catch (error: any) {
    if (error !== 'cancel') {
      ElMessage.error('删除失败')
    }
  }
}

// 权限设置
const handlePermission = async (row: Role) => {
  try {
    currentRoleId.value = row.id
    permissionDialogVisible.value = true
    
    // 获取权限树
    const treeResponse = await getAllRightsTree()
    permissionTree.value = treeResponse.data
    
    // 获取当前角色权限
    const rightsResponse = await getRoleRights(row.id)
    checkedKeys.value = rightsResponse.data.map((item: any) => item.id)
    
    await nextTick()
    if (treeRef.value) {
      treeRef.value.setCheckedKeys(checkedKeys.value)
    }
  } catch (error) {
    ElMessage.error('获取权限数据失败')
  }
}

// 保存权限
const handleSavePermission = async () => {
  try {
    const checkedNodes = treeRef.value?.getCheckedNodes() || []
    const rightIds = checkedNodes.map((node: any) => node.id)
    
    await setRoleRights(currentRoleId.value, rightIds)
    ElMessage.success('权限设置成功')
    permissionDialogVisible.value = false
  } catch (error) {
    ElMessage.error('权限设置失败')
  }
}

// 提交表单
const handleSubmit = async () => {
  if (!formRef.value) return
  
  try {
    const valid = await formRef.value.validate()
    if (!valid) return
    
    if (form.id) {
      await updateRole(form.id, form)
      ElMessage.success('更新成功')
    } else {
      await createRole(form)
      ElMessage.success('创建成功')
    }
    
    dialogVisible.value = false
    getList()
  } catch (error) {
    ElMessage.error('操作失败')
  }
}

// 重置表单
const resetForm = () => {
  Object.assign(form, {
    id: 0,
    role_name: '',
    order_no: 1,
    description: ''
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
})
</script>

<style scoped>
.role-management {
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
