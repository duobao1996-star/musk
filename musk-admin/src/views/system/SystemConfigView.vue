<template>
  <div class="system-config-container">
    <el-card class="config-card">
      <template #header>
        <span>系统配置</span>
      </template>

      <el-form :model="config" label-width="120px">
        <el-form-item label="系统名称">
          <el-input v-model="config.systemName" />
        </el-form-item>
        <el-form-item label="系统Logo">
          <el-input v-model="config.systemLogo" />
        </el-form-item>
        <el-form-item label="系统描述">
          <el-input v-model="config.systemDesc" type="textarea" />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="saveConfig">保存配置</el-button>
        </el-form-item>
      </el-form>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { ElMessage } from 'element-plus'

const config = ref({
  systemName: 'Musk管理系统',
  systemLogo: '',
  systemDesc: '基于Vue3的现代化管理系统'
})

const saveConfig = () => {
  ElMessage.success('配置保存成功')
}
</script>

<style scoped>
.system-config-container {
  padding: 20px;
}
</style>

