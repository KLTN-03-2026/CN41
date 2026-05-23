# Sơ đồ Lớp — Payment Domain

```mermaid
classDiagram
    direction TB

    class Model {
        <<abstract>>
        +int id
    }

    class Order {
        +string order_code
        +int student_id
        +decimal subtotal
        +decimal discount_amount
        +decimal total_amount
        +string coupon_code
        +string status
        +string payment_method
        +timestamp paid_at
        +timestamp deleted_at
        +student() BelongsTo
        +items() HasMany
        +transactions() HasMany
        +scopePaid(query) Builder
        +scopePending(query) Builder
        +scopeFailed(query) Builder
        +isPaid() bool
        +isPending() bool
        +isFailed() bool
    }

    class OrderItem {
        +int order_id
        +int course_id
        +decimal price
        +decimal sale_price
        +decimal final_price
        +order() BelongsTo
        +course() BelongsTo
    }

    class Transaction {
        +int order_id
        +string gateway
        +string transaction_code
        +string bank_code
        +string card_type
        +decimal amount
        +string status
        +json gateway_response
        +string response_code
        +timestamp paid_at
        +order() BelongsTo
        +isSuccess() bool
        +isPending() bool
    }

    class Coupon {
        +string code
        +string type
        +decimal value
        +decimal min_order_value
        +decimal max_discount
        +int usage_limit
        +int used_count
        +timestamp start_date
        +timestamp end_date
        +int status
        +timestamp deleted_at
        +scopeActive(query) Builder
        +scopeValid(query) Builder
        +isValid() bool
        +calculateDiscount(amount) decimal
    }

    class VnpayService {
        <<Service>>
        +createPaymentUrl(order) string
        +handleIpn(params) array
        +verifyChecksum(params, secret) bool
        +enrollStudent(order) void
    }

    class Student {
        +string name
        +orders() HasMany
    }

    class Course {
        +string name
        +decimal price
    }

    Model <|-- Order
    Model <|-- OrderItem
    Model <|-- Transaction
    Model <|-- Coupon

    Student "1" --> "N" Order : student_id
    Order "1" --> "N" OrderItem : order_id
    OrderItem "N" --> "1" Course : course_id
    Order "1" --> "N" Transaction : order_id
    VnpayService --> Order : cập nhật trạng thái
    VnpayService --> Transaction : tạo giao dịch
