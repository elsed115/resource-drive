import '../css/tool.css'

import Tool from './components/Tool.vue'

Nova.booting(app => {
  console.log('Resource Drive Tool: Booting Vue component.');
  app.component('resource-file-manager', Tool)
})
