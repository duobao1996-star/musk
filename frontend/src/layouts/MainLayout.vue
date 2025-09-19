<template>
  <div class="layout">
    <el-container>
      <el-aside width="200px">
        <el-menu :default-active="$route.path" router>
          <el-menu-item index="/dashboard">仪表盘</el-menu-item>
          <el-menu-item index="/permissions">权限管理</el-menu-item>
          <el-menu-item index="/roles">角色管理</el-menu-item>
          <el-menu-item index="/operation-logs">操作日志</el-menu-item>
        </el-menu>
      </el-aside>
      <el-container>
        <el-header height="56px" style="display:flex;align-items:center;justify-content:space-between">
          <div style="font-weight:600">Webman Admin</div>
          <div>
            <el-button size="small" @click="logout">退出登录</el-button>
          </div>
        </el-header>
        <TabsBar />
        <el-main>
          <router-view />
        </el-main>
      </el-container>
    </el-container>
  </div>
</template>
<script setup lang="ts">
import TabsBar from '@/components/TabsBar.vue';
import {useRouter} from 'vue-router';
const router=useRouter();
function logout(){ localStorage.removeItem('token'); router.push('/login'); }
</script>
<style scoped>
.layout { height: 100vh; }
:deep(.el-container) { height: 100%; }
</style>


