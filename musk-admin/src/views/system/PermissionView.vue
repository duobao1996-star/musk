<template>
  <div class="permission-management">
    <!-- 页面标题 -->
    <div class="page-header">
      <h2>权限管理</h2>
      <p class="page-description">管理系统菜单权限和API权限，配置角色访问控制</p>
    </div>

    <!-- 标签页导航 -->
    <el-tabs v-model="activeTab" type="card" class="permission-tabs">
      <!-- 菜单权限管理 -->
      <el-tab-pane label="菜单权限" name="menu">
        <MenuPermissionPanel />
      </el-tab-pane>

      <!-- API权限管理 -->
      <el-tab-pane label="API权限" name="api">
        <ApiPermissionPanel />
      </el-tab-pane>

      <!-- 权限树视图 -->
      <el-tab-pane label="权限树" name="tree">
        <PermissionTreePanel />
      </el-tab-pane>

      <!-- 角色权限分配 -->
      <el-tab-pane label="角色权限" name="role">
        <RolePermissionPanel />
      </el-tab-pane>

      <!-- 权限日志 -->
      <el-tab-pane label="权限日志" name="logs">
        <PermissionLogPanel />
      </el-tab-pane>
    </el-tabs>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import MenuPermissionPanel from './components/MenuPermissionPanel.vue'
import ApiPermissionPanel from './components/ApiPermissionPanel.vue'
import PermissionTreePanel from './components/PermissionTreePanel.vue'
import RolePermissionPanel from './components/RolePermissionPanel.vue'
import PermissionLogPanel from './components/PermissionLogPanel.vue'

const activeTab = ref('menu')
</script>

<style scoped>
.permission-management {
  padding: 20px;
}

.page-header {
  margin-bottom: 24px;
  padding-bottom: 16px;
  border-bottom: 1px solid #e4e7ed;
}

.page-header h2 {
  margin: 0 0 8px 0;
  color: #303133;
  font-size: 24px;
  font-weight: 600;
}

.page-description {
  margin: 0;
  color: #606266;
  font-size: 14px;
}

.permission-tabs {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 12px 0 rgba(0, 0, 0, 0.1);
}

:deep(.el-tabs__header) {
  margin: 0;
  background: #f8f9fa;
  border-radius: 8px 8px 0 0;
}

:deep(.el-tabs__nav) {
  border: none;
}

:deep(.el-tabs__item) {
  border: none;
  padding: 0 24px;
  height: 48px;
  line-height: 48px;
  font-weight: 500;
}

:deep(.el-tabs__item.is-active) {
  background: white;
  color: #409eff;
}

:deep(.el-tabs__content) {
  padding: 24px;
  min-height: 600px;
}
</style>