<template>
  <div class="system-info-container">
    <el-card class="info-card">
      <template #header>
        <div class="card-header">
          <span>系统信息</span>
          <el-button type="primary" @click="refreshInfo">刷新</el-button>
        </div>
      </template>

      <el-descriptions :column="2" border>
        <el-descriptions-item label="系统名称">{{ systemInfo.name }}</el-descriptions-item>
        <el-descriptions-item label="系统版本">{{ systemInfo.version }}</el-descriptions-item>
        <el-descriptions-item label="运行环境">{{ systemInfo.environment }}</el-descriptions-item>
        <el-descriptions-item label="服务器时间">{{ systemInfo.serverTime }}</el-descriptions-item>
        <el-descriptions-item label="运行时间">{{ systemInfo.uptime }}</el-descriptions-item>
        <el-descriptions-item label="数据库版本">{{ systemInfo.databaseVersion }}</el-descriptions-item>
      </el-descriptions>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'

const systemInfo = ref({
  name: 'Musk管理系统',
  version: '1.0.0',
  environment: 'Production',
  serverTime: new Date().toLocaleString(),
  uptime: '0天0小时',
  databaseVersion: 'MySQL 8.0'
})

const refreshInfo = () => {
  systemInfo.value.serverTime = new Date().toLocaleString()
  ElMessage.success('刷新成功')
}

onMounted(() => {
  refreshInfo()
})
</script>

<style scoped>
.system-info-container {
  padding: 20px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
</style>

