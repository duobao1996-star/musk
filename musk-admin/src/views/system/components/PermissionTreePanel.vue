<template>
  <div class="permission-tree-panel">
    <!-- 操作栏 -->
    <div class="panel-header">
      <div class="header-left">
        <h3>权限树视图</h3>
        <p>可视化显示完整的权限层级关系，支持搜索和过滤</p>
      </div>
      <div class="header-right">
        <el-input
          v-model="searchKeyword"
          placeholder="搜索权限..."
          clearable
          style="width: 200px; margin-right: 12px;"
        >
          <template #prefix>
            <el-icon><Search /></el-icon>
          </template>
        </el-input>
        <el-button @click="handleExpandAll">
          <el-icon><Plus /></el-icon>
          展开全部
        </el-button>
        <el-button @click="handleCollapseAll">
          <el-icon><Minus /></el-icon>
          收起全部
        </el-button>
      </div>
    </div>

    <!-- 权限统计 -->
    <div class="permission-stats">
      <el-row :gutter="20">
        <el-col :span="6">
          <div class="stat-card">
            <div class="stat-icon menu">
              <el-icon><Menu /></el-icon>
            </div>
            <div class="stat-content">
              <div class="stat-value">{{ menuCount }}</div>
              <div class="stat-label">菜单权限</div>
            </div>
          </div>
        </el-col>
        <el-col :span="6">
          <div class="stat-card">
            <div class="stat-icon api">
              <el-icon><Link /></el-icon>
            </div>
            <div class="stat-content">
              <div class="stat-value">{{ apiCount }}</div>
              <div class="stat-label">API权限</div>
            </div>
          </div>
        </el-col>
        <el-col :span="6">
          <div class="stat-card">
            <div class="stat-icon role">
              <el-icon><User /></el-icon>
            </div>
            <div class="stat-content">
              <div class="stat-value">{{ roleCount }}</div>
              <div class="stat-label">角色数量</div>
            </div>
          </div>
        </el-col>
        <el-col :span="6">
          <div class="stat-card">
            <div class="stat-icon user">
              <el-icon><Avatar /></el-icon>
            </div>
            <div class="stat-content">
              <div class="stat-value">{{ userCount }}</div>
              <div class="stat-label">用户数量</div>
            </div>
          </div>
        </el-col>
      </el-row>
    </div>

    <!-- 权限树 -->
    <div class="tree-container">
      <el-tree
        ref="treeRef"
        :data="filteredPermissionTree"
        :props="treeProps"
        node-key="id"
        :default-expanded-keys="expandedKeys"
        :filter-node-method="filterNode"
        class="permission-tree"
      >
        <template #default="{ node, data }">
          <div class="tree-node">
            <div class="node-content">
              <el-icon v-if="data.icon" class="node-icon" :class="data.type">
                <component :is="getIcon(data.icon)" />
              </el-icon>
              <div class="node-info">
                <div class="node-title">{{ data.title || data.right_name }}</div>
                <div class="node-details">
                  <el-tag v-if="data.path" size="small" type="info">{{ data.path }}</el-tag>
                  <el-tag v-if="data.method" size="small" :type="getMethodType(data.method)">
                    {{ data.method }}
                  </el-tag>
                  <el-tag v-if="data.type" size="small" :type="data.type === 'menu' ? 'success' : 'primary'">
                    {{ data.type === 'menu' ? '菜单' : 'API' }}
                  </el-tag>
                </div>
              </div>
            </div>
            <div class="node-actions">
              <el-button size="small" type="primary" @click="handleViewDetails(data)">
                详情
              </el-button>
            </div>
          </div>
        </template>
      </el-tree>
    </div>

    <!-- 权限详情对话框 -->
    <el-dialog
      v-model="detailDialogVisible"
      title="权限详情"
      width="600px"
    >
      <div v-if="selectedPermission" class="permission-details">
        <el-descriptions :column="2" border>
          <el-descriptions-item label="权限ID">
            {{ selectedPermission.id }}
          </el-descriptions-item>
          <el-descriptions-item label="权限名称">
            {{ selectedPermission.right_name }}
          </el-descriptions-item>
          <el-descriptions-item label="权限描述">
            {{ selectedPermission.description }}
          </el-descriptions-item>
          <el-descriptions-item label="权限类型">
            <el-tag :type="selectedPermission.is_menu ? 'success' : 'primary'">
              {{ selectedPermission.is_menu ? '菜单权限' : 'API权限' }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="路径" v-if="selectedPermission.path">
            {{ selectedPermission.path }}
          </el-descriptions-item>
          <el-descriptions-item label="请求方法" v-if="selectedPermission.method">
            <el-tag :type="getMethodType(selectedPermission.method)">
              {{ selectedPermission.method }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="父级权限ID">
            {{ selectedPermission.pid || '无' }}
          </el-descriptions-item>
          <el-descriptions-item label="排序">
            {{ selectedPermission.sort }}
          </el-descriptions-item>
          <el-descriptions-item label="状态">
            <el-tag :type="selectedPermission.is_del ? 'success' : 'danger'">
              {{ selectedPermission.is_del ? '启用' : '禁用' }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="创建时间" v-if="selectedPermission.created_at">
            {{ selectedPermission.created_at }}
          </el-descriptions-item>
          <el-descriptions-item label="更新时间" v-if="selectedPermission.updated_at">
            {{ selectedPermission.updated_at }}
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
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { ElMessage } from 'element-plus'
import { Search, Plus, Minus, Menu, Link, User, Avatar } from '@element-plus/icons-vue'
import { getPermissionList } from '@/api/permission'
import { getRoleList } from '@/api/role'
import { getAdminList } from '@/api/admins'

// 响应式数据
const treeRef = ref()
const searchKeyword = ref('')
const detailDialogVisible = ref(false)
const selectedPermission = ref(null)
const permissionTree = ref([])
const expandedKeys = ref([])

// 统计数据
const menuCount = ref(0)
const apiCount = ref(0)
const roleCount = ref(0)
const userCount = ref(0)

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

// 过滤后的权限树
const filteredPermissionTree = computed(() => {
  if (!searchKeyword.value) {
    return permissionTree.value
  }
  
  const filterTree = (nodes: any[]) => {
    return nodes.filter(node => {
      const matches = 
        node.right_name?.toLowerCase().includes(searchKeyword.value.toLowerCase()) ||
        node.description?.toLowerCase().includes(searchKeyword.value.toLowerCase()) ||
        node.path?.toLowerCase().includes(searchKeyword.value.toLowerCase())
      
      if (node.children) {
        const filteredChildren = filterTree(node.children)
        if (filteredChildren.length > 0) {
          node.children = filteredChildren
          return true
        }
      }
      
      return matches
    })
  }
  
  return filterTree(JSON.parse(JSON.stringify(permissionTree.value)))
})

// 获取权限数据
const getPermissionData = async () => {
  try {
    const response = await getPermissionList({ page: 1, limit: 1000 })
    const permissions = response.data.data || []
    
    // 构建权限树
    permissionTree.value = buildPermissionTree(permissions)
    
    // 统计数量
    menuCount.value = permissions.filter(p => p.is_menu === 1).length
    apiCount.value = permissions.filter(p => p.is_menu === 0).length
    
    // 获取角色和用户数量
    await getRoleAndUserCount()
    
  } catch (error) {
    ElMessage.error('获取权限数据失败')
  }
}

// 获取角色和用户数量
const getRoleAndUserCount = async () => {
  try {
    const [roleResponse, userResponse] = await Promise.all([
      getRoleList({ page: 1, limit: 1000 }),
      getAdminList({ page: 1, limit: 1000 })
    ])
    
    roleCount.value = roleResponse.data.data?.length || 0
    userCount.value = userResponse.data.data?.length || 0
  } catch (error) {
    console.error('获取统计数据失败:', error)
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
      type: permission.is_menu === 1 ? 'menu' : 'api',
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
  
  // 设置展开的节点
  expandedKeys.value = permissions.map(p => p.id)
  
  return tree.sort((a, b) => a.sort - b.sort)
}

// 过滤节点
const filterNode = (value: string, data: any) => {
  if (!value) return true
  return (
    data.right_name?.toLowerCase().includes(value.toLowerCase()) ||
    data.description?.toLowerCase().includes(value.toLowerCase()) ||
    data.path?.toLowerCase().includes(value.toLowerCase())
  )
}

// 操作方法
const handleExpandAll = () => {
  expandedKeys.value = permissionTree.value.map((node: any) => getAllNodeIds(node)).flat()
}

const handleCollapseAll = () => {
  expandedKeys.value = []
}

const handleViewDetails = (data: any) => {
  selectedPermission.value = data
  detailDialogVisible.value = true
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

// 监听搜索关键词
watch(searchKeyword, () => {
  treeRef.value?.filter(searchKeyword.value)
})

// 初始化
onMounted(() => {
  getPermissionData()
})
</script>

<style scoped>
.permission-tree-panel {
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

.permission-stats {
  margin-bottom: 24px;
}

.stat-card {
  display: flex;
  align-items: center;
  padding: 20px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 16px;
  font-size: 24px;
  color: white;
}

.stat-icon.menu {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stat-icon.api {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.stat-icon.role {
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.stat-icon.user {
  background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.stat-content {
  flex: 1;
}

.stat-value {
  font-size: 24px;
  font-weight: 600;
  color: #303133;
  line-height: 1;
  margin-bottom: 4px;
}

.stat-label {
  font-size: 14px;
  color: #909399;
}

.tree-container {
  background: white;
  border-radius: 8px;
  border: 1px solid #e4e7ed;
  padding: 20px;
  min-height: 500px;
  overflow-y: auto;
  max-height: 600px;
}

.permission-tree {
  min-height: 400px;
  padding: 10px 0;
}

/* 修复Element Plus树组件的样式 */
:deep(.el-tree-node) {
  margin-bottom: 8px;
}

:deep(.el-tree-node__content) {
  padding: 8px 0 !important;
  margin-bottom: 4px;
  border-bottom: 1px solid #f5f5f5;
}

:deep(.el-tree-node__children) {
  margin-top: 8px;
}

:deep(.el-tree-node__expand-icon) {
  margin-right: 8px;
}

.tree-node {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  padding: 12px 0;
  margin-bottom: 8px;
  border-bottom: 1px solid #f0f0f0;
}

.node-content {
  display: flex;
  align-items: center;
  flex: 1;
}

.node-icon {
  margin-right: 12px;
  font-size: 16px;
}

.node-icon.menu {
  color: #67c23a;
}

.node-icon.api {
  color: #409eff;
}

.node-info {
  flex: 1;
  min-width: 0;
}

.node-title {
  font-weight: 500;
  color: #303133;
  margin-bottom: 6px;
  line-height: 1.4;
  word-wrap: break-word;
}

.node-details {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
  margin-top: 4px;
}

.node-actions {
  opacity: 0;
  transition: opacity 0.2s;
}

.tree-node:hover .node-actions {
  opacity: 1;
}

.permission-details {
  padding: 16px 0;
}
</style>
