
export const formatCurrency = (value: string) => {
    const numericValue = parseFloat(value);
    return numericValue.toLocaleString('en-US', { style: 'currency', currency: 'PHP' });
};


export const formatDate = (value: string) => {
    try {
        const date = new Date(value);
        const monthNames = ["January", "February", "March", "April", "May", "June",
            "July", "August", "September", "October", "November", "December"
        ];
        const day = date.getDate();
        const monthIndex = date.getMonth();
        const year = date.getFullYear();

        return `${monthNames[monthIndex]} ${day}, ${year}`;
    } catch (error) {
        console.error("Error formatting date:", error);
        return value; // Return the original value in case of an error
    }
};

