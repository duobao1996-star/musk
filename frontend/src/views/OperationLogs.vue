<template>
  <div>
    <div style="display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap">
      <el-input v-model="query.keyword" placeholder="操作名/URL" style="width:260px" clearable @keyup.enter="load"/>
      <el-select v-model="query.status" placeholder="状态" style="width:140px" clearable>
        <el-option :value="200" label="成功(200)"/>
        <el-option :value="400" label="失败(400)"/>
        <el-option :value="500" label="异常(500)"/>
      </el-select>
      <el-button type="primary" @click="load">搜索</el-button>
      <el-button @click="reset">重置</el-button>
    </div>
    <el-table :data="items" border stripe size="small">
      <el-table-column prop="id" label="ID" width="80"/>
      <el-table-column prop="operation_desc" label="操作名" min-width="200"/>
      <el-table-column prop="method" label="方法" width="90"/>
      <el-table-column prop="url" label="URL" min-width="240"/>
      <el-table-column prop="status" label="状态" width="90"/>
      <el-table-column prop="operation_time" label="时间" width="180"/>
    </el-table>
    <div style="margin-top:12px;text-align:right">
      <el-pagination background layout="prev, pager, next, ->, total" :total="total" :page-size="query.limit" :current-page="query.page" @current-change="(p:number)=>{query.page=p; load()}"/>
    </div>
  </div>
</template>
<script setup lang="ts">
import {ref, onMounted} from 'vue';
import http from '@/api/http';

interface LogItem { id:number; operation_desc:string; method:string; url:string; status:number; operation_time:string }
const items = ref<LogItem[]>([]);
const total = ref(0);
const query = ref<{page:number;limit:number;keyword?:string;status?:number|undefined}>({ page:1, limit:10, keyword:'', status:undefined });

async function load(){
  const params:any = { page: query.value.page, limit: query.value.limit };
  if(query.value.keyword){ params.keyword = query.value.keyword; }
  if(query.value.status){ params.status = query.value.status; }
  const res = await http.get('/api/operation-logs', { params });
  const list = res.data?.data?.items || [];
  items.value = list;
  total.value = res.data?.data?.total || list.length;
}
function reset(){ query.value={ page:1, limit:10, keyword:'', status:undefined }; load(); }
onMounted(load);
</script>


