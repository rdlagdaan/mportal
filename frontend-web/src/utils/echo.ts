import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
;(window as any).Pusher = Pusher

const scheme = 'http'
const host   = 'localhost'
const port   = 9090

const echo = new Echo({
  broadcaster: 'reverb',
  key: '89mu6mnhw0lbqfjcmqbt',
  wsHost: host,
  wsPort: port,
  wssPort: port,
  forceTLS: false,
  enabledTransports: ['ws'],
  // wsPath: '/app',   <-- REMOVE THIS LINE
  authEndpoint: '/app/api/broadcasting/auth',
  withCredentials: true,
})

;(window as any).Echo = echo
console.log('[Echo] target →', `ws://${host}:${port}/app/<APP_KEY>`)
