import type { PaymentStatus } from '@/types/payment'

export const getStatusBadgeClass = (status: PaymentStatus) => {
    switch (status) {
        case 'in_review':
            return 'bg-yellow-100 text-yellow-800 border-yellow-200'
        case 'partially_paid':
            return 'bg-gray-100 text-gray-800 border-gray-200'
        case 'fully_paid':
            return 'bg-green-100 text-green-800 border-green-200'
        default:
            return 'bg-red-100 text-red-800 border-red-200'
    }
}

export const getStatusLabel = (status: string) => {
    return status.replace('_', ' ').toUpperCase()
}

export const formatDateWithTime = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}

export const isValidCssColor = (value: string) => {
    if (!value) return false
    return typeof CSS !== 'undefined' && CSS.supports?.('color', value)
}
