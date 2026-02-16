import { createApp } from 'vue'
import CalendarResourceAdmin from './CalendarResourceAdmin.vue'

const app = createApp(CalendarResourceAdmin)
app.mixin({ methods: { t, n } })
app.mount('#calendar-resource-admin')