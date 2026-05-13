import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useCartStore } from '@/stores/cart.store'
import { orderService } from '@/services/order.service'
import { couponService } from '@/services/coupon.service'
import type { CouponValidation, PublicCoupon } from '@/services/coupon.service'

export function useCheckout() {
  const router = useRouter()
  const toast = useToast()
  const cartStore = useCartStore()

  const paymentMethod = ref('vnpay')
  const isProcessing = ref(false)
  const errorMessage = ref('')

  const couponCode = ref('')
  const couponError = ref('')
  const isValidating = ref(false)
  const appliedCoupon = ref<CouponValidation | null>(null)

  const showAvailableCoupons = ref(false)
  const availableCoupons = ref<PublicCoupon[]>([])
  const loadingAvailable = ref(false)
  const availableFetched = ref(false)

  const finalTotal = computed(() => {
    const discount = appliedCoupon.value?.discount_amount ?? 0
    return Math.max(0, cartStore.total - discount)
  })

  async function fetchAvailableCoupons() {
    if (availableFetched.value) return
    loadingAvailable.value = true
    try {
      const res = await couponService.getAvailable()
      availableCoupons.value = res.data.data
      availableFetched.value = true
    } catch {
      availableCoupons.value = []
    } finally {
      loadingAvailable.value = false
    }
  }

  async function toggleAvailableCoupons() {
    showAvailableCoupons.value = !showAvailableCoupons.value
    if (showAvailableCoupons.value) {
      await fetchAvailableCoupons()
    }
  }

  function selectAvailableCoupon(code: string) {
    couponCode.value = code
    showAvailableCoupons.value = false
    couponError.value = ''
  }

  async function applyCoupon() {
    if (!couponCode.value.trim() || isValidating.value) return
    isValidating.value = true
    couponError.value = ''
    try {
      const res = await couponService.validate({
        code: couponCode.value.trim().toUpperCase(),
        subtotal: Number(cartStore.total),
      })
      appliedCoupon.value = res.data.data
      toast.success('Áp dụng mã giảm giá thành công!')
    } catch (err) {
      const data = (
        err as {
          response?: {
            data?: { message?: string; errors?: { code?: string | string[]; subtotal?: unknown } }
          }
        }
      ).response?.data
      if (data?.errors?.code) {
        couponError.value = Array.isArray(data.errors.code) ? data.errors.code[0] : data.errors.code
      } else if (data?.errors?.subtotal) {
        couponError.value = 'Giá trị đơn hàng không hợp lệ.'
      } else {
        couponError.value = data?.message ?? 'Mã giảm giá không hợp lệ.'
      }
      appliedCoupon.value = null
    } finally {
      isValidating.value = false
    }
  }

  function removeCoupon() {
    appliedCoupon.value = null
    couponCode.value = ''
    couponError.value = ''
  }

  async function handleCheckout() {
    if (cartStore.count === 0) return
    isProcessing.value = true
    errorMessage.value = ''
    try {
      const payload: Record<string, unknown> = { course_ids: cartStore.courseIds }
      if (appliedCoupon.value) {
        payload.coupon_code = appliedCoupon.value.code
      }
      const res = await orderService.createOrder(payload)
      const { payment_url, order } = res.data.data
      if (payment_url) {
        cartStore.setPendingOrder(order.order_code)
        window.location.href = payment_url
      } else {
        cartStore.clear()
        toast.success('Đơn hàng miễn phí đã được xử lý thành công!')
        router.push(
          `/payment/result?order_code=${order.order_code}&status=success&message=Đăng+ký+thành+công`,
        )
      }
    } catch (err: unknown) {
      const data = (
        err as {
          response?: {
            data?: {
              message?: string
              errors?: { course_ids?: string | string[]; coupon_code?: string | string[] }
            }
          }
        }
      ).response?.data
      if (data?.errors?.course_ids) {
        errorMessage.value = Array.isArray(data.errors.course_ids)
          ? data.errors.course_ids[0]
          : data.errors.course_ids
      } else if (data?.errors?.coupon_code) {
        errorMessage.value = Array.isArray(data.errors.coupon_code)
          ? data.errors.coupon_code[0]
          : data.errors.coupon_code
      } else {
        errorMessage.value = data?.message ?? 'Có lỗi xảy ra khi tạo đơn hàng.'
      }
      toast.error(errorMessage.value)
    } finally {
      isProcessing.value = false
    }
  }

  return {
    paymentMethod,
    isProcessing,
    errorMessage,
    couponCode,
    couponError,
    isValidating,
    appliedCoupon,
    showAvailableCoupons,
    availableCoupons,
    loadingAvailable,
    finalTotal,
    toggleAvailableCoupons,
    selectAvailableCoupon,
    applyCoupon,
    removeCoupon,
    handleCheckout,
  }
}
