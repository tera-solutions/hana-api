<?php

namespace App\Modules\Education\PlacementTest\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\PlacementTest\Actions\CreatePlacementTestAction;
use App\Modules\Education\PlacementTest\Actions\DeletePlacementTestAction;
use App\Modules\Education\PlacementTest\Actions\GeneratePlacementTestQuestionsAction;
use App\Modules\Education\PlacementTest\Actions\GetPlacementTestAction;
use App\Modules\Education\PlacementTest\Actions\ListPlacementTestAction;
use App\Modules\Education\PlacementTest\Actions\ListPlacementTestResultAction;
use App\Modules\Education\PlacementTest\Actions\PublishPlacementTestAction;
use App\Modules\Education\PlacementTest\Actions\RecordPlacementTestResultAction;
use App\Modules\Education\PlacementTest\Actions\UpdatePlacementTestAction;
use App\Modules\Education\PlacementTest\Http\Requests\CreatePlacementTestRequest;
use App\Modules\Education\PlacementTest\Http\Requests\GeneratePlacementTestQuestionsRequest;
use App\Modules\Education\PlacementTest\Http\Requests\RecordPlacementTestResultRequest;
use App\Modules\Education\PlacementTest\Http\Requests\UpdatePlacementTestRequest;
use App\Modules\Education\PlacementTest\Http\Resources\PlacementTestResource;
use App\Modules\Education\PlacementTest\Http\Resources\PlacementTestResultResource;
use Illuminate\Http\Request;

/**
 * @group Education - PlacementTest
 *
 * Placement tests (teacher app "Kiểm tra đầu vào" screen).
 *
 * @authenticated
 */
class PlacementTestController extends Controller
{
    public function list(Request $request, ListPlacementTestAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), PlacementTestResource::class);
    }

    public function detail($id, GetPlacementTestAction $action)
    {
        return $this->respondSuccess(new PlacementTestResource($action->handle($id)));
    }

    public function create(CreatePlacementTestRequest $request, CreatePlacementTestAction $action)
    {
        $test = $action->handle($request->validated());

        return $this->respondSuccess(new PlacementTestResource($test), 'Tạo bài kiểm tra thành công.');
    }

    public function update(UpdatePlacementTestRequest $request, $id, UpdatePlacementTestAction $action)
    {
        $test = $action->handle($id, $request->validated());

        return $this->respondSuccess(new PlacementTestResource($test), 'Cập nhật bài kiểm tra thành công.');
    }

    public function publish($id, PublishPlacementTestAction $action)
    {
        $test = $action->handle($id);

        return $this->respondSuccess(new PlacementTestResource($test), 'Xuất bản bài kiểm tra thành công.');
    }

    public function delete($id, DeletePlacementTestAction $action)
    {
        $action->handle($id);

        return $this->respondSuccess(null, 'Xóa bài kiểm tra thành công.');
    }

    public function results($id, Request $request, ListPlacementTestResultAction $action)
    {
        return $this->respondPaginated(
            $action->handle($id, $request->all()),
            PlacementTestResultResource::class,
        );
    }

    public function recordResult($id, RecordPlacementTestResultRequest $request, RecordPlacementTestResultAction $action)
    {
        $result = $action->handle($id, $request->validated());

        return $this->respondSuccess(new PlacementTestResultResource($result), 'Ghi nhận kết quả thành công.');
    }

    public function generateQuestions(
        $id,
        GeneratePlacementTestQuestionsRequest $request,
        GeneratePlacementTestQuestionsAction $action,
    ) {
        $test = $action->handle($id, $request->validated('buckets'));

        return $this->respondSuccess(new PlacementTestResource($test), 'Đã thêm câu hỏi vào bài kiểm tra.');
    }
}
