<template>
  <div class="tags">
    <span v-for="t in tabs" :key="t.path" :class="['tag',active===t.path?'active':'']" @click="go(t.path)">
      {{t.title}}
      <span class="close" @click.stop="close(t.path)">×</span>
    </span>
  </div>
  
</template>
<script setup lang="ts">
import {useTabStore} from '@/store';
import {useRouter,useRoute,onBeforeRouteUpdate} from 'vue-router';
import {onMounted, watch} from 'vue';
const store=useTabStore();
const router=useRouter();
const route=useRoute();
function openByRoute(){ store.open((route.meta.title as string)||'页面', route.fullPath); }
onMounted(()=>{ store.restore(); openByRoute(); });
onBeforeRouteUpdate(()=>{ openByRoute(); });
const tabs=store.$state.tabs as any; const active=store.$state.active as any;
function go(p:string){ router.push(p); }
function close(p:string){ store.remove(p); if(store.active && store.active!==route.fullPath){ router.push(store.active); } }
watch(()=>route.fullPath,()=>openByRoute());
</script>
<style scoped>@import '@/styles.css';</style>


