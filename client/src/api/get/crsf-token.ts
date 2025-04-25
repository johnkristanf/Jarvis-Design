import axios from 'axios'

export const getCsrfToken = async () => {
    try {
        await axios.get(`${import.meta.env.VITE_API_URL}/sanctum/csrf-cookie`, {
            withCredentials: true,
        })
        console.log('CSRF token set successfully')
    } catch (error) {
        console.error('Error setting CSRF token:', error)
    }
}
