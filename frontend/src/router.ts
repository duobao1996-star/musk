import {createRouter,createWebHistory,RouteRecordRaw} from 'vue-router';
import Login from './views/Login.vue';
import Dashboard from './views/Dashboard.vue';
import MainLayout from './layouts/MainLayout.vue';
import Permissions from './views/Permissions.vue';
import Roles from './views/Roles.vue';
import OperationLogs from './views/OperationLogs.vue';
export const routes:RouteRecordRaw[]=[
  {path:'/login',component:Login,meta:{title:'登录'}},
  {
    path:'/',
    component: MainLayout,
    children:[
      {path:'',redirect:'/dashboard'},
      {path:'dashboard',component:Dashboard,meta:{title:'仪表盘',auth:true}},
      {path:'permissions',component:Permissions,meta:{title:'权限管理',auth:true}},
      {path:'roles',component:Roles,meta:{title:'角色管理',auth:true}},
      {path:'operation-logs',component:OperationLogs,meta:{title:'操作日志',auth:true}},
    ]
  }
];
const router=createRouter({history:createWebHistory(),routes});
router.beforeEach((to, _from, next)=>{
  if(to.path !== '/login' && to.meta?.auth){
    const token = localStorage.getItem('token');
    if(!token){ return next('/login'); }
  }
  document.title = (to.meta?.title as string) || 'Webman Admin';
  next();
});
export default router;