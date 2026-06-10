<?php

namespace App\Modules\Education\Teacher\Services;

use App\Modules\Education\Teacher\Models\Teacher;

class TeacherService
{
    public function paginate()
    {
        return Teacher::paginate();
    }

    public function find($id)
    {
        return Teacher::findOrFail($id);
    }

    public function create(array $data)
    {
        return Teacher::create($data);
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
