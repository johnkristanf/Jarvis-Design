
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
