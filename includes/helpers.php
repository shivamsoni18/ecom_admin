function formatPrice($price) {
    return '₹' . number_format($price, 2);
}

// Then use it throughout your application:
echo formatPrice($product->price); 