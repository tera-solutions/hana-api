<?php

namespace Package\Util;

use Exception;
use Illuminate\Support\Facades\File;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Package\Exception\DatabaseException;
use Package\Exception\HttpException;

/**
 * Created by TeraCore.
 * User: truong.nq
 * Date: 5/12/2020
 * Time: 2:45 PM
 */

abstract class BasicEntity
{
    protected  $primaryKey;

    protected  $table;

    protected  $entity;

    protected  $id;

    protected $error;

    protected $user;

    /**
     * @param array $data
     * @return bool
     */
    public function CreateOrUpdate(array $data)
    {
        DB::beginTransaction();
        try {
            $user_id = Auth::guard('api')->user()->id;
            if (isset($data[$this->primaryKey]) == false) {
                $data['created_at'] = isset($data['created_at']) ? $data['created_at'] : now();
                $data['created_by'] = $user_id;
                $result = DB::table($this->table)->updateOrInsert($data);

                if (!$result) {
                    DB::rollBack();
                    throw new DatabaseException("Lỗi trong quá trình thêm dữ liệu");
                }

                DB::commit();
            } else {
                $data['updated_at'] = isset($data['updated_at']) ? $data['updated_at'] : now();
                $this->id = $data[$this->primaryKey];
                $result = DB::table($this->table)
                    ->where($this->primaryKey, $this->id)
                    ->update($data);
                if (!$result) {
                    DB::rollBack();
                    throw new DatabaseException("Lỗi trong quá trình cập nhật dữ liệu");
                }

                DB::commit();
            }
            return $result;
        } catch (DatabaseException $e) {
            DB::rollBack();

            $this->error = $e->getMessage();
            throw new DatabaseException($e);
        }
    }

    /**
     * @param array $arrId
     * @param array $attributes
     * @return bool
     */
    public function CreateOrUpdateManyRow(array $arrId, array $attributes)
    {
        DB::beginTransaction();

        try {
            if (empty($arrId) == true) {
                foreach ($attributes as $key => $value) {
                    $value['created_at'] =  now();
                    $data['insert_user_id'] =  '1';
                    $attributes[$key] = $value;
                }
                DB::table($this->table)->insert($attributes);

                DB::commit();

                return true;
            } else if (empty($table) == false && empty($arrId) == false) {
                foreach ($attributes as $key => $value) {
                    $value['updated_at'] =  now();
                    $attributes[$key] = $value;
                }
                foreach ($arrId as $index => $nodeValue) {
                    $query = DB::table($this->table)->where($this->primaryKey, $nodeValue)->update($attributes[$index]);
                    if ($query == false) {
                        DB::rollBack();
                        throw new DatabaseException("Lỗi trong quá trình cập nhật dữ liệu");
                    }
                }

                DB::commit();

                return true;
            } else {
                DB::rollBack();
                throw new DatabaseException("Lỗi trong quá trình cập nhật dữ liệu");
            }
        } catch (DatabaseException $e) {
            DB::rollBack();

            $this->error = $e->getMessage();
            throw new DatabaseException($e);
        }
    }

    /**
     * @param array $attributes
     * @return bool
     */
    public function CreateManyRow(array $attributes)
    {
        DB::beginTransaction();

        try {
            if (count($attributes) > 0) {
                foreach ($attributes as $key => $value) {
                    $attributes[$key] = $value;
                }

                DB::table($this->table)->insert($attributes);

                DB::commit();

                return true;
            } else {
                DB::rollBack();
                throw new DatabaseException("Lỗi trong quá trình tạo mới dữ liệu");
            }
        } catch (DatabaseException $e) {
            DB::rollBack();

            $this->error = $e->getMessage();
            throw new DatabaseException($e);
        }
    }

    /**
     * @param array $arrId
     * @param array $attributes
     * @return bool
     */
    public function UpdateManyRow(array $arrId, array $attributes)
    {
        DB::beginTransaction();

        try {
            if (empty($arrId) == false) {
                foreach ($attributes as $key => $value) {
                    $value['updated_at'] =  now();
                    $attributes[$key] = $value;
                }
                foreach ($arrId as $index => $nodeValue) {
                    $query = DB::table($this->table)->where($this->primaryKey, $nodeValue)->update($attributes[$index]);
                    if ($query == false) {
                        DB::rollBack();
                        throw new DatabaseException("Lỗi trong quá trình cập nhật dữ liệu");
                    }
                }

                DB::commit();

                return true;
            } else {
                DB::rollBack();

                $this->error = 'name table or id not found for update';
                return false;
            }
        } catch (DatabaseException $e) {
            DB::rollBack();

            $this->error = $e->getMessage();
            throw new DatabaseException($e);
        }
    }

    /**
     * @param array $arrID
     * @return bool
     */

    public function DeleteManyRow(array $arrID)
    {
        DB::beginTransaction();
        try {
            if (empty($arrID) == false) {
                $path = storage_path('logs/package.delete');
                $totalLine = count(file($path));
                $content = "\r\n #" . $totalLine . ':delete row from ' . $this->primaryKey . ' /id:' . implode(",", $arrID) . ' from table /tb:' . $this->table . ' at time /t:' . now();
                $query = DB::table($this->table)
                    ->where('is_delete', '=', 1)
                    ->whereIn($this->primaryKey, $arrID)
                    ->delete();
                if (!$query) {
                    DB::rollBack();
                    throw new DatabaseException("Lỗi trong quá trình xoá dữ liệu");
                }

                DB::commit();

                $this->WriteLog($path, $content);

                return true;
            } else {
                DB::rollBack();
                throw new DatabaseException("Không tìm thấy ID cần xoá!");
            }
        } catch (DatabaseException $e) {
            DB::rollBack();

            $this->error = $e->getMessage();
            throw new DatabaseException($e);
        }
    }

    /**
     * @param null $paging
     * @param null $lang
     * @return bool|\Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection
     */
    public function findAll($paging = null)
    {
        try {
            $table = $this->table;

            if (empty($paging) == false) {
                $result = DB::table($table)
                    ->where("{$table}.is_active", '=', 1)
                    ->where("{$table}.is_delete", '=', 0)
                    ->paginate($paging);
            } else {
                $result = DB::table($table)
                    ->where("{$table}.is_active", '=', 1)
                    ->where("{$table}.is_delete", '=', 0)
                    ->get();
            }

            return $result;
        } catch (HttpException $e) {
            DB::rollBack();

            $this->error = $e->getMessage();
            throw new HttpException($e);
        }
    }

    /**
     * @return bool|\Illuminate\Support\Collection
     */
    public function findAllTrash($paging = null)
    {
        try {
            if (empty($lang) == true) {
                $lang = App::getLocale();
            }
            if (empty($paging) == false) {
                $result = DB::table($this->table)
                    ->where('is_delete', '=', 1)
                    ->paginate($paging);
            } else {
                $result = DB::table($this->table)
                    ->where('is_delete', '=', 1)
                    ->get();
            }
            return $result;
        } catch (HttpException $e) {
            DB::rollBack();

            $this->error = $e->getMessage();
            throw new HttpException($e);
        }
    }
    /**
     * @param $id: is id need find
     * @return bool|Model|\Illuminate\Database\Query\Builder|null|object
     */
    public function findOneById($id)
    {
        try {
            if (empty($id) == false) {
                $result = DB::table($this->table)
                    ->where($this->primaryKey, $id)
                    ->first();
                return $result;
            } else {
                throw new DatabaseException("Không tìm thấy dữ liệu!");
            }
        } catch (HttpException $e) {
            DB::rollBack();

            $this->error = $e->getMessage();
            throw new HttpException($e);
        }
    }

    /**
     * @param $table: name table
     * @param $keyFiled: key condition field
     * @param array $Attributes: value condition
     * @return bool|\Illuminate\Support\Collection
     */
    public function findMany($keyFiled, array $Attributes)
    {
        try {
            if (empty($Attributes) == false && empty($keyFiled) == false) {

                $result = DB::table($this->table)
                    ->whereIn($keyFiled, $Attributes)
                    ->get();
                return $result;
            } else {
                throw new DatabaseException("Không tìm thấy dữ liệu!");
            }
        } catch (HttpException $e) {
            DB::rollBack();

            $this->error = $e->getMessage();
            throw new HttpException($e);
        }
    }
    public function TrashOrRecover($id, $trash = true)
    {
        try {
            if (empty($id) == false) {
                if ($trash == true) {
                    $data['is_delete'] = '1';
                } else {
                    $data['is_delete'] = '0';
                }
                $data['updated_at'] = now();
                $flag = DB::table($this->table)
                    ->where($this->primaryKey, $id)
                    ->update($data);
                if ($flag == true) {
                    return true;
                }
                return false;
            } else {
                throw new DatabaseException("Không tìm thấy dữ liệu!");
            }
        } catch (HttpException $e) {
            DB::rollBack();

            $this->error = $e->getMessage();
            throw new HttpException($e);
        }
    }

    public function WriteLog($file, $content)
    {
        $f = fopen($file, "a+");
        file_put_contents($file, $content, FILE_APPEND);
        fclose($f);
    }
}
