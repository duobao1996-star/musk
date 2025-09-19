import {defineStore} from 'pinia';
export interface Tab{ title:string; path:string; }
export const useTabStore=defineStore('tabs',{
  state:()=>({ tabs:[] as Tab[], active:'' }),
  actions:{
    open(title:string,path:string){
      const i=this.tabs.findIndex(t=>t.path===path);
      if(i===-1){ this.tabs.push({title,path}); }
      this.active=path;
      localStorage.setItem('tabs',JSON.stringify(this.tabs));
      localStorage.setItem('active',this.active);
    },
    remove(path:string){
      const i=this.tabs.findIndex(t=>t.path===path);
      if(i>-1){ this.tabs.splice(i,1); }
      if(this.active===path && this.tabs.length){ this.active=this.tabs[this.tabs.length-1].path; }
      localStorage.setItem('tabs',JSON.stringify(this.tabs));
      localStorage.setItem('active',this.active);
    },
    restore(){
      try{
        const t=JSON.parse(localStorage.getItem('tabs')||'[]');
        const a=localStorage.getItem('active')||'';
        this.tabs=t; this.active=a;
      }catch{}
    }
  }
});