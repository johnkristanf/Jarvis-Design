/* eslint-disable @typescript-eslint/no-explicit-any */
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

const echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
    // client: new Pusher(import.meta.env.VITE_PUSHER_APP_KEY, {
    //     wsHost: import.meta.env.VITE_REVERB_HOST ,
    //     wsPort: import.meta.env.VITE_REVERB_PORT ,
    //     forceTLS: true,
    //     enabledTransports: ['ws', 'wss'],
    //     cluster: import.meta.env.VITE_REVERB_APP_CLUSTER || 'mt1',
    // }),

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
