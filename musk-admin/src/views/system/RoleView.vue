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
        default-expand-all
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
import { useMenuStore } from '@/stores/menu'
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

// 过滤出用户明确选择的权限（排除自动添加的父级权限）
const filterUserSelectedPermissions = (allRightIds: number[], permissionTree: any[]): number[] => {
  const userSelectedIds: number[] = []
  
  // 递归遍历权限树，找出用户明确选择的权限
  const traverseTree = (nodes: any[]) => {
    nodes.forEach(node => {
      if (allRightIds.includes(node.id)) {
        // 如果当前节点在权限列表中
        const hasChildren = node.children && node.children.length > 0
        
        if (hasChildren) {
          // 如果有子权限，检查子权限的选中情况
          const selectedChildrenCount = node.children.filter((child: any) => allRightIds.includes(child.id)).length
          
          if (selectedChildrenCount === 0) {
            // 如果子权限都没有被选中，说明用户明确选择了父权限
            userSelectedIds.push(node.id)
          } else if (selectedChildrenCount === node.children.length) {
            // 如果所有子权限都被选中，说明父权限是自动添加的，不标记为用户选择
            // 继续遍历子节点
            traverseTree(node.children)
          } else {
            // 如果部分子权限被选中，说明父权限是自动添加的，但需要标记选中的子权限
            traverseTree(node.children)
          }
        } else {
          // 如果没有子权限，则标记为用户选择的权限
          userSelectedIds.push(node.id)
        }
      } else if (node.children) {
        // 如果当前节点不在权限列表中，继续遍历子节点
        traverseTree(node.children)
      }
    })
  }
  
  traverseTree(permissionTree)
  return userSelectedIds
}

// 权限设置
const handlePermission = async (row: Role) => {
  try {
    currentRoleId.value = row.id
    permissionDialogVisible.value = true
    
    // 获取权限树
    const treeResponse = await getAllRightsTree()
    const treeData = (treeResponse as any)?.data?.data ?? (treeResponse as any)?.data ?? []
    const list = Array.isArray(treeData) ? treeData : []
    // 如果没有 children 字段，尝试由父子关系构建树
    const hasChildren = list.some((n: any) => Array.isArray(n.children) && n.children.length)
    if (hasChildren) {
      permissionTree.value = list
    } else {
      // 动态识别父字段
      const candidateKeys = ['parent_id','pid','parentId','parent','p_id']
      let parentKey = 'parent_id'
      for (const k of candidateKeys) {
        if (list.some((it: any) => k in it)) { parentKey = k; break }
      }
      const map: Record<string, any> = {}
      list.forEach((item: any) => {
        const id = String(item.id)
        map[id] = { ...item, id, children: [] }
      })
      const roots: any[] = []
      list.forEach((item: any) => {
        const pidRaw = item[parentKey]
        const pid = pidRaw == null ? '' : String(pidRaw)
        const node = map[String(item.id)]
        if (pid && map[pid]) {
          map[pid].children.push(node)
        } else {
          roots.push(node)
        }
      })
      // 若仍全部为根且没有层级，按 path 的模块名分组构造树
      const noHierarchy = roots.length === list.length && !roots.some((n: any) => n.children && n.children.length)
      if (noHierarchy) {
        const moduleBuckets: Record<string, any[]> = {}
        roots.forEach((n: any) => {
          const path: string = n.path || n.request_path || n.url || ''
          // 模块名：/api/<module>/...
          const m = (path.match(/^\/?api\/([^\/]+)/) || [])[1] || '其它'
          if (!moduleBuckets[m]) moduleBuckets[m] = []
          moduleBuckets[m].push(n)
        })
        const grouped: any[] = Object.keys(moduleBuckets).map((m) => ({
          id: `module:${m}`,
          description: m,
          children: moduleBuckets[m]
        }))
        permissionTree.value = grouped
      } else {
        permissionTree.value = roots
      }
    }
    
    // 获取当前角色权限
    const rightsResponse = await getRoleRights(row.id)
    const rightsList = (rightsResponse as any)?.data?.data ?? (rightsResponse as any)?.data ?? []
    const allRightIds = Array.isArray(rightsList) ? rightsList.map((item: any) => item.id) : []
    
    // 过滤出用户明确选择的权限（排除自动添加的父级权限）
    const userSelectedIds = filterUserSelectedPermissions(allRightIds, permissionTree.value)
    checkedKeys.value = userSelectedIds
    
    await nextTick()
    if (treeRef.value) {
      // 只设置用户明确选择的权限为选中状态
      treeRef.value.setCheckedKeys(checkedKeys.value, false)
    }
  } catch (error) {
    ElMessage.error('获取权限数据失败')
  }
}

// 保存权限
const handleSavePermission = async () => {
  try {
    // 只获取实际选中的权限，不包含半选中的父节点
    const checkedKeysAll = treeRef.value?.getCheckedKeys(false) || []
    const rightIds = Array.from(new Set(checkedKeysAll))
    
    
    await setRoleRights(currentRoleId.value, rightIds)
    ElMessage.success('权限设置成功')
    permissionDialogVisible.value = false
    
    // 刷新菜单状态，确保权限变更立即生效
    const menuStore = useMenuStore()
    await menuStore.getMenuList()
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
