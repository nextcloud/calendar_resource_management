import { createApp } from 'vue'
import CalendarResourceAdmin from './CalendarResourceAdmin.vue'

// Wrap t and n to auto-inject app name
const appName = 'calendar_resource_management'
const wrapT = (text, vars) => t(appName, text, vars)
const wrapN = (textSingular, textPlural, count, vars) => n(appName, textSingular, textPlural, count, vars)

const app = createApp(CalendarResourceAdmin)
app.mixin({ methods: { t: wrapT, n: wrapN } })
app.mount('#calendar-resource-admin')