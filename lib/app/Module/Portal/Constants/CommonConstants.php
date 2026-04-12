<?php

namespace App\Module\Portal\Constants;

/**
 * Class TransactionTypeConstant.
 */
final class CommonConstants
{
    public const ACTION_TYPE_TEXT = [
        "created" => "Tạo mới",
        "edited" => "Chỉnh sửa",
        "deleted" => "Xóa",
        "approve" => "Duyệt",
        "send_request" => "Gửi yêu cầu duyệt",
        "reject" => "Từ chối",
        "unapprove" => "Hủy yêu cầu duyệt",
        "edited_price" => "Chỉnh sửa giá",
        "change_status" => "Cập nhật trạng thái",
        "processing" => "Xử lý",
        "received" => "Đã nhận",
        "complete" => "Hoàn thành",
        "change_implement" => "Bàn giao công việc",
        "uploaded" => "Tải lên tệp"
    ];

    public const OBJECT_TEXT = [
        "crm_supplier" => "Nhà cung cấp",
        "crm_customer" => "Khách hàng",
        "crm_consulting_ticket" => "Thẻ tư vấn",
        "crm_product" => "Sản phẩm",
        "crm_category" => "Danh mục",
        "crm_brand" => "Nhãn hiệu",
        "crm_unit" => "Đơn vị",
        "crm_purchase_order" => "Đơn mua hàng",
        "crm_purchase_request" => "Yêu Cầu mua hàng",
        "crm_purchase_order_return" => "Yêu cầu trả hàng mua",
        "crm_sale_order" => "Đơn hàng bán",
        "crm_sale_order_return" => "Yêu cầu trả hàng bán",
        "crm_quote" => "Đơn Báo giá",
        "crm_sell_delivery" => "Giao hàng xuất kho",
        "crm_purchase_delivery" => "Giao hàng nhập kho",
        "crm_inbound_inspection" => "Kiểm hàng nhập kho",
        "crm_outbound_inspection" => "Kiểm hàng xuất kho"
    ];

    public const STATUS_BUSINESS = [
        "no_activated" => "Chưa kích hoạt",
        "waiting_for_activation" => "Chờ kích hoạt",
        "is_active" => "Đang hoạt động",
        "expired" => "Hết hạn",
        "cancelled" => "Đã hủy"
    ];

    public const STATUS_ACCOUNT = [
        "is_active" => "Hoạt động",
        "expired" => "Hết hạn",
        "cancelled" => "Đã hủy"
    ];

    public const TYPE_ACCOUNT = [
        "individual" => "Cá nhân",
        "owner" => "Chủ sở hữu",
        "member" => "Thành viên"
    ];

    public const PAYMENT_METHOD = [
        "cash" => "Tiền mặt",
        "transfer" => "Chuyển khoản"
    ];

    public const EMPLOYEE_SIZE = [
        "less_than_fifty" => "< 50 nhân viên",
        "fifty" => "50 nhân viên",
        "fifty_one_to_one_hundred" => "51 đến 100 nhân viên",
        "one_hundred_and_one_to_two_hundred" => "101 đến 200 nhân viên",
        "over_five_hundred" => "Trên 500 nhân viên"
    ];


    public const STATUS_SERVICE_BUSINESS = [
        'not_activated' => 'Chưa kích hoạt',
        'is_active' => 'Hoạt động',
        'cancel_activated' => 'Hủy kích hoạt',
        'expired' => 'Đã hết hạn',
        'finished' => 'Đã kết thúc'
    ];

    public const STATUS_BILL = [
        'unpaid' => 'Chưa thanh toán',
        'paid' => 'Đã thanh toán',
        'in_process' => 'Đang tiến hành',
        'complete' => 'Hoàn thành',
        'fail' => 'Thất bại'
    ];

    public const METHOD_PAYMENT = [
        1 => "Chuyển khoản",
        2 => "Ví điện tử",
        3 => "Ví Tera"
    ];

    public const BALLOT_TYPE = [
        "collect_money_customer" => "Thu tiền khách hàng",
        "spend_money_customer" => "Trả tiền khách hàng",
        "release_fund" => "Xuất quỹ",
        "pay_fund" => "Nộp quỹ",
        "other_pay" => "Chi khác",
        "other_revenue" => "Thu khác"
    ];
}
