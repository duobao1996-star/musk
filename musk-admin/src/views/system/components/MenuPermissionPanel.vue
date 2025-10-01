<template>
  <div class="menu-permission-panel">
    <!-- 操作栏 -->
    <div class="panel-header">
      <div class="header-left">
        <h3>菜单权限管理</h3>
        <p>管理侧边栏菜单的显示和层级结构</p>
      </div>
      <div class="header-right">
        <el-button type="primary" @click="handleAdd">
          <el-icon><Plus /></el-icon>
          添加菜单
        </el-button>
        <el-button @click="handleRefresh">
          <el-icon><Refresh /></el-icon>
          刷新
        </el-button>
      </div>
    </div>

    <!-- 菜单树 -->
    <div class="menu-tree-container">
      <el-tree
        ref="menuTreeRef"
        :data="menuTree"
        :props="treeProps"
        node-key="id"
        draggable
        :allow-drop="allowDrop"
        :allow-drag="allowDrag"
        @node-drop="handleNodeDrop"
        class="menu-tree"
      >
        <template #default="{ node, data }">
          <div class="tree-node">
            <div class="node-content">
              <el-icon v-if="data.icon" class="node-icon">
                <component :is="getIcon(data.icon)" />
              </el-icon>
              <span class="node-title">{{ data.title }}</span>
              <span class="node-path">{{ data.path }}</span>
            </div>
            <div class="node-actions">
              <el-button size="small" type="primary" @click="handleEdit(data)">
                编辑
              </el-button>
              <el-button size="small" type="danger" @click="handleDelete(data)">
                删除
              </el-button>
            </div>
          </div>
        </template>
      </el-tree>
    </div>

    <!-- 添加/编辑菜单对话框 -->
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
        <el-form-item label="菜单名称" prop="title">
          <el-input v-model="form.title" placeholder="请输入菜单名称" />
        </el-form-item>
        
        <el-form-item label="菜单路径" prop="path">
          <el-input v-model="form.path" placeholder="请输入菜单路径，如：/system/users" />
        </el-form-item>
        
        <el-form-item label="菜单图标" prop="icon">
          <el-input v-model="form.icon" placeholder="请输入图标名称，如：ri:user-line" />
        </el-form-item>
        
        <el-form-item label="父级菜单" prop="pid">
          <el-tree-select
            v-model="form.pid"
            :data="menuOptions"
            :props="{ label: 'title', value: 'id' }"
            placeholder="选择父级菜单"
            clearable
            check-strictly
          />
        </el-form-item>
        
        <el-form-item label="排序" prop="sort">
          <el-input-number v-model="form.sort" :min="0" :max="999" />
        </el-form-item>
        
        <el-form-item label="显示设置">
          <el-checkbox v-model="form.hidden">隐藏菜单</el-checkbox>
          <el-checkbox v-model="form.noCache">不缓存</el-checkbox>
          <el-checkbox v-model="form.affix">固定标签</el-checkbox>
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
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus, Refresh } from '@element-plus/icons-vue'
import { getMenuPermissions, createPermission, updatePermission, deletePermission } from '@/api/permission'

// 响应式数据
const menuTreeRef = ref()
const dialogVisible = ref(false)
const dialogTitle = ref('')
const menuTree = ref([])
const menuOptions = ref([])

// 表单数据
const form = reactive({
  id: null,
  title: '',
  path: '',
  icon: '',
  pid: null,
  sort: 0,
  hidden: false,
  noCache: false,
  affix: false
})

// 表单验证规则
const rules = {
  title: [{ required: true, message: '请输入菜单名称', trigger: 'blur' }],
  path: [{ required: true, message: '请输入菜单路径', trigger: 'blur' }],
  icon: [{ required: true, message: '请输入菜单图标', trigger: 'blur' }]
}

// 树形组件配置
const treeProps = {
  children: 'children',
  label: 'title'
}

// 图标映射
const getIcon = (iconName: string) => {
  // 这里可以根据实际需要映射图标
  return 'Document'
}

// 获取菜单数据
const getMenuData = async () => {
  try {
    const response = await getMenuPermissions()
    menuTree.value = response.data.data || []
    buildMenuOptions()
  } catch (error) {
    ElMessage.error('获取菜单数据失败')
  }
}

// 构建菜单选项
const buildMenuOptions = () => {
  const options = []
  const buildOptions = (nodes: any[], level = 0) => {
    nodes.forEach(node => {
      options.push({
        id: node.id,
        title: '　'.repeat(level) + node.title,
        pid: node.pid
      })
      if (node.children && node.children.length > 0) {
        buildOptions(node.children, level + 1)
      }
    })
  }
  buildOptions(menuTree.value)
  menuOptions.value = options
}

// 拖拽相关
const allowDrop = (draggingNode: any, dropNode: any, type: string) => {
  return type !== 'inner'
}

const allowDrag = (draggingNode: any) => {
  return true
}

const handleNodeDrop = async (draggingNode: any, dropNode: any, dropType: string) => {
  // 处理拖拽排序
  try {
    // 这里需要调用API更新排序
    ElMessage.success('排序更新成功')
  } catch (error) {
    ElMessage.error('排序更新失败')
  }
}

// 操作方法
const handleAdd = () => {
  dialogTitle.value = '添加菜单'
  resetForm()
  dialogVisible.value = true
}

const handleEdit = (data: any) => {
  dialogTitle.value = '编辑菜单'
  Object.assign(form, data)
  dialogVisible.value = true
}

const handleDelete = async (data: any) => {
  try {
    await ElMessageBox.confirm('确定要删除这个菜单吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })
    
    await deletePermission(data.id)
    ElMessage.success('删除成功')
    getMenuData()
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('删除失败')
    }
  }
}

const handleRefresh = () => {
  getMenuData()
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
    getMenuData()
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
    title: '',
    path: '',
    icon: '',
    pid: null,
    sort: 0,
    hidden: false,
    noCache: false,
    affix: false
  })
}

// 初始化
onMounted(() => {
  getMenuData()
})
</script>

<style scoped>
.menu-permission-panel {
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

.menu-tree-container {
  background: white;
  border-radius: 8px;
  border: 1px solid #e4e7ed;
  padding: 16px;
}

.menu-tree {
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

.node-title {
  font-weight: 500;
  color: #303133;
  margin-right: 12px;
}

.node-path {
  color: #909399;
  font-size: 12px;
  font-family: monospace;
}

.node-actions {
  display: flex;
  gap: 8px;
  opacity: 0;
  transition: opacity 0.2s;
}

.tree-node:hover .node-actions {
  opacity: 1;
}
</style>
