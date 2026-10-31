import { createApp } from 'vue'
import '../css/app.css'
import ForgeWizard from './components/ForgeWizard.vue'

const el = document.getElementById('forge-app')
if (el) {
  const csrf = el.dataset.csrf || ''
  createApp(ForgeWizard, { csrfToken: csrf }).mount(el)
}
