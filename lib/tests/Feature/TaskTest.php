<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\TaskPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TaskPermissionSeeder::class);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Liên hệ phụ huynh',
            'category' => 'finance',
            'priority' => 'high',
            'start_date' => now()->toDateString(),
            'due_date' => now()->addDays(3)->toDateString(),
        ], $overrides);
    }

    private function createTask(array $overrides = []): int
    {
        return $this->postJson('/v1/sys/task/create', $this->payload($overrides))->json('data.id');
    }

    private function uploadMediaId(): int
    {
        return DB::table('media')->insertGetId([
            'file_path' => 'storage/uploads/doc_'.uniqid().'.pdf',
            'file_name' => 'doc.pdf',
            'file_type' => 'application/pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/v1/sys/task/list')->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);

        $this->getJson('/v1/sys/task/list')->assertJsonPath('code', 403);
    }

    public function test_create_starts_as_draft_with_code(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/sys/task/create', $this->payload())
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.task_code', 'TASK000001')
            ->assertJsonPath('data.progress', 0);
    }

    public function test_create_rejects_due_before_start(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/sys/task/create', $this->payload([
            'start_date' => now()->addDays(5)->toDateString(),
            'due_date' => now()->addDays(2)->toDateString(),
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['due_date']);
    }

    public function test_list_filters_by_status(): void
    {
        $this->actingAsAdmin();

        $this->createTask();
        $id = $this->createTask();
        $this->putJson("/v1/sys/task/update/{$id}", ['status' => 'open']);

        $this->getJson('/v1/sys/task/list?status=open')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.status', 'open');
    }

    public function test_update_changes_fields_and_delete_soft_deletes(): void
    {
        $this->actingAsAdmin();

        $id = $this->createTask();

        $this->putJson("/v1/sys/task/update/{$id}", ['priority' => 'urgent', 'progress' => 50])
            ->assertStatus(200)
            ->assertJsonPath('data.priority', 'urgent')
            ->assertJsonPath('data.progress', 50);

        $this->deleteJson("/v1/sys/task/delete/{$id}")->assertStatus(200)->assertJsonPath('success', true);
        $this->assertSoftDeleted('task_tasks', ['id' => $id]);
    }

    public function test_cannot_pending_review_below_full_progress(): void
    {
        $this->actingAsAdmin();
        $id = $this->createTask();

        // BR-03
        $this->putJson("/v1/sys/task/update/{$id}", ['status' => 'pending_review', 'progress' => 80])
            ->assertJsonPath('success', false)
            ->assertJsonPath('msg', 'Không thể chuyển sang chờ duyệt khi tiến độ chưa đạt 100%.');
    }

    public function test_cannot_complete_with_incomplete_checklist(): void
    {
        $this->actingAsAdmin();
        $id = $this->createTask();

        $this->postJson("/v1/sys/task/{$id}/checklist/create", ['title' => 'Chuẩn bị giáo án'])->assertStatus(200);
        $this->putJson("/v1/sys/task/update/{$id}", ['progress' => 100]);

        // BR-02
        $this->putJson("/v1/sys/task/update/{$id}", ['status' => 'completed'])
            ->assertJsonPath('success', false)
            ->assertJsonPath('msg', 'Không thể hoàn thành khi checklist chưa hoàn tất.');
    }

    public function test_complete_succeeds_after_checklist_done(): void
    {
        $this->actingAsAdmin();
        $id = $this->createTask();

        $itemId = $this->postJson("/v1/sys/task/{$id}/checklist/create", ['title' => 'Xong việc'])->json('data.id');
        $this->putJson("/v1/sys/task/checklist/update/{$itemId}", ['is_completed' => true])
            ->assertStatus(200)
            ->assertJsonPath('data.is_completed', true);

        $this->putJson("/v1/sys/task/update/{$id}", ['progress' => 100, 'status' => 'completed'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.progress', 100);
    }

    public function test_checklist_list_and_delete(): void
    {
        $this->actingAsAdmin();
        $id = $this->createTask();

        $itemId = $this->postJson("/v1/sys/task/{$id}/checklist/create", ['title' => 'A'])->json('data.id');
        $this->postJson("/v1/sys/task/{$id}/checklist/create", ['title' => 'B']);

        $this->getJson("/v1/sys/task/{$id}/checklist/list")->assertStatus(200)->assertJsonCount(2, 'data');

        $this->deleteJson("/v1/sys/task/checklist/delete/{$itemId}")->assertStatus(200);
        $this->getJson("/v1/sys/task/{$id}/checklist/list")->assertJsonCount(1, 'data');
    }

    public function test_comments_list_and_add(): void
    {
        $this->actingAsAdmin();
        $id = $this->createTask();

        $this->postJson("/v1/sys/task/{$id}/comment/create", ['comment' => 'Đã gọi phụ huynh'])
            ->assertStatus(200)
            ->assertJsonPath('data.comment', 'Đã gọi phụ huynh');

        $this->getJson("/v1/sys/task/{$id}/comment/list")->assertStatus(200)->assertJsonCount(1, 'data');
    }

    public function test_attachments_list_add_delete(): void
    {
        $this->actingAsAdmin();
        $id = $this->createTask();
        $fileId = $this->uploadMediaId();

        $attId = $this->postJson("/v1/sys/task/{$id}/attachment/create", ['file_id' => $fileId])
            ->assertStatus(200)
            ->assertJsonPath('data.file_id', $fileId)
            ->json('data.id');

        $this->getJson("/v1/sys/task/{$id}/attachment/list")->assertStatus(200)->assertJsonCount(1, 'data');

        $this->deleteJson("/v1/sys/task/attachment/delete/{$attId}")->assertStatus(200);
        $this->getJson("/v1/sys/task/{$id}/attachment/list")->assertJsonCount(0, 'data');
    }

    public function test_only_assignee_can_update_progress(): void
    {
        // Manager (non-admin) who is NOT the assignee cannot move progress (BR-04).
        $manager = $this->actingAsManager(['task.list', 'task.view', 'task.create', 'task.update']);

        $id = $this->createTask(['assignee_id' => $this->makeOtherUserId($manager)]);

        $this->putJson("/v1/sys/task/update/{$id}", ['progress' => 40])
            ->assertJsonPath('success', false)
            ->assertJsonPath('msg', 'Chỉ người được giao việc mới được cập nhật tiến độ.');
    }

    private function makeOtherUserId(User $context): int
    {
        return $this->makeUser(false, $context->role_id, $context->business_id)->id;
    }
}
