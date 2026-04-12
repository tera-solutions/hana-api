<?php

namespace App\Modules\Education\Student\Services;

use App\Modules\Education\Student\Models\Student;

class StudentService
{
    public function paginate()
    {
        return Student::paginate();
    }

    public function find($id)
    {
        return Student::findOrFail($id);
    }

    public function create(array $data)
    {
        return Student::create($data);
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