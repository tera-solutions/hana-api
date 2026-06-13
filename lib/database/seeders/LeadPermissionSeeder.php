<?php

namespace Database\Seeders;

class LeadPermissionSeeder extends PermissionSeeder
{
    /**
     * Seed the CRM Lead management permissions (lead.md §2–§9).
     *
     * `crm_lead.view` also guards the nested guardian/student read endpoints and
     * `crm_lead.update` guards their writes (see CRM/Lead/Router/api.php).
     */
    public function run(): void
    {
        $this->seedPermissions('CRM', 'Lead', [
            'crm_lead.list' => 'Xem danh sách',
            'crm_lead.view' => 'Xem chi tiết',
            'crm_lead.create' => 'Tạo mới',
            'crm_lead.update' => 'Cập nhật',
            'crm_lead.suspend' => 'Ngừng khách hàng',
            'crm_lead.restore' => 'Khôi phục khách hàng',
        ]);
    }
}
