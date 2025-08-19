export const getStatusBadgeClass = (status: string) => {
    const baseClasses = 'px-2 py-1 rounded-full text-xs font-medium border'
    switch (status.toLowerCase()) {
        case 'pending':
            return `${baseClasses} bg-gray-100 text-gray-800 border-gray-300`
        case 'processing':
            return `${baseClasses} bg-gray-800 text-white border-gray-700`
        case 'completed':
            return `${baseClasses} bg-black text-white border-black`
        case 'cancelled':
            return `${baseClasses} bg-white text-black border-2 border-black`
        default:
            return `${baseClasses} bg-gray-100 text-gray-800 border-gray-300`
    }
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
