import {createRouter,createWebHistory,RouteRecordRaw} from 'vue-router';
import Login from './views/Login.vue';
import Dashboard from './views/Dashboard.vue';
export const routes:RouteRecordRaw[]=[
  {path:'/login',component:Login,meta:{title:'登录'}},
  {path:'/',redirect:'/dashboard'},
  {path:'/dashboard',component:Dashboard,meta:{title:'仪表盘',auth:true}}
];
const router=createRouter({history:createWebHistory(),routes});
export default router;