<?php

namespace App\Modules\Education\Course\Services;

use App\Modules\Education\Course\Models\Course;

class CourseService
{
    public function paginate()
    {
        return Course::paginate();
    }

    public function find($id)
    {
        return Course::findOrFail($id);
    }

    public function create(array $data)
    {
        return Course::create($data);
    }

    public function update($id, array $data)
    {
        $model = $this->find($id);
        $model->update($data);
        return $model;
    }

    public function delete($id)
    {
        return $this->find($id)->delete();
    }
}