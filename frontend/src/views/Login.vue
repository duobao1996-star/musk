<template>
  <div class="login-wrap">
    <!-- 背景装饰：漂浮形状 + 金币雨 -->
    <div class="background-decoration" aria-hidden="true">
      <div class="floating-shapes">
        <div class="shape s1"></div>
        <div class="shape s2"></div>
        <div class="shape s3"></div>
        <div class="shape s4"></div>
        <div class="shape s5"></div>
      </div>
      <div class="money-rain">
        <span v-for="(m,i) in moneyItems" :key="i" class="money" :style="m.style">{{ m.symbol }}</span>
      </div>
    </div>

    <el-card class="login-panel" shadow="always">
      <div class="panel-header">
        <img class="logo" src="/vite.svg" alt="logo" />
        <div class="brand-text">
          <div class="brand-line">
            <span class="brand-name">Musk</span>
            <span class="ver-chip">v8.1.8</span>
          </div>
          <div class="slogan-en">Money Ultra Simple Key</div>
          <div class="slogan-cn">超级简单财富密码</div>
        </div>
      </div>

      <h2 class="form-title">登录</h2>
      <el-form ref="formRef" :model="form" :rules="rules" label-width="0" class="form" @keyup.enter="onSubmit">
        <el-form-item prop="username">
          <el-input v-model="form.username" placeholder="用户名：admin" clearable />
        </el-form-item>
        <el-form-item prop="password">
          <el-input v-model="form.password" placeholder="密码：Admin@12345" type="password" show-password />
        </el-form-item>
        <el-form-item>
          <el-input v-model="form.google_code" placeholder="谷歌验证码(可选)" />
        </el-form-item>
        <div class="row-between">
          <el-checkbox v-model="remember">记住我</el-checkbox>
        </div>
        <el-button type="primary" :loading="loading" class="login-btn" @click="onSubmit">登录</el-button>
      </el-form>
    </el-card>
  </div>
  
</template>
<script setup lang="ts">
import {ref, onMounted} from 'vue';
import {useRouter} from 'vue-router';
import { ElMessage } from 'element-plus';
import http from '@/api/http';

interface LoginForm { username: string; password: string; google_code?: string }
const router=useRouter();
const formRef = ref();
const loading = ref(false);
const remember = ref(true);
const form = ref<LoginForm>({ username: 'admin', password: 'Admin@12345', google_code: '' });
const rules = {
  username: [{ required: true, message: '请输入用户名', trigger: 'blur' }],
  password: [{ required: true, message: '请输入密码', trigger: 'blur' }]
};

const moneyItems = ref<{symbol:string;style:any}[]>([]);

onMounted(()=>{
  const u = localStorage.getItem('remember_username');
  if(u){ form.value.username = u; }
  generateMoneyRain();
});

function goDoc(){ window.open('https://www.webman.tech/', '_blank'); }

async function onSubmit(){
  if(!formRef.value) return;
  await formRef.value.validate(async (valid: boolean)=>{
    if(!valid) return;
    try{
      loading.value = true;
      const res = await http.post('/api/login', { username: form.value.username, password: form.value.password });
      const token = res.data?.data?.token;
      if(token){
        localStorage.setItem('token', token);
        if(remember.value){ localStorage.setItem('remember_username', form.value.username); }
        ElMessage.success('登录成功');
        router.push('/dashboard');
      }else{
        ElMessage.error(res.data?.message || '登录失败');
      }
    }catch(e: any){
      ElMessage.error(e?.response?.data?.message || '登录失败');
    }finally{
      loading.value = false;
    }
  });
}

function generateMoneyRain(){
  const symbols = ['💰', '$'];
  const total = 90;
  const arr: {symbol:string;style:any}[] = [];
  for(let i=0;i<total;i++){
    const left = (Math.random()*100).toFixed(2);
    const size = (18 + Math.random()*14).toFixed(0);
    const duration = (2.6 + Math.random()*1.8).toFixed(2);
    const delay = (-Math.random()*2).toFixed(2);
    arr.push({
      symbol: symbols[Math.floor(Math.random()*symbols.length)],
      style: { left: left+'vw', fontSize: size+'px', animationDuration: duration+'s', animationDelay: delay+'s' }
    });
  }
  moneyItems.value = arr;
}
</script>
<style scoped>
.login-wrap{ position:fixed; inset:0; display:flex; align-items:center; justify-content:center; padding:0; overflow:hidden; }
.login-panel{ width: 420px; border-radius:16px; box-shadow: 0 20px 40px rgba(0,0,0,0.12); padding: 30px 26px; background: rgba(255,255,255,0.96); backdrop-filter: blur(16px); }
.panel-header{ display:flex; align-items:center; justify-content:center; gap:12px; margin-bottom:12px; }
.panel-header .logo{ width:48px; height:48px; border-radius:12px; }
.brand-text{ display:flex; flex-direction:column; align-items:center; }
.brand-line{ display:flex; gap:8px; align-items:baseline; line-height:1; }
.brand-name{ font-size:24px; font-weight:800; color:#0f172a; letter-spacing:.2px; }
.ver-chip{ font-size:11px; color:#1d4ed8; background:#eaf2ff; padding:2px 8px; border-radius:999px; border:1px solid #d6e6ff; text-transform:lowercase }
.slogan-en{ font-size:14px; color:#475569; font-weight:600; text-align:center; margin-top:6px; letter-spacing:.2px; }
.slogan-cn{ font-size:12px; color:#8a8f98; text-align:center; margin-top:2px; letter-spacing:.2px; }
.form-title{ font-size:22px; font-weight:900; text-align:center; margin: 10px 0 16px; color:#0b1220; letter-spacing:.3px; }
.form{ margin-top:2px; }
.row-between{ display:flex; align-items:center; justify-content:space-between; margin-top:6px; margin-bottom:12px; }
.login-btn{ width:100%; height:44px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); border:none; border-radius:10px; }
</style>
<style scoped>
/* 背景动画与漂浮装饰，参考示例精简实现 */
.background-decoration{position:absolute;inset:0;pointer-events:none;z-index:0}
.floating-shapes{position:absolute;inset:0}
.shape{position:absolute;border-radius:50%;background:rgba(255,255,255,.18);filter:blur(0.2px);animation:float 6s ease-in-out infinite}
.s1{width:80px;height:80px;top:10%;left:8%;animation-delay:0s}
.s2{width:120px;height:120px;top:18%;right:12%;animation-delay:1.2s}
.s3{width:60px;height:60px;bottom:28%;left:18%;animation-delay:2.4s}
.s4{width:100px;height:100px;bottom:16%;right:22%;animation-delay:.6s}
.s5{width:140px;height:140px;top:52%;left:54%;animation-delay:1.8s}
@keyframes float{0%,100%{transform:translateY(0) scale(1)}50%{transform:translateY(-18px) scale(1.02)}}

/* 渐变背景平滑流动 */
.login-wrap{
  background: linear-gradient(135deg,#667eea 0%, #764ba2 25%, #f093fb 50%, #f5576c 75%, #4facfe 100%);
  background-size: 400% 400%;
  animation: gradientShift 12s ease infinite;
}
@keyframes gradientShift{0%{background-position:0% 50%}50%{background-position:100% 50%}100%{background-position:0% 50%}}

/* 金币雨 */
.money-rain{position:absolute;inset:0;pointer-events:none;overflow:hidden;z-index:0}
.money{position:absolute;top:-10vh;color:#ffd54a;text-shadow:0 0 8px rgba(255,213,74,.7),0 0 18px rgba(255,213,74,.35);opacity:.95;animation-name:fall;animation-timing-function:linear;animation-iteration-count:infinite}
@keyframes fall{0%{transform:translateY(-12vh) translateX(0) rotate(0deg);opacity:0}10%{opacity:1}100%{transform:translateY(120vh) translateX(2vw) rotate(360deg);opacity:.9}}
</style>