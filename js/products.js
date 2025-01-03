function formatPrice(price) {
    return '₹' + parseFloat(price).toFixed(2);
}

// Or using Intl.NumberFormat for proper Indian formatting
const formatPrice = (price) => {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(price);
}; 