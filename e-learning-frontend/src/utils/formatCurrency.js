export const formatCurrency = (amount) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount)
}

export const formatPrice = (price, salePrice) => {
  if (!price && !salePrice) return { display: 'Miễn phí', hasDiscount: false, isFree: true }
  
  const currentPrice = salePrice ?? price
  const hasDiscount = salePrice !== null && salePrice < price

  return {
    display: formatCurrency(currentPrice),
    original: formatCurrency(price),
    hasDiscount,
    isFree: currentPrice === 0
  }
}
