<?php

namespace Package\Repository;

/**
 * Created by TeraCore.
 * User: truong.nq
 * Date: 5/12/2020
 * Time: 2:45 PM
 */
interface RepositoryInterface
{
    /**
     * @param $filter
     * @return mixed
     */
    public function all($filter);

    /**
     * @param $id
     * @return mixed
     */
    public function find($id);

    /**
     * @param $data
     * @return mixed
     */
    public function create($data);

    /**
     * @param $data
     * @return mixed
     */
    public function update($data);

    /**
     * @param $data
     * @param $array_id
     * @return mixed
     */
    public function createManyOfRow($data);

    /**
     * @param $id
     * @return mixed
     */
    public function delete($id);
}
