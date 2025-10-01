import { createApp } from 'vue'
import { createPinia } from 'pinia'
import ElementPlus from 'element-plus'
import 'element-plus/dist/index.css'
// 按需导入常用图标，减少包体积
import { 
  House, 
  Setting, 
  User, 
  Document, 
  Monitor,
  Operation,
  Key,
  Avatar,
  View,
  Edit,
  Delete,
  Plus,
  Search,
  Refresh,
  Download,
  Upload
} from '@element-plus/icons-vue'

import App from './App.vue'
import router from './router'

const app = createApp(App)

// 注册常用图标
const icons = {
  House, Setting, User, Document, Monitor, Operation, Key, Avatar,
  View, Edit, Delete, Plus, Search, Refresh, Download, Upload
}

for (const [key, component] of Object.entries(icons)) {
  app.component(key, component)
}

app.use(createPinia())
app.use(router)
app.use(ElementPlus)

app.mount('#app')