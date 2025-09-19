<template>
  <div style="display:flex;align-items:center;justify-content:center;height:100vh;">
    <el-card style="width:360px">
      <el-form @submit.prevent="onSubmit">
        <el-form-item label="用户名"><el-input v-model="username"/></el-form-item>
        <el-form-item label="密码"><el-input v-model="password" type="password"/></el-form-item>
        <el-button type="primary" @click="onSubmit" style="width:100%">登录</el-button>
      </el-form>
    </el-card>
  </div>
</template>
<script setup lang="ts">
import {ref} from 'vue';
import axios from 'axios';
import {useRouter} from 'vue-router';
const router=useRouter();
const username=ref('admin');
const password=ref('Admin@12345');
async function onSubmit(){
  const res=await axios.post('/api/login',{username:username.value,password:password.value});
  if(res.data?.data?.token){
    localStorage.setItem('token',res.data.data.token);
    router.push('/dashboard');
  }
}
</script>