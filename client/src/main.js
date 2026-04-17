import { createApp } from 'vue'
import { createPinia } from 'pinia'
import Toast from 'vue-toastification'
import 'vue-toastification/dist/index.css'

import App    from './App.vue'
import router from './router'
import './assets/main.css'

/* FontAwesome */
import { library } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { 
  faChartLine, 
  faBook, 
  faTags, 
  faClipboardList, 
  faUsers, 
  faLink, 
  faPowerOff,
  faSearch,
  faUserCircle,
  faBell,
  faKey,
  faPlus,
  faEdit,
  faTrashAlt,
  faTimes,
  faChevronLeft,
  faChevronRight,
  faReply,
  faExclamationTriangle,
  faExclamationCircle,
  faCheckCircle,
  faBolt,
  faCirclePlus,
  faSync,
  faTicketAlt,
  faTimesCircle,
  faInbox,
  faCog,
  faFolderOpen,
  faCheck,
  faEnvelope,
  faLock,
  faEye,
  faEyeSlash,
  faBuilding
} from '@fortawesome/free-solid-svg-icons'

library.add(
  faChartLine, 
  faBook, 
  faTags, 
  faClipboardList, 
  faUsers, 
  faLink, 
  faPowerOff,
  faSearch,
  faUserCircle,
  faBell,
  faKey,
  faPlus,
  faEdit,
  faTrashAlt,
  faTimes,
  faChevronLeft,
  faChevronRight,
  faReply,
  faExclamationTriangle,
  faExclamationCircle,
  faCheckCircle,
  faBolt,
  faCirclePlus,
  faSync,
  faTicketAlt,
  faTimesCircle,
  faInbox,
  faCog,
  faFolderOpen,
  faCheck,
  faEnvelope,
  faLock,
  faEye,
  faEyeSlash,
  faBuilding
)

const app   = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)
app.component('font-awesome-icon', FontAwesomeIcon)
app.use(Toast, {
  position:        'top-right',
  timeout:         3500,
  closeOnClick:    true,
  pauseOnHover:    true,
  draggable:       true,
  hideProgressBar: false,
  toastClassName:  'biblioteca-toast',
})

app.mount('#app')
