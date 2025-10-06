/* eslint-disable @typescript-eslint/no-explicit-any */
import { apiService } from '@/api/axios'
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

declare global {
    interface Window {
        Pusher: typeof Pusher
    }
}

window.Pusher = Pusher

export const initializeEcho = (): Echo<'pusher'> => {
    const echo = new Echo({
        broadcaster: 'pusher',
        key: import.meta.env.VITE_PUSHER_APP_KEY,
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
        forceTLS: false,
        authorizer: (channel: { name: any }) => {
            return {
                authorize: async (socketId: any, callback: (arg0: unknown, arg1: unknown) => void) => {
                    try {
                        const response = await apiService.post('/broadcasting/auth', {
                            socket_id: socketId,
                            channel_name: channel.name,
                        })

                        callback(null, response)
                    } catch (error) {
                        console.error('Broadcasting auth error:', error)
                        callback(error, null)
                    }
                },
            }
        },
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

    return echo
}
