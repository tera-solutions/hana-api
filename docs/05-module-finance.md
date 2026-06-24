# Module Finance

Module tài chính — quản lý hóa đơn, thanh toán, công nợ, khuyến mãi và ví điện tử.

**API Prefix:** `/api/v1/fin/`  
**Code:** `lib/app/Modules/Finance/`

---

## Sơ đồ quan hệ tài chính

```
Invoice (Hóa đơn)
    ├── InvoiceItem (Chi tiết hóa đơn)
    ├── Payment (Thanh toán)
    │       └── PaymentAllocation (Phân bổ thanh toán)
    ├── Debt (Công nợ phát sinh từ hóa đơn)
    └── Refund (Hoàn tiền)

Wallet (Ví cá nhân)
    └── WalletTransaction (Lịch sử giao dịch ví)

Promotion (Khuyến mãi)
    ├── PromotionRule (Điều kiện áp dụng)
    ├── PromotionReward (Phần thưởng)
    ├── PromotionUsage (Lịch sử sử dụng)
    ├── Voucher (Mã giảm giá)
    └── Referral (Chương trình giới thiệu)
```

---

## Sub-module: Invoice (Hóa đơn)

### Mục đích
Tạo và quản lý hóa đơn học phí — cả chiều thu (học viên trả tiền) và chiều chi (hoàn tiền, chi lương...).

### Luồng trạng thái hóa đơn

```
draft → pending_approval → approved → partially_paid / paid
                        ↓
                      denied
                        ↓ (sau approved)
                      cancelled
                        ↓
                      refunded
```

### Bảng dữ liệu

**`fin_invoices`**

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `invoice_code` | varchar | Mã hóa đơn |
| `type` | varchar | Loại: `receivable` (thu), `payable` (chi) |
| `partner_type` | varchar | Đối tác: `student`, `parent`, `teacher` |
| `partner_id` | bigint | ID đối tác |
| `amount` | decimal | Tổng tiền |
| `paid_amount` | decimal | Đã thanh toán |
| `remaining_amount` | decimal | Còn lại |
| `due_date` | date | Hạn thanh toán |
| `status` | varchar | Trạng thái |
| `note` | text | Ghi chú |

**`fin_invoice_items`** — Chi tiết từng dòng hóa đơn

| Cột | Mô tả |
|-----|-------|
| `invoice_id` | FK → fin_invoices |
| `name` | Tên mục (học phí, phí tài liệu...) |
| `quantity`, `unit_price` | Số lượng, đơn giá |
| `discount_amount` | Giảm giá |
| `total` | Thành tiền |

**`fin_invoice_histories`** — Lịch sử thay đổi trạng thái hóa đơn

### API Routes

| Method | Endpoint | Permission | Mô tả |
|--------|----------|-----------|-------|
| GET | `/fin/invoice/list` | `fin_invoice.list` | Danh sách hóa đơn |
| GET | `/fin/invoice/detail/{id}` | `fin_invoice.view` | Chi tiết hóa đơn |
| POST | `/fin/invoice/create` | `fin_invoice.create` | Tạo hóa đơn |
| PUT | `/fin/invoice/update/{id}` | `fin_invoice.update` | Cập nhật |
| POST | `/fin/invoice/approve/{id}` | `fin_invoice.approve` | Duyệt hóa đơn |
| POST | `/fin/invoice/deny/{id}` | `fin_invoice.approve` | Từ chối |
| POST | `/fin/invoice/cancel/{id}` | `fin_invoice.cancel` | Hủy hóa đơn |
| POST | `/fin/invoice/refund/{id}` | `fin_invoice.refund` | Hoàn tiền |
| POST | `/fin/invoice/payment/{id}` | `fin_invoice.pay` | Ghi nhận thanh toán |

---

## Sub-module: Payment (Thanh toán)

### Mục đích
Ghi nhận và quản lý các giao dịch thanh toán gắn với hóa đơn.

### Luồng trạng thái thanh toán

```
pending → confirmed → receipt_issued
        ↓
      cancelled
        ↓ (sau confirmed)
      refunded / reversed
```

### Bảng dữ liệu

**`fin_payments`**

| Cột | Mô tả |
|-----|-------|
| `payment_code` | Mã giao dịch |
| `invoice_id` | FK → fin_invoices |
| `amount` | Số tiền |
| `method` | Phương thức: `cash`, `bank_transfer`, `wallet`, `vnpay` |
| `direction` | `inbound` (thu) / `outbound` (chi) |
| `type` | Loại thanh toán |
| `status` | Trạng thái |
| `paid_at` | Thời điểm thanh toán |

**`fin_payment_allocations`** — Phân bổ thanh toán cho nhiều hóa đơn

**`fin_payment_histories`** — Lịch sử thay đổi trạng thái

**`fin_payment_logs`** — Log giao dịch với payment gateway

**`fin_refunds`** — Thông tin hoàn tiền

### API Routes

| Method | Endpoint | Permission | Mô tả |
|--------|----------|-----------|-------|
| GET | `/fin/payment/list` | `fin_payment.list` | Danh sách thanh toán |
| GET | `/fin/payment/detail/{id}` | `fin_payment.view` | Chi tiết |
| POST | `/fin/payment/create` | `fin_payment.create` | Tạo thanh toán |
| PUT | `/fin/payment/update/{id}` | `fin_payment.update` | Cập nhật |
| POST | `/fin/payment/confirm/{id}` | `fin_payment.confirm` | Xác nhận |
| POST | `/fin/payment/receipt/{id}` | `fin_payment.receipt` | Xuất biên lai |
| POST | `/fin/payment/cancel/{id}` | `fin_payment.cancel` | Hủy |
| POST | `/fin/payment/refund/{id}` | `fin_payment.refund` | Hoàn tiền |
| POST | `/fin/payment/reverse/{id}` | `fin_payment.reverse` | Đảo giao dịch |

---

## Sub-module: Debt (Công nợ)

### Mục đích
Theo dõi và quản lý công nợ phát sinh khi học viên/phụ huynh chưa thanh toán đủ hóa đơn.

### Luồng trạng thái

```
active → partially_collected → collected (đã thu đủ)
       ↓
     written_off (xóa nợ — cần duyệt)
```

### Bảng dữ liệu: `fin_debts`

| Cột | Mô tả |
|-----|-------|
| `invoice_id` | Hóa đơn phát sinh công nợ |
| `partner_id`, `partner_type` | Đối tượng nợ |
| `original_amount` | Số nợ gốc |
| `remaining_amount` | Còn phải trả |
| `due_date` | Hạn thanh toán |
| `status` | Trạng thái |

**`fin_debt_adjustments`** — Điều chỉnh công nợ (cộng/trừ thủ công)

### API Routes

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/fin/debt/list` | Danh sách công nợ |
| GET | `/fin/debt/detail/{id}` | Chi tiết công nợ |
| GET | `/fin/debt/dashboard` | Tổng quan công nợ |
| GET | `/fin/debt/aging` | Phân tích tuổi nợ (aging report) |
| POST | `/fin/debt/collect/{id}` | Thu nợ |
| POST | `/fin/debt/adjust/{id}` | Điều chỉnh nợ |
| POST | `/fin/debt/writeoff/{id}` | Xóa nợ |
| POST | `/fin/debt/approve-writeoff/{id}` | Duyệt xóa nợ |
| POST | `/fin/debt/deny-writeoff/{id}` | Từ chối xóa nợ |
| POST | `/fin/debt/reconcile/{id}` | Đối soát nợ |

---

## Sub-module: Promotion (Khuyến mãi)

### Mục đích
Quản lý chương trình khuyến mãi, voucher giảm giá và chương trình giới thiệu học viên mới.

### Loại khuyến mãi (`fin_promotions.type`)
- `discount` — Giảm giá trực tiếp trên hóa đơn
- `voucher` — Mã giảm giá
- `referral` — Giới thiệu học viên mới được hưởng ưu đãi

### Luồng trạng thái khuyến mãi

```
draft → active → paused → active → closed
```

### Bảng dữ liệu

**`fin_promotions`** — Chương trình khuyến mãi

| Cột | Mô tả |
|-----|-------|
| `name`, `code` | Tên và mã chương trình |
| `type` | Loại khuyến mãi |
| `discount_type` | `percentage` / `fixed_amount` |
| `discount_value` | Giá trị giảm |
| `start_date`, `end_date` | Thời gian áp dụng |
| `max_usage` | Giới hạn số lần dùng |
| `status` | Trạng thái |

**`fin_promotion_rules`** — Điều kiện áp dụng (số tiền tối thiểu, học viên mới...)

**`fin_promotion_rewards`** — Phần thưởng khi đạt điều kiện

**`fin_promotion_usages`** — Lịch sử sử dụng khuyến mãi

**`fin_vouchers`** — Mã voucher cụ thể

| Cột | Mô tả |
|-----|-------|
| `code` | Mã voucher |
| `promotion_id` | FK → fin_promotions |
| `status` | `available`, `used`, `expired` |
| `used_by` | Học viên sử dụng |
| `used_at` | Thời điểm sử dụng |

**`fin_referrals`** — Chương trình giới thiệu

### API Routes

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/fin/promotion/list` | Danh sách khuyến mãi |
| POST | `/fin/promotion/create` | Tạo khuyến mãi |
| POST | `/fin/promotion/activate/{id}` | Kích hoạt |
| POST | `/fin/promotion/pause/{id}` | Tạm dừng |
| POST | `/fin/promotion/close/{id}` | Đóng chương trình |
| POST | `/fin/promotion/generate-vouchers/{id}` | Tạo hàng loạt voucher |
| POST | `/fin/promotion/apply` | Áp dụng khuyến mãi vào hóa đơn |
| POST | `/fin/voucher/validate` | Kiểm tra mã voucher |
| GET | `/fin/referral/list` | Danh sách giới thiệu |
| POST | `/fin/referral/create` | Tạo giới thiệu |
| POST | `/fin/referral/reward/{id}` | Ghi nhận thưởng giới thiệu |

---

## Sub-module: Wallet (Ví cá nhân)

### Mục đích
Mỗi giáo viên/học viên có ví điện tử để nhận lương, nạp tiền học phí, rút tiền.

### Bảng dữ liệu

**`fin_wallets`**

| Cột | Mô tả |
|-----|-------|
| `owner_id`, `owner_type` | Chủ ví (teacher/student) |
| `balance` | Số dư hiện tại |
| `status` | `active` / `frozen` |

**`fin_wallet_transactions`** — Lịch sử giao dịch ví

| Cột | Mô tả |
|-----|-------|
| `wallet_id` | FK → fin_wallets |
| `type` | `deposit` (nạp), `withdrawal` (rút), `payment` (trả tiền), `salary` (nhận lương) |
| `amount` | Số tiền |
| `balance_before`, `balance_after` | Số dư trước/sau |
| `reference_id`, `reference_type` | Tham chiếu (invoice, payroll...) |
| `status` | `pending`, `completed`, `failed` |

### API Routes

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/fin/wallet/summary` | Số dư và tổng quan ví |
| GET | `/fin/wallet/transactions` | Lịch sử giao dịch |
| POST | `/fin/wallet/deposit` | Nạp tiền vào ví |
| POST | `/fin/wallet/withdraw` | Rút tiền từ ví |

---

## Sub-module: Account (Tài khoản kế toán)

### Mục đích
Quản lý tài khoản kế toán và tài khoản ngân hàng của trung tâm.

### Bảng dữ liệu

**`fin_accounts`** — Tài khoản kế toán (chart of accounts)

**`fin_bank_accounts`** — Tài khoản ngân hàng nhận chuyển khoản

### API Routes

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/fin/account/list` | Danh sách tài khoản |
| POST | `/fin/account/create` | Tạo tài khoản |
| PUT | `/fin/account/update/{id}` | Cập nhật |
