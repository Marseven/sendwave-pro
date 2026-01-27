import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import { registerPermissionDirectives } from './directives/permission'
import './index.css'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

// Register permission directives (v-can, v-role)
registerPermissionDirectives(app)

app.mount('#root')
