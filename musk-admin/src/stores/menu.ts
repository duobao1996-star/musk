import { defineStore } from 'pinia'
import { ref } from 'vue'
import { getMenuPermissions, type Permission } from '@/api/permission'

export interface MenuItem {
  id: number
  title: string
  path: string
  icon: string
  children?: MenuItem[]
}

export const useMenuStore = defineStore('menu', () => {
  const menuList = ref<MenuItem[]>([])
  const loading = ref(false)

  // 获取菜单数据
  const getMenuList = async () => {
    try {
      loading.value = true
      const response = await getMenuPermissions()
      
      // 转换为菜单格式 - 显示所有菜单项
      menuList.value = response.data.data
        .filter((item: Permission) => item.menu === 1)
        .map((item: Permission) => ({
          id: item.id,
          title: item.description,
          path: item.path,
          icon: item.icon || 'ri:file-list-line'
        }))
        .sort((a, b) => a.id - b.id)
      
      console.log('菜单数据:', menuList.value)
      console.log('菜单数量:', menuList.value.length)
      return menuList.value
    } catch (error) {
      console.error('获取菜单失败:', error)
      return []
    } finally {
      loading.value = false
    }
  }

  // 清空菜单
  const clearMenuList = () => {
    menuList.value = []
  }

  return {
    menuList,
    loading,
    getMenuList,
    clearMenuList
  }
})
