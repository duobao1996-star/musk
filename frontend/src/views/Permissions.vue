<template>
  <div>
    <div style="display:flex;gap:8px;margin-bottom:12px">
      <el-input v-model="query.keyword" placeholder="搜索名称/路径" style="width:260px" clearable @keyup.enter="load"/>
      <el-button type="primary" @click="load">搜索</el-button>
      <el-button @click="reset">重置</el-button>
    </div>
    <el-table :data="items" border stripe size="small">
      <el-table-column prop="id" label="ID" width="80"/>
      <el-table-column prop="right_name" label="权限名" min-width="180"/>
      <el-table-column prop="path" label="路径" min-width="220"/>
      <el-table-column prop="method" label="方法" width="90"/>
      <el-table-column prop="description" label="描述" min-width="200"/>
      <el-table-column label="操作" width="120">
        <template #default="{row}">
          <el-popconfirm title="确定删除？" @confirm="del(row.id)">
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
  </div>
</template>
<script setup lang="ts">
import {ref, onMounted} from 'vue';
import http from '@/api/http';

interface PermItem { id:number; right_name:string; path:string; method:string; description:string }
const items = ref<PermItem[]>([]);
const total = ref(0);
const query = ref({ page:1, limit:10, keyword:'' });

async function load(){
  const params:any = { page: query.value.page, limit: query.value.limit };
  if(query.value.keyword){ params.keyword = query.value.keyword; }
  const res = await http.get('/api/permissions', { params });
  const list = res.data?.data?.items || [];
  items.value = list;
  total.value = res.data?.data?.total || list.length;
}
function reset(){ query.value={ page:1, limit:10, keyword:'' }; load(); }
async function del(id:number){ await http.delete(`/api/permissions/${id}`); await load(); }
onMounted(load);
</script>


