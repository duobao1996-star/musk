<template>
  <div class="login-container">
    <!-- 背景装饰与 Money Rain -->
    <div class="background-decoration">
      <div class="floating-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
        <div class="shape shape-4"></div>
        <div class="shape shape-5"></div>
      </div>
      <div class="money-rain">
        <span
          v-for="(item, index) in moneyItems"
          :key="index"
          class="money"
          :style="item.style"
        >
          {{ item.symbol }}
        </span>
      </div>
    </div>

    <div class="login-box">
      <div class="login-header">
        <div class="logo-section">
          <img src="/favicon.svg" alt="logo" class="logo-image" />
          <div class="logo-text">
            <h1 class="brand-name">Musk管理系统</h1>
           
          </div>
        </div>
      </div>
      
      <el-form
        ref="loginFormRef"
        :model="loginForm"
        :rules="loginRules"
        class="login-form"
        @submit.prevent="handleLogin"
      >
        <el-form-item prop="username">
          <el-input
            v-model="loginForm.username"
            placeholder="请输入用户名"
            size="large"
            :prefix-icon="User"
            clearable
          />
        </el-form-item>
        
        <el-form-item prop="password">
          <el-input
            v-model="loginForm.password"
            type="password"
            placeholder="请输入密码"
            size="large"
            :prefix-icon="Lock"
            show-password
            clearable
            @keyup.enter="handleLogin"
          />
        </el-form-item>
        
        <el-form-item>
          <el-button
            type="primary"
            size="large"
            :loading="loading"
            @click="handleLogin"
            class="login-btn"
          >
            {{ loading ? '登录中...' : '登录' }}
          </el-button>
        </el-form-item>
      </el-form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, type FormInstance, type FormRules } from 'element-plus'
import { User, Lock } from '@element-plus/icons-vue'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const loginFormRef = ref<FormInstance>()
const loading = ref(false)

const loginForm = reactive({
  username: '',
  password: ''
})

const loginRules: FormRules = {
  username: [
    { required: true, message: '请输入用户名', trigger: 'blur' }
  ],
  password: [
    { required: true, message: '请输入密码', trigger: 'blur' },
    { min: 6, message: '密码长度不能少于6位', trigger: 'blur' }
  ]
}

// Money rain 数据
const moneyItems = ref<Array<{ symbol: string; style: Record<string, string> }>>([])

const generateMoneyRain = () => {
  const symbols = ['💰', '$']
  const totalCount = 80
  const items: Array<{ symbol: string; style: Record<string, string> }> = []

  for (let i = 0; i < totalCount; i++) {
    const left = (Math.random() * 100).toFixed(2)
    const size = (18 + Math.random() * 14).toFixed(0)
    const duration = (2.6 + Math.random() * 1.6).toFixed(2)
    const delay = (-Math.random() * 2).toFixed(2)

    items.push({
      symbol: symbols[Math.floor(Math.random() * symbols.length)],
      style: {
        left: left + 'vw',
        fontSize: size + 'px',
        animationDuration: duration + 's',
        animationDelay: delay + 's'
      }
    })
  }

  moneyItems.value = items
}

onMounted(() => {
  generateMoneyRain()
})

const handleLogin = async () => {
  if (!loginFormRef.value) return
  
  try {
    const valid = await loginFormRef.value.validate()
    if (!valid) return
    
    loading.value = true
    await authStore.loginAction(loginForm)
    ElMessage.success('登录成功')
    router.push('/')
  } catch (error: any) {
    ElMessage.error(error.message || '登录失败')
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-container {
  height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  overflow: hidden;
}

.login-container::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
  opacity: 0.3;
  animation: float 20s ease-in-out infinite;
}

@keyframes float {
  0%, 100% { transform: translateY(0px) rotate(0deg); }
  50% { transform: translateY(-20px) rotate(180deg); }
}

.login-box {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-radius: 20px;
  padding: 40px;
  width: 400px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  position: relative;
  z-index: 1;
}

.login-header {
  text-align: center;
  margin-bottom: 30px;
}

.logo-section {
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 8px;
}

.logo-image {
  width: 48px;
  height: 48px;
  margin-right: 12px;
}

.brand-name {
  color: #2c3e50;
  font-size: 24px;
  font-weight: 700;
  margin: 0 0 4px 0;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.slogan {
  color: #7f8c8d;
  font-size: 13px;
}

/* 背景装饰与 Money Rain */
.background-decoration {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  z-index: 0;
}

.floating-shapes {
  position: absolute;
  width: 100%;
  height: 100%;
}

.shape {
  position: absolute;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.1);
  animation: float 6s ease-in-out infinite;
}

.shape-1 { width: 80px; height: 80px; top: 10%; left: 10%; animation-delay: 0s; }
.shape-2 { width: 120px; height: 120px; top: 20%; right: 15%; animation-delay: 2s; }
.shape-3 { width: 60px; height: 60px; bottom: 30%; left: 20%; animation-delay: 4s; }
.shape-4 { width: 100px; height: 100px; bottom: 20%; right: 25%; animation-delay: 1s; }
.shape-5 { width: 140px; height: 140px; top: 50%; left: 50%; animation-delay: 3s; }

.money-rain {
  position: fixed;
  inset: 0;
  pointer-events: none;
  overflow: hidden;
  z-index: 0;
}

.money {
  position: absolute;
  top: -10vh;
  color: #ffd54a;
  text-shadow: 0 0 8px rgba(255, 213, 74, 0.7), 0 0 18px rgba(255, 213, 74, 0.35);
  opacity: 0.95;
  animation-name: fall;
  animation-timing-function: linear;
  animation-iteration-count: infinite;
  animation-duration: 3s;
  animation-delay: 0s;
}

@keyframes fall {
  0% { transform: translateY(-12vh) translateX(0) rotate(0deg); opacity: 0; }
  10% { opacity: 1; }
  100% { transform: translateY(120vh) translateX(2vw) rotate(360deg); opacity: 0.9; }
}

.login-form {
  width: 100%;
}

.login-form .el-form-item {
  margin-bottom: 20px;
}

.login-btn {
  width: 100%;
  height: 45px;
  font-size: 16px;
  font-weight: 600;
  border-radius: 10px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  transition: all 0.3s ease;
}

.login-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
}

:deep(.el-input__wrapper) {
  border-radius: 10px;
  border: 2px solid #e1e8ed;
  transition: all 0.3s ease;
}

:deep(.el-input__wrapper:hover) {
  border-color: #667eea;
}

:deep(.el-input__wrapper.is-focus) {
  border-color: #667eea;
  box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2);
}
</style>
