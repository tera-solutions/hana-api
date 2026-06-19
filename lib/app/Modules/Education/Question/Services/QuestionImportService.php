<?php

namespace App\Modules\Education\Question\Services;

use App\Models\Media;
use App\Modules\Education\Question\Enums\QuestionDifficulty;
use App\Modules\Education\Question\Enums\QuestionSkill;
use App\Modules\Education\Question\Enums\QuestionType;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Import questions from an uploaded spreadsheet (question.md §X). The file is referenced
 * by an uploaded media record (file_id); the columns follow the spec template:
 * Question | Option A | Option B | Option C | Option D | Correct Answer | Difficulty | Skill.
 * Valid rows are persisted; per-row errors are reported without rolling back (BR008).
 */
class QuestionImportService
{
    public function __construct(private QuestionService $questionService) {}

    /**
     * @param  array<string, mixed>  $data  file_id + optional defaults/overrides
     * @return array{imported: int, failed: array<int, array{row: int, errors: array<string, array<int, string>>}>}
     *
     * @throws \RuntimeException
     */
    public function import(array $data): array
    {
        $rows = $this->readRows($data['file_id']);
        $header = $this->headerMap(array_shift($rows));

        $defaults = [
            'level_id' => $data['level_id'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'score' => $data['default_score'] ?? 1,
            'skill' => $data['default_skill'] ?? null,
            'difficulty' => $data['default_difficulty'] ?? null,
        ];

        $imported = 0;
        $failed = [];

        foreach ($rows as $index => $row) {
            if ($this->isBlankRow($row)) {
                continue;
            }

            $payload = $this->rowToPayload($row, $header, $defaults);
            $rowNumber = $index + 2; // 1-based, past the header

            if ($errors = $this->validateRow($payload)) {
                $failed[] = ['row' => $rowNumber, 'errors' => $errors];

                continue;
            }

            try {
                $this->questionService->create($payload);
                $imported++;
            } catch (\Throwable $e) {
                $failed[] = ['row' => $rowNumber, 'errors' => ['import' => [$e->getMessage()]]];
            }
        }

        return ['imported' => $imported, 'failed' => $failed];
    }

    /**
     * Read the first worksheet of the media file as an array of rows.
     *
     * @return array<int, array<int, mixed>>
     *
     * @throws \RuntimeException
     */
    private function readRows($fileId): array
    {
        $media = Media::find($fileId);

        if (! $media) {
            throw new \RuntimeException('Không tìm thấy tập tin import.');
        }

        $path = public_path($media->file_path);

        if (! File::exists($path)) {
            throw new \RuntimeException('Tập tin import không tồn tại trên hệ thống.');
        }

        $rows = Excel::toArray([], $path)[0] ?? [];

        if (count($rows) < 2) {
            throw new \RuntimeException('Tập tin import không có dữ liệu.');
        }

        return $rows;
    }

    /**
     * Map (lower-cased, trimmed) header labels to their column index.
     *
     * @param  array<int, mixed>  $row
     * @return array<string, int>
     */
    private function headerMap(array $row): array
    {
        $map = [];

        foreach ($row as $i => $label) {
            $map[strtolower(trim((string) $label))] = $i;
        }

        return $map;
    }

    /**
     * Build a create payload from a spreadsheet row.
     *
     * @param  array<int, mixed>  $row
     * @param  array<string, int>  $header
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    private function rowToPayload(array $row, array $header, array $defaults): array
    {
        $cell = fn (string $name): string => isset($header[$name]) ? trim((string) ($row[$header[$name]] ?? '')) : '';

        $correct = collect(explode(',', $cell('correct answer')))
            ->map(fn ($v) => strtoupper(trim($v)))
            ->filter()
            ->all();

        $answers = [];
        $order = 0;
        foreach (['A' => 'option a', 'B' => 'option b', 'C' => 'option c', 'D' => 'option d'] as $key => $column) {
            $content = $cell($column);
            if ($content === '') {
                continue;
            }
            $answers[] = [
                'answer_key' => $key,
                'answer_content' => $content,
                'is_correct' => in_array($key, $correct, true),
                'sort_order' => $order++,
            ];
        }

        return [
            'question_type' => count($correct) > 1 ? QuestionType::MultipleChoice->value : QuestionType::SingleChoice->value,
            'skill' => strtolower($cell('skill')) ?: $defaults['skill'],
            'difficulty' => strtolower($cell('difficulty')) ?: $defaults['difficulty'],
            'level_id' => $defaults['level_id'],
            'category_id' => $defaults['category_id'],
            'score' => $defaults['score'],
            'content' => $cell('question'),
            'answers' => $answers,
        ];
    }

    /**
     * Validate a parsed row (BR001–BR004), returning field => messages (empty when valid).
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, array<int, string>>
     */
    private function validateRow(array $payload): array
    {
        $validator = Validator::make($payload, [
            'question_type' => ['required', Rule::in(QuestionType::values())],
            'skill' => ['required', Rule::in(QuestionSkill::values())],
            'difficulty' => ['required', Rule::in(QuestionDifficulty::values())],
            'score' => ['required', 'numeric', 'gt:0'],     // BR004
            'content' => ['required', 'string'],
            'answers' => ['required', 'array', 'min:1'],     // BR001
        ]);

        $validator->after(function ($validator) use ($payload) {
            $correct = collect($payload['answers'] ?? [])->filter(fn ($a) => ! empty($a['is_correct']));

            if (in_array($payload['question_type'] ?? null, QuestionType::singleCorrect(), true)) {
                if ($correct->count() !== 1) {
                    $validator->errors()->add('answers', 'Phải có đúng một đáp án đúng.'); // BR002
                }
            } elseif ($correct->isEmpty()) {
                $validator->errors()->add('answers', 'Phải có ít nhất một đáp án đúng.'); // BR003
            }
        });

        return $validator->errors()->toArray();
    }

    /**
     * @param  array<int, mixed>  $row
     */
    private function isBlankRow(array $row): bool
    {
        return collect($row)->every(fn ($v) => trim((string) $v) === '');
    }
}
