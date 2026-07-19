<?php

namespace App\Modules\Education\Score\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\Score\Http\Requests\SaveScoreComponentRequest;
use App\Modules\Education\Score\Http\Requests\SaveScoreConfigRequest;
use App\Modules\Education\Score\Services\ScoreService;

/**
 * @group Education - Score
 *
 * Weighted score structure per class + finalize (EDU-17).
 *
 * @authenticated
 */
class ScoreController extends Controller
{
    public function __construct(private ScoreService $scores)
    {
    }

    /**
     * Score config for a class
     *
     * @urlParam classId integer required
     */
    public function getConfig($classId)
    {
        return $this->respondSuccess($this->scores->getConfig($classId));
    }

    /**
     * Save (create/replace) a class's weighted score structure
     *
     * @urlParam classId integer required
     */
    public function saveConfig(SaveScoreConfigRequest $request, $classId)
    {
        return $this->tryRespond(
            fn () => $this->scores->saveConfig($classId, $request->validated('components')),
            'Đã lưu cấu trúc điểm.',
        );
    }

    /**
     * Score board: roster + entered component scores + final (if any)
     *
     * @urlParam classId integer required
     */
    public function board($classId)
    {
        return $this->respondSuccess($this->scores->board($classId));
    }

    /**
     * Enter/update one student's score for one component
     */
    public function saveComponent(SaveScoreComponentRequest $request, $classId)
    {
        return $this->tryRespond(
            fn () => $this->scores->saveComponentScore(
                $classId,
                $request->validated('student_id'),
                $request->validated('type'),
                (float) $request->validated('score'),
            ),
            'Đã lưu điểm thành phần.',
        );
    }

    /**
     * Compute + lock the weighted final score for every student in the class
     *
     * @urlParam classId integer required
     */
    public function finalize($classId)
    {
        return $this->tryRespond(
            fn () => $this->scores->finalize($classId),
            'Đã chốt điểm cho lớp.',
        );
    }

    /**
     * Unlock a finalized class (removes the final rows so components can be edited again)
     *
     * @urlParam classId integer required
     */
    public function unlock($classId)
    {
        return $this->tryRespond(
            fn () => $this->scores->unlock($classId),
            'Đã mở khóa điểm.',
            fn () => null,
        );
    }
}
