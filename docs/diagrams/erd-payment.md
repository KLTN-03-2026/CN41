# ERD — Payment Domain

Bao gồm: đơn hàng, chi tiết đơn hàng, giao dịch VNPAY, mã giảm giá.

```mermaid
erDiagram
    students {
        bigint id PK
        varchar name
        varchar email
    }

    courses {
        bigint id PK
        varchar name
        decimal price
        decimal sale_price
    }

    coupons {
        bigint id PK
        varchar code UK
        enum type "fixed|percentage"
        decimal value "giá trị giảm"
        decimal min_order_value "giá trị đơn hàng tối thiểu"
        decimal max_discount "giảm tối đa (cho percentage)"
        int usage_limit "0 = không giới hạn"
        int used_count
        timestamp start_date
        timestamp end_date
        tinyint status "0=inactive 1=active"
        text description
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    orders {
        bigint id PK
        varchar order_code UK
        bigint student_id FK
        decimal subtotal "tổng trước giảm giá"
        decimal discount_amount "số tiền giảm"
        decimal total_amount "tổng sau giảm giá"
        varchar coupon_code "lưu lại mã đã dùng"
        enum status "pending|paid|failed|cancelled|refunded"
        varchar payment_method "vnpay|..."
        text note
        timestamp paid_at
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    order_items {
        bigint id PK
        bigint order_id FK
        bigint course_id FK
        decimal price "giá gốc lúc mua"
        decimal sale_price "giá sale lúc mua"
        decimal final_price "giá thực tế đã thanh toán"
        timestamp created_at
        timestamp updated_at
    }

    transactions {
        bigint id PK
        bigint order_id FK
        varchar gateway "vnpay"
        varchar transaction_code "mã giao dịch từ cổng"
        varchar bank_code "mã ngân hàng"
        varchar card_type "ATM | QRCODE | ..."
        decimal amount
        enum status "pending|success|failed"
        json gateway_response "toàn bộ response từ VNPAY"
        varchar response_code "mã kết quả VNPAY"
        timestamp paid_at
        timestamp created_at
        timestamp updated_at
    }

    students ||--o{ orders : "đặt hàng"
    orders ||--|{ order_items : "chứa"
    order_items }|--|| courses : "khoá học mua"
    orders ||--o{ transactions : "giao dịch thanh toán"
