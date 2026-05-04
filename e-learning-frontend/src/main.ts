import './assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'
import Toast from 'vue-toastification'
import 'vue-toastification/dist/index.css'
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css'

import { permissionDirective } from './directives/permission'

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.component('QuillEditor', QuillEditor)

app.use(Toast, {
  position: 'top-right',
  timeout: 3000,
  closeOnClick: true,
})

app.directive('permission', permissionDirective)

app.mount('#app')
