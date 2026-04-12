<?php

namespace Package\Entity;

/**
 * Created by TeraCore.
 * User: truong.nq
 * Date: 5/12/2020
 * Time: 2:45 PM
 */
interface EntityInterface
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
     * @param array $input
     * @return mixed
     */
    public function create(array $input);

    /**
     * @param array $input
     * @return mixed
     */
    public function createManyOfRow(array $input);

    /**
     * @param array $input
     * @return mixed
     */
    public function update(array $input);

    /**
     * Delete
     *
     * @param int $id
     * @return boolean
     */
    public function delete($id);
}
