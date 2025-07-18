import '../css/tool.css'

Nova.booting(app => {
  console.log('Resource Drive Tool: Booting Vue component.');
  app.component('resource-file-manager', require('./components/Tool.vue').default)
})
