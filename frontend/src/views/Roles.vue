<template>
  <div>
    <div style="display:flex;gap:8px;margin-bottom:12px">
      <el-input v-model="query.keyword" placeholder="搜索角色名" style="width:260px" clearable @keyup.enter="load"/>
      <el-button type="primary" @click="load">搜索</el-button>
      <el-button @click="reset">重置</el-button>
    </div>
    <el-table :data="items" border stripe size="small">
      <el-table-column prop="id" label="ID" width="90"/>
      <el-table-column prop="role_name" label="角色名" min-width="200"/>
      <el-table-column label="操作" width="220">
        <template #default="{row}">
          <el-button type="primary" text @click="openRights(row)">分配权限</el-button>
          <el-popconfirm title="确定删除该角色？" @confirm="del(row.id)">
            <template #reference>
              <el-button type="danger" text>删除</el-button>
            </template>
          </el-popconfirm>
        </template>
      </el-table-column>
    </el-table>
    <div style="margin-top:12px;text-align:right">
      <el-pagination background layout="prev, pager, next, ->, total" :total="total" :page-size="query.limit" :current-page="query.page" @current-change="(p:number)=>{query.page=p; load()}"/>
    </div>

    <el-dialog v-model="dlg.visible" :title="'分配权限 - '+(dlg.role?.role_name||'')" width="720px">
      <el-tree
        v-if="dlg.visible"
        ref="treeRef"
        :data="rightTree"
        node-key="id"
        :props="{label:'right_name', children:'children'}"
        show-checkbox
        default-expand-all
        style="max-height:420px;overflow:auto;border:1px solid #f0f0f0;padding:8px;border-radius:6px"
      />
      <template #footer>
        <el-button @click="dlg.visible=false">取消</el-button>
        <el-button type="primary" :loading="dlg.saving" @click="saveRights">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>
<script setup lang="ts">
import {ref, onMounted} from 'vue';
import http from '@/api/http';
import type { ElTree } from 'element-plus';

interface Role { id:number; role_name:string }
interface RightNode { id:number; right_name:string; children?:RightNode[] }

const items = ref<Role[]>([]);
const total = ref(0);
const query = ref({ page:1, limit:10, keyword:'' });

async function load(){
  const params:any = { page: query.value.page, limit: query.value.limit };
  if(query.value.keyword){ params.keyword = query.value.keyword; }
  const res = await http.get('/api/roles', { params });
  const list = res.data?.data?.items || [];
  items.value = list;
  total.value = res.data?.data?.total || list.length;
}
function reset(){ query.value={ page:1, limit:10, keyword:'' }; load(); }
async function del(id:number){ await http.delete(`/api/roles/${id}`); await load(); }

// 分配权限
const dlg = ref<{visible:boolean; role:Role|null; saving:boolean}>({visible:false, role:null, saving:false});
const rightTree = ref<RightNode[]>([]);
const treeRef = ref<InstanceType<typeof ElTree>>();

async function openRights(role:Role){
  dlg.value={visible:true, role, saving:false};
  const [treeRes, rightsRes] = await Promise.all([
    http.get('/api/permissions/tree'),
    http.get(`/api/roles/${role.id}/rights`)
  ]);
  rightTree.value = treeRes.data?.data || [];
  const owned:number[] = (rightsRes.data?.data||[]).map((r:any)=>r.id);
  // 异步渲染后设置选中
  setTimeout(()=>{ treeRef.value?.setCheckedKeys(owned); }, 0);
}

async function saveRights(){
  if(!dlg.value.role) return;
  dlg.value.saving = true;
  const ids = (treeRef.value?.getCheckedKeys(false) as number[]) || [];
  await http.post(`/api/roles/${dlg.value.role.id}/rights`, { right_ids: ids });
  dlg.value.saving = false;
  dlg.value.visible = false;
}

onMounted(load);
</script>


