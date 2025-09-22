<template>
  <div class="system-management">
    <!-- 系统配置管理 -->
    <el-row :gutter="20">
      <el-col :span="24">
        <el-card>
          <template #header>
            <div class="card-header">
              <span>系统配置管理</span>
              <el-button type="primary" @click="handleRefresh">
                <el-icon><Refresh /></el-icon>
                刷新配置
              </el-button>
            </div>
          </template>
          
          <el-row :gutter="20">
            <el-col :span="8">
              <div class="config-section">
                <div class="section-header">
                  <el-icon class="section-icon"><Setting /></el-icon>
                  <span>权限配置</span>
                </div>
                <div class="section-content">
                  <div class="config-item" @click="$router.push('/system/roles')">
                    <el-icon><UserFilled /></el-icon>
                    <span>角色管理</span>
                    <el-icon class="arrow"><ArrowRight /></el-icon>
                  </div>
                  <div class="config-item" @click="$router.push('/system/permissions')">
                    <el-icon><Key /></el-icon>
                    <span>权限管理</span>
                    <el-icon class="arrow"><ArrowRight /></el-icon>
                  </div>
                </div>
              </div>
            </el-col>
            
            <el-col :span="8">
              <div class="config-section">
                <div class="section-header">
                  <el-icon class="section-icon"><Monitor /></el-icon>
                  <span>系统监控</span>
                </div>
                <div class="section-content">
                  <div class="config-item" @click="$router.push('/system/logs')">
                    <el-icon><Document /></el-icon>
                    <span>操作日志</span>
                    <el-icon class="arrow"><ArrowRight /></el-icon>
                  </div>
                  <div class="config-item" @click="$router.push('/system/performance')">
                    <el-icon><DataAnalysis /></el-icon>
                    <span>性能监控</span>
                    <el-icon class="arrow"><ArrowRight /></el-icon>
                  </div>
                </div>
              </div>
            </el-col>
            
            <el-col :span="8">
              <div class="config-section">
                <div class="section-header">
                  <el-icon class="section-icon"><Tools /></el-icon>
                  <span>系统工具</span>
                </div>
                <div class="section-content">
                  <div class="config-item" @click="handleSystemInfo">
                    <el-icon><InfoFilled /></el-icon>
                    <span>系统信息</span>
                    <el-icon class="arrow"><ArrowRight /></el-icon>
                  </div>
                  <div class="config-item" @click="handleCacheClear">
                    <el-icon><Delete /></el-icon>
                    <span>清理缓存</span>
                    <el-icon class="arrow"><ArrowRight /></el-icon>
                  </div>
                </div>
              </div>
            </el-col>
          </el-row>
        </el-card>
      </el-col>
    </el-row>

    <!-- 系统状态 -->
    <el-row :gutter="20" style="margin-top: 20px;">
      <el-col :span="12">
        <el-card>
          <template #header>
            <span>系统状态</span>
          </template>
          <div class="status-list">
            <div class="status-item">
              <el-icon class="status-icon success"><CircleCheck /></el-icon>
              <span>系统运行状态</span>
              <el-tag type="success">正常</el-tag>
            </div>
            <div class="status-item">
              <el-icon class="status-icon success"><CircleCheck /></el-icon>
              <span>数据库连接</span>
              <el-tag type="success">正常</el-tag>
            </div>
            <div class="status-item">
              <el-icon class="status-icon warning"><Warning /></el-icon>
              <span>内存使用率</span>
              <el-tag type="warning">75%</el-tag>
            </div>
            <div class="status-item">
              <el-icon class="status-icon info"><InfoFilled /></el-icon>
              <span>磁盘空间</span>
              <el-tag type="info">充足</el-tag>
            </div>
          </div>
        </el-card>
      </el-col>
      
      <el-col :span="12">
        <el-card>
          <template #header>
            <span>最近操作</span>
          </template>
          <div class="recent-actions">
            <div class="action-item">
              <el-icon class="action-icon"><User /></el-icon>
              <div class="action-info">
                <div class="action-desc">管理员 admin 登录系统</div>
                <div class="action-time">2分钟前</div>
              </div>
            </div>
            <div class="action-item">
              <el-icon class="action-icon"><Key /></el-icon>
              <div class="action-info">
                <div class="action-desc">权限配置已更新</div>
                <div class="action-time">10分钟前</div>
              </div>
            </div>
            <div class="action-item">
              <el-icon class="action-icon"><Document /></el-icon>
              <div class="action-info">
                <div class="action-desc">操作日志已清理</div>
                <div class="action-time">1小时前</div>
              </div>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { 
  Refresh, 
  Setting,
  Monitor,
  Tools,
  UserFilled, 
  Key, 
  Document, 
  DataAnalysis,
  InfoFilled,
  Delete,
  ArrowRight,
  CircleCheck,
  Warning,
  User
} from '@element-plus/icons-vue'

const router = useRouter()

// 刷新配置
const handleRefresh = () => {
  ElMessage.success('配置已刷新')
}

// 系统信息
const handleSystemInfo = () => {
  ElMessage.info('系统信息功能开发中...')
}

// 清理缓存
const handleCacheClear = () => {
  ElMessage.success('缓存清理完成')
}
</script>

<style scoped>
.system-management {
  padding: 20px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 16px;
  font-weight: 600;
}

.config-section {
  border: 1px solid #e4e7ed;
  border-radius: 8px;
  overflow: hidden;
  background: #fff;
}

.section-header {
  display: flex;
  align-items: center;
  padding: 16px 20px;
  background: #f8f9fa;
  border-bottom: 1px solid #e4e7ed;
  font-weight: 600;
  color: #303133;
}

.section-icon {
  margin-right: 8px;
  color: #409eff;
  font-size: 18px;
}

.section-content {
  padding: 0;
}

.config-item {
  display: flex;
  align-items: center;
  padding: 16px 20px;
  cursor: pointer;
  transition: all 0.3s;
  border-bottom: 1px solid #f0f0f0;
}

.config-item:last-child {
  border-bottom: none;
}

.config-item:hover {
  background: #f8f9fa;
  color: #409eff;
}

.config-item .el-icon {
  margin-right: 12px;
  font-size: 16px;
}

.config-item span {
  flex: 1;
  font-size: 14px;
}

.config-item .arrow {
  margin-left: auto;
  margin-right: 0;
  font-size: 14px;
  opacity: 0.6;
}

.status-list {
  padding: 0;
}

.status-item {
  display: flex;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid #f0f0f0;
}

.status-item:last-child {
  border-bottom: none;
}

.status-icon {
  margin-right: 12px;
  font-size: 18px;
}

.status-icon.success {
  color: #67c23a;
}

.status-icon.warning {
  color: #e6a23c;
}

.status-icon.info {
  color: #909399;
}

.status-item span {
  flex: 1;
  font-size: 14px;
  color: #303133;
}

.recent-actions {
  padding: 0;
}

.action-item {
  display: flex;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid #f0f0f0;
}

.action-item:last-child {
  border-bottom: none;
}

.action-icon {
  margin-right: 12px;
  font-size: 16px;
  color: #409eff;
}

.action-info {
  flex: 1;
}

.action-desc {
  font-size: 14px;
  color: #303133;
  margin-bottom: 4px;
}

.action-time {
  font-size: 12px;
  color: #909399;
}
</style>
