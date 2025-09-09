/* eslint-disable @typescript-eslint/no-explicit-any */
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

const echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
    wsPort: import.meta.env.VITE_REVERB_PORT || 8080,
    forceTLS: false,
    disableStats: true,
    encrypted: false,
    enabledTransports: ['ws', 'wss'],

    client: new Pusher(import.meta.env.VITE_REVERB_APP_KEY, {
        wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
        wsPort: import.meta.env.VITE_REVERB_PORT || 8080,
        forceTLS: false,
        enabledTransports: ['ws', 'wss'],
        cluster: import.meta.env.VITE_REVERB_APP_CLUSTER || 'mt1',
    }),

})


// Test connection events
echo.connector.pusher.connection.bind('connected', () => {
    console.log('✅ Echo connected successfully!')
})

echo.connector.pusher.connection.bind('disconnected', () => {
    console.log('❌ Echo disconnected')
})

echo.connector.pusher.connection.bind('error', (error: any) => {
    console.log('🚨 Echo connection error:', error)
})

export default echo
