<template>
  <div class="role-permission-panel">
    <!-- 操作栏 -->
    <div class="panel-header">
      <div class="header-left">
        <h3>角色权限分配</h3>
        <p>为角色分配权限，支持批量操作和权限继承</p>
      </div>
      <div class="header-right">
        <el-button type="primary" @click="handleSaveAll">
          <el-icon><Check /></el-icon>
          保存全部
        </el-button>
        <el-button @click="handleRefresh">
          <el-icon><Refresh /></el-icon>
          刷新
        </el-button>
      </div>
    </div>

    <!-- 角色权限分配界面 -->
    <div class="role-permission-container">
      <!-- 左侧：角色列表 -->
      <div class="role-list">
        <div class="section-title">
          <h4>角色列表</h4>
          <el-button size="small" type="primary" @click="handleAddRole">
            <el-icon><Plus /></el-icon>
            添加角色
          </el-button>
        </div>
        <div class="role-items">
          <div
            v-for="role in roles"
            :key="role.id"
            :class="['role-item', { active: selectedRole?.id === role.id }]"
            @click="handleSelectRole(role)"
          >
            <div class="role-info">
              <div class="role-name">{{ role.name }}</div>
              <div class="role-desc">{{ role.description }}</div>
            </div>
            <div class="role-actions">
              <el-button size="small" type="primary" @click.stop="handleEditRole(role)">
                编辑
              </el-button>
            </div>
          </div>
        </div>
      </div>

      <!-- 右侧：权限树 -->
      <div class="permission-tree">
        <div class="section-title">
          <h4>权限分配</h4>
          <div class="tree-actions">
            <el-button size="small" @click="handleExpandAll">
              <el-icon><Plus /></el-icon>
              展开全部
            </el-button>
            <el-button size="small" @click="handleCollapseAll">
              <el-icon><Minus /></el-icon>
              收起全部
            </el-button>
            <el-button size="small" @click="handleSelectAll">
              <el-icon><Check /></el-icon>
              全选
            </el-button>
            <el-button size="small" @click="handleUnselectAll">
              <el-icon><Close /></el-icon>
              取消全选
            </el-button>
          </div>
        </div>
        
        <div class="tree-content">
          <el-tree
            ref="treeRef"
            :data="permissionTree"
            :props="treeProps"
            show-checkbox
            node-key="id"
            :default-checked-keys="checkedKeys"
            :default-expanded-keys="expandedKeys"
            :check-strictly="false"
            @check="handleCheck"
            class="permission-tree"
          >
            <template #default="{ node, data }">
              <div class="tree-node">
                <div class="node-content">
                  <el-icon v-if="data.icon" class="node-icon">
                    <component :is="getIcon(data.icon)" />
                  </el-icon>
                  <div class="node-info">
                    <div class="node-title">{{ data.title || data.right_name }}</div>
                    <div class="node-desc">{{ data.description }}</div>
                  </div>
                </div>
                <div class="node-tags">
                  <el-tag v-if="data.path" size="small" type="info">{{ data.path }}</el-tag>
                  <el-tag v-if="data.method" size="small" :type="getMethodType(data.method)">
                    {{ data.method }}
                  </el-tag>
                  <el-tag size="small" :type="data.is_menu ? 'success' : 'primary'">
                    {{ data.is_menu ? '菜单' : 'API' }}
                  </el-tag>
                </div>
              </div>
            </template>
          </el-tree>
        </div>
      </div>
    </div>

    <!-- 权限冲突检测 -->
    <div v-if="permissionConflicts.length > 0" class="conflict-warning">
      <el-alert
        title="权限冲突检测"
        type="warning"
        :closable="false"
        show-icon
      >
        <template #default>
          <div class="conflict-list">
            <div v-for="conflict in permissionConflicts" :key="conflict.id" class="conflict-item">
              <el-icon><Warning /></el-icon>
              <span>{{ conflict.message }}</span>
            </div>
          </div>
        </template>
      </el-alert>
    </div>

    <!-- 添加/编辑角色对话框 -->
    <el-dialog
      v-model="roleDialogVisible"
      :title="roleDialogTitle"
      width="500px"
      :before-close="handleRoleDialogClose"
    >
      <el-form
        ref="roleFormRef"
        :model="roleForm"
        :rules="roleRules"
        label-width="80px"
      >
        <el-form-item label="角色名称" prop="name">
          <el-input v-model="roleForm.name" placeholder="请输入角色名称" />
        </el-form-item>
        
        <el-form-item label="角色描述" prop="description">
          <el-input 
            v-model="roleForm.description" 
            type="textarea" 
            :rows="3"
            placeholder="请输入角色描述" 
          />
        </el-form-item>
        
        <el-form-item label="排序" prop="sort">
          <el-input-number v-model="roleForm.sort" :min="0" :max="999" />
        </el-form-item>
        
        <el-form-item label="状态">
          <el-switch v-model="roleForm.status" active-value="1" inactive-value="0" />
          <span class="form-tip">启用后该角色将生效</span>
        </el-form-item>
      </el-form>
      
      <template #footer>
        <el-button @click="handleRoleDialogClose">取消</el-button>
        <el-button type="primary" @click="handleRoleSubmit">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { 
  Check, Refresh, Plus, Minus, Close, Warning 
} from '@element-plus/icons-vue'
import { 
  getRoleList, 
  createRole, 
  updateRole, 
  deleteRole,
  getRoleRights,
  setRoleRights
} from '@/api/role'
import { getPermissionList } from '@/api/permission'

// 响应式数据
const treeRef = ref()
const roleDialogVisible = ref(false)
const roleDialogTitle = ref('')
const roles = ref([])
const selectedRole = ref(null)
const permissionTree = ref([])
const checkedKeys = ref([])
const expandedKeys = ref([])
const permissionConflicts = ref([])

// 角色表单
const roleForm = reactive({
  id: null,
  name: '',
  description: '',
  sort: 0,
  status: 1
})

// 角色表单验证规则
const roleRules = {
  name: [{ required: true, message: '请输入角色名称', trigger: 'blur' }],
  description: [{ required: true, message: '请输入角色描述', trigger: 'blur' }]
}

// 树形组件配置
const treeProps = {
  children: 'children',
  label: 'title'
}

// 图标映射
const getIcon = (iconName: string) => {
  const iconMap = {
    'ri:dashboard-line': 'Odometer',
    'ri:settings-line': 'Setting',
    'ri:line-chart-line': 'TrendCharts',
    'ri:admin-line': 'Avatar',
    'ri:file-list-line': 'Document',
    'ri:key-line': 'Key',
    'ri:user-settings-line': 'User',
    'ri:user-line': 'Avatar'
  }
  return iconMap[iconName] || 'Document'
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

// 获取角色数据
const getRoles = async () => {
  try {
    const response = await getRoleList({ page: 1, limit: 1000 })
    roles.value = response.data.data || []
  } catch (error) {
    ElMessage.error('获取角色数据失败')
  }
}

// 获取权限数据
const getPermissions = async () => {
  try {
    const response = await getPermissionList({ page: 1, limit: 1000 })
    const permissions = response.data.data || []
    
    // 只显示菜单权限
    const menuPermissions = permissions.filter(p => p.is_menu === 1)
    permissionTree.value = buildPermissionTree(menuPermissions)
    expandedKeys.value = menuPermissions.map(p => p.id)
  } catch (error) {
    ElMessage.error('获取权限数据失败')
  }
}

// 构建权限树
const buildPermissionTree = (permissions: any[]) => {
  const tree = []
  const nodeMap = new Map()
  
  // 创建节点映射
  permissions.forEach(permission => {
    nodeMap.set(permission.id, {
      ...permission,
      title: permission.description,
      children: []
    })
  })
  
  // 构建树形结构
  permissions.forEach(permission => {
    const node = nodeMap.get(permission.id)
    if (permission.pid === 0 || !permission.pid) {
      tree.push(node)
    } else {
      const parent = nodeMap.get(permission.pid)
      if (parent) {
        parent.children.push(node)
      }
    }
  })
  
  return tree.sort((a, b) => a.sort - b.sort)
}

// 获取角色权限
const getRolePermissions = async (roleId: number) => {
  try {
    const response = await getRoleRights(roleId)
    checkedKeys.value = response.data.data || []
    
    // 检测权限冲突
    detectPermissionConflicts()
  } catch (error) {
    ElMessage.error('获取角色权限失败')
  }
}

// 检测权限冲突
const detectPermissionConflicts = () => {
  // 这里可以实现权限冲突检测逻辑
  // 例如：检测是否同时拥有删除和只读权限等
  permissionConflicts.value = []
}

// 操作方法
const handleSelectRole = (role: any) => {
  selectedRole.value = role
  getRolePermissions(role.id)
}

const handleAddRole = () => {
  roleDialogTitle.value = '添加角色'
  resetRoleForm()
  roleDialogVisible.value = true
}

const handleEditRole = (role: any) => {
  roleDialogTitle.value = '编辑角色'
  Object.assign(roleForm, role)
  roleDialogVisible.value = true
}

const handleRoleSubmit = async () => {
  try {
    if (roleForm.id) {
      await updateRole(roleForm.id, roleForm)
      ElMessage.success('更新成功')
    } else {
      await createRole(roleForm)
      ElMessage.success('添加成功')
    }
    roleDialogVisible.value = false
    getRoles()
  } catch (error) {
    ElMessage.error('操作失败')
  }
}

const handleRoleDialogClose = () => {
  roleDialogVisible.value = false
  resetRoleForm()
}

const resetRoleForm = () => {
  Object.assign(roleForm, {
    id: null,
    name: '',
    description: '',
    sort: 0,
    status: 1
  })
}

const handleExpandAll = () => {
  expandedKeys.value = permissionTree.value.map((node: any) => getAllNodeIds(node)).flat()
}

const handleCollapseAll = () => {
  expandedKeys.value = []
}

const handleSelectAll = () => {
  const allKeys = permissionTree.value.map((node: any) => getAllNodeIds(node)).flat()
  treeRef.value?.setCheckedKeys(allKeys)
}

const handleUnselectAll = () => {
  treeRef.value?.setCheckedKeys([])
}

const handleCheck = (data: any, checkedInfo: any) => {
  // 处理权限选择变化
  detectPermissionConflicts()
}

const handleSaveAll = async () => {
  if (!selectedRole.value) {
    ElMessage.warning('请先选择角色')
    return
  }
  
  try {
    const checkedKeysFull = treeRef.value?.getCheckedKeys(false) || []
    const halfCheckedKeys = treeRef.value?.getHalfCheckedKeys() || []
    const rightIds = [...new Set([...checkedKeysFull, ...halfCheckedKeys])]
    
    await setRoleRights(selectedRole.value.id, rightIds)
    ElMessage.success('权限分配成功')
  } catch (error) {
    ElMessage.error('权限分配失败')
  }
}

const handleRefresh = () => {
  getRoles()
  getPermissions()
  if (selectedRole.value) {
    getRolePermissions(selectedRole.value.id)
  }
}

// 获取所有节点ID
const getAllNodeIds = (node: any): number[] => {
  const ids = [node.id]
  if (node.children) {
    node.children.forEach((child: any) => {
      ids.push(...getAllNodeIds(child))
    })
  }
  return ids
}

// 监听选中的角色变化
watch(selectedRole, (newRole) => {
  if (newRole) {
    getRolePermissions(newRole.id)
  }
})

// 初始化
onMounted(() => {
  getRoles()
  getPermissions()
})
</script>

<style scoped>
.role-permission-panel {
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

.role-permission-container {
  display: flex;
  gap: 20px;
  height: 600px;
}

.role-list {
  width: 300px;
  background: white;
  border-radius: 8px;
  border: 1px solid #e4e7ed;
  padding: 16px;
}

.section-title {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 1px solid #e4e7ed;
}

.section-title h4 {
  margin: 0;
  color: #303133;
  font-size: 16px;
  font-weight: 600;
}

.role-items {
  max-height: 500px;
  overflow-y: auto;
}

.role-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  border: 1px solid #e4e7ed;
  border-radius: 6px;
  margin-bottom: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.role-item:hover {
  border-color: #409eff;
  background: #f0f9ff;
}

.role-item.active {
  border-color: #409eff;
  background: #e6f7ff;
}

.role-info {
  flex: 1;
}

.role-name {
  font-weight: 500;
  color: #303133;
  margin-bottom: 4px;
}

.role-desc {
  font-size: 12px;
  color: #909399;
}

.role-actions {
  opacity: 0;
  transition: opacity 0.2s;
}

.role-item:hover .role-actions {
  opacity: 1;
}

.permission-tree {
  flex: 1;
  background: white;
  border-radius: 8px;
  border: 1px solid #e4e7ed;
  padding: 16px;
  display: flex;
  flex-direction: column;
}

.tree-actions {
  display: flex;
  gap: 8px;
}

.tree-content {
  flex: 1;
  overflow-y: auto;
}

.permission-tree {
  min-height: 400px;
}

.tree-node {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  padding: 4px 0;
}

.node-content {
  display: flex;
  align-items: center;
  flex: 1;
}

.node-icon {
  margin-right: 8px;
  color: #606266;
}

.node-info {
  flex: 1;
}

.node-title {
  font-weight: 500;
  color: #303133;
  margin-bottom: 2px;
}

.node-desc {
  font-size: 12px;
  color: #909399;
}

.node-tags {
  display: flex;
  gap: 4px;
  flex-wrap: wrap;
}

.conflict-warning {
  margin-top: 20px;
}

.conflict-list {
  margin-top: 8px;
}

.conflict-item {
  display: flex;
  align-items: center;
  margin-bottom: 4px;
}

.conflict-item .el-icon {
  margin-right: 8px;
  color: #e6a23c;
}

.form-tip {
  margin-left: 8px;
  color: #909399;
  font-size: 12px;
}
</style>
