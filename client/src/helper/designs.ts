import dayjs from 'dayjs'
import relativeTime from 'dayjs/plugin/relativeTime'
import 'dayjs/locale/en'
import type { CartItem } from '@/types/order'

dayjs.extend(relativeTime)
dayjs.locale('en')

export const formatCurrency = (value: string) => {
    const numericValue = parseFloat(value)
    return numericValue.toLocaleString('en-US', { style: 'currency', currency: 'PHP' })
}

export const formatDate = (value: string) => {
    try {
        const date = new Date(value)
        const monthNames = [
            'January',
            'February',
            'March',
            'April',
            'May',
            'June',
            'July',
            'August',
            'September',
            'October',
            'November',
            'December',
        ]
        const day = date.getDate()
        const monthIndex = date.getMonth()
        const year = date.getFullYear()

        return `${monthNames[monthIndex]} ${day}, ${year}`
    } catch (error) {
        console.error('Error formatting date:', error)
        return value // Return the original value in case of an error
    }
}

export const formateNotificationTimeAgo = (date: string | Date) => {
    return dayjs(date).fromNow()
}

export const truncateNonDecimal = (value: number) => {
    const num = Number(value)

    if (Number.isNaN(num)) return value

    // If value is 1 or greater → no decimals
    if (Math.abs(num) >= 1) {
        return Math.trunc(num)
    }

    // If pure decimal → show decimals (trim trailing zeros)
    return num.toString().replace(/\.?0+$/, '')
}

export const getCartItemImageSrc = (item: CartItem) => {
    if (item.own_design_url) {
        return (item as any).own_design_temp_url ?? ''
    }

    const design = item.product.designs?.[0]
    if (design && typeof design === 'object' && 'business_design_temp_url' in design) {
        return (design as any).business_design_temp_url ?? ''
    }

    return ''
}
