<?php

namespace Tests\Feature;

use Database\Seeders\QuestionPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class QuestionTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    private int $businessId;

    private int $courseId;

    private int $levelId;

    /** @var array<int, string> Absolute paths of temp upload files to clean up. */
    private array $tmpFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(QuestionPermissionSeeder::class);

        $this->businessId = $this->makeBusinessId();
        $this->courseId = $this->makeCourseId();
        $this->levelId = $this->makeLevelId();
    }

    protected function tearDown(): void
    {
        foreach ($this->tmpFiles as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        parent::tearDown();
    }

    /** Write a CSV under public/ and register it as an uploaded media file. */
    private function uploadCsv(string $contents): int
    {
        $dir = public_path('storage/uploads');
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $name = 'qimport_'.uniqid().'.csv';
        $relative = 'storage/uploads/'.$name;
        $absolute = public_path($relative);

        file_put_contents($absolute, $contents);
        $this->tmpFiles[] = $absolute;

        return DB::table('media')->insertGetId([
            'file_path' => $relative,
            'file_name' => $name,
            'file_type' => 'text/csv',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeCourseId(): int
    {
        return DB::table('edu_courses')->insertGetId([
            'business_id' => $this->businessId,
            'name' => 'Course '.uniqid(),
            'code' => 'C_'.strtoupper(uniqid()),
            'duration_minutes' => 90,
            'price_per_lesson' => 100000,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeLevelId(): int
    {
        return DB::table('edu_levels')->insertGetId([
            'level_code' => 'L_'.strtoupper(uniqid()),
            'level_name' => 'Starter',
            'course_id' => $this->courseId,
            'level_order' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function singleChoicePayload(array $overrides = []): array
    {
        return array_merge([
            'question_type' => 'single_choice',
            'skill' => 'grammar',
            'difficulty' => 'easy',
            'level_id' => $this->levelId,
            'score' => 2,
            'content' => 'What color is the sky?',
            'answers' => [
                ['answer_key' => 'A', 'answer_content' => 'Red', 'is_correct' => false],
                ['answer_key' => 'B', 'answer_content' => 'Blue', 'is_correct' => true],
            ],
        ], $overrides);
    }

    private function createQuestion(array $overrides = []): int
    {
        return $this->postJson('/v1/edu/question/create', $this->singleChoicePayload($overrides))->json('data.id');
    }

    /** Walk a question from draft to active. */
    private function activate(int $id): void
    {
        $this->postJson("/v1/edu/question/review/{$id}")->assertJsonPath('data.status', 'reviewing');
        $this->postJson("/v1/edu/question/approve/{$id}")->assertJsonPath('data.status', 'approved');
        $this->postJson("/v1/edu/question/activate/{$id}")->assertJsonPath('data.status', 'active');
    }

    private function generateExam(array $overrides = []): TestResponse
    {
        return $this->postJson('/v1/edu/question/generate-exam', array_merge([
            'exam_name' => 'Grammar Quiz',
            'exam_type' => 'progress',
            'course_id' => $this->courseId,
            'level_id' => $this->levelId,
            'duration' => 30,
            'passing_score' => 2,
            'skill' => 'grammar',
            'difficulties' => [['difficulty' => 'easy', 'count' => 5]],
        ], $overrides));
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/v1/edu/question/list')->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);

        $this->postJson('/v1/edu/question/create', [])->assertJsonPath('code', 403);
    }

    public function test_create_starts_as_draft_with_code_and_answers(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/edu/question/create', $this->singleChoicePayload())
            ->assertStatus(200)
            ->assertJsonPath('data.question_code', 'QST000001')
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonCount(2, 'data.answers');
    }

    public function test_create_validation(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/edu/question/create', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['question_type', 'skill', 'difficulty', 'score', 'content']);

        // BR004: score must be > 0.
        $this->postJson('/v1/edu/question/create', $this->singleChoicePayload(['score' => 0]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['score']);
    }

    public function test_single_choice_requires_exactly_one_correct(): void
    {
        $this->actingAsAdmin();

        // BR002: two correct answers on a single-choice question is invalid.
        $this->postJson('/v1/edu/question/create', $this->singleChoicePayload(['answers' => [
            ['answer_key' => 'A', 'answer_content' => 'Red', 'is_correct' => true],
            ['answer_key' => 'B', 'answer_content' => 'Blue', 'is_correct' => true],
        ]]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['answers']);
    }

    public function test_answer_backed_question_requires_an_answer(): void
    {
        $this->actingAsAdmin();

        // BR001: matching question without answers is invalid.
        $this->postJson('/v1/edu/question/create', $this->singleChoicePayload([
            'question_type' => 'matching',
            'answers' => [],
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['answers']);
    }

    public function test_essay_allows_no_answers(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/edu/question/create', [
            'question_type' => 'essay',
            'skill' => 'writing',
            'difficulty' => 'medium',
            'score' => 10,
            'content' => 'Describe your family.',
        ])->assertStatus(200)->assertJsonPath('data.status', 'draft');
    }

    public function test_review_workflow_transitions(): void
    {
        $this->actingAsAdmin();

        $id = $this->createQuestion();

        // Cannot approve straight from draft.
        $this->postJson("/v1/edu/question/approve/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('success', false);

        $this->activate($id);
    }

    public function test_update_in_use_question_creates_version(): void
    {
        $this->actingAsAdmin();

        $id = $this->createQuestion();
        $this->activate($id);

        // BR006/BR007: editing an active (in-use) question snapshots a version + bumps version.
        $this->putJson("/v1/edu/question/update/{$id}", ['content' => 'Updated content', 'change_log' => 'typo'])
            ->assertStatus(200)
            ->assertJsonPath('data.version', 2);

        $this->assertDatabaseHas('edu_question_versions', ['question_id' => $id, 'version' => 1]);

        // The version history is exposed via the dedicated module endpoint, newest first.
        $this->getJson("/v1/edu/question/version/list/{$id}")
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.version', 1);
    }

    public function test_clone_resets_to_draft_and_copies_answers(): void
    {
        $this->actingAsAdmin();

        $id = $this->createQuestion();

        $this->postJson("/v1/edu/question/clone/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.question_code', 'QST000002')
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonCount(2, 'data.answers');
    }

    public function test_import_from_file_persists_valid_rows_and_reports_failures(): void
    {
        $this->actingAsAdmin();

        // Spec §X template: one valid single-choice row + one row with no correct answer.
        $csv = implode("\n", [
            'Question,Option A,Option B,Option C,Option D,Correct Answer,Difficulty,Skill',
            'What color is the sky?,Red,Blue,,,B,easy,grammar',
            'Missing correct?,Red,Blue,,,,easy,grammar',
        ]);

        $fileId = $this->uploadCsv($csv);

        $this->postJson('/v1/edu/question/import', ['file_id' => $fileId, 'file_name' => 'questions.csv'])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.imported', 1)
            ->assertJsonCount(1, 'data.failed')
            ->assertJsonPath('data.failed.0.row', 3);

        $this->assertDatabaseHas('edu_questions', ['content' => 'What color is the sky?', 'question_type' => 'single_choice']);
    }

    public function test_import_rejects_unknown_file(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/edu/question/import', ['file_id' => 999999])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file_id']);
    }

    public function test_generate_exam_only_uses_active_questions(): void
    {
        $this->actingAsAdmin();

        // One active, one still draft.
        $active = $this->createQuestion();
        $this->activate($active);
        $this->createQuestion();

        $response = $this->generateExam()
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonCount(1, 'data.questions'); // BR005: only the active one

        $examId = $response->json('data.id');

        // Usage recorded in the pivot + statistics bumped.
        $this->assertDatabaseHas('edu_exam_question', ['exam_id' => $examId, 'question_id' => $active]);
        $this->assertDatabaseHas('edu_question_statistics', ['question_id' => $active, 'usage_count' => 1]);

        // total_score is the sum of drawn question scores.
        $this->assertEquals('2.00', DB::table('edu_exams')->where('id', $examId)->value('total_score'));
    }

    public function test_generate_exam_fails_when_no_active_questions(): void
    {
        $this->actingAsAdmin();

        $this->createQuestion(); // draft only

        $this->generateExam()
            ->assertStatus(200)
            ->assertJsonPath('success', false);
    }

    public function test_tag_crud_and_question_usage_count(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/edu/question-tag/create', ['tag_name' => 'colors'])
            ->assertStatus(200)
            ->assertJsonPath('data.tag_name', 'colors')
            ->json('data.id');

        // Duplicate name rejected.
        $this->postJson('/v1/edu/question-tag/create', ['tag_name' => 'colors'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['tag_name']);

        // A question carrying the tag is reflected in the tag's questions_count.
        $this->createQuestion(['tag_ids' => [$id]]);

        $this->getJson('/v1/edu/question-tag/list')
            ->assertStatus(200)
            ->assertJsonPath('data.items.0.tag_name', 'colors')
            ->assertJsonPath('data.items.0.questions_count', 1);

        $this->putJson("/v1/edu/question-tag/update/{$id}", ['tag_name' => 'colours'])
            ->assertStatus(200)
            ->assertJsonPath('data.tag_name', 'colours');

        $this->deleteJson("/v1/edu/question-tag/delete/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_category_crud(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/edu/question-category/create', [
            'category_code' => 'GRAMMAR',
            'category_name' => 'Grammar',
        ])->assertStatus(200)->json('data.id');

        $this->putJson("/v1/edu/question-category/update/{$id}", ['category_name' => 'Grammar & Usage'])
            ->assertStatus(200)
            ->assertJsonPath('data.category_name', 'Grammar & Usage');

        $this->deleteJson("/v1/edu/question-category/delete/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }
}
