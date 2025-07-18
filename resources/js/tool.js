
import '../css/tool.css'

import Tool from './components/Tool.vue'

Nova.booting(app => {
  app.component('resource-file-manager', Tool)
})
