<?php

namespace Fennay\ModelHelper;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Cache;

class HelperQueryBuilder extends Builder
{

    protected $model;

    public function setHelperModel($model)
    {
        $this->model = $model;
    }

    /**
     * 根据ID获取一条数据
     * @param array $where
     * @param array $orderBy
     * @return mixed
     * @author: Mikey
     */
    public function findOne(array $where, $orderBy = ['id' => 'desc'])
    {
        $this->applyWHere($where);
        $this->applyOrder($orderBy);

        return $this->first();
    }

    /**
     * 根据ID获取一条数据
     * @param $id
     * @return mixed
     * @author: Mikey
     */
    public function getOne($id)
    {
        return $this->applyWhere(['id' => $id])->applyOrder(['id' => 'desc'])->first();
    }

    /**
     * 根据条件读取列表
     * @param array $where
     * @param int   $size
     * @param array $order
     * @param int   $skip
     * @return mixed
     */
    public function getList(array $where, $size = null, $order = ['id' => 'desc'], $skip = null)
    {
        $this->applyWhere($where)->applyOrder($order);
        if (!empty($size)) {
            $this->take($size);
        }

        if (!empty($skip)) {
            $this->skip($skip);
        }

        return $this->get();
    }

    /**
     * 获取分页数据
     * @param array  $where
     * @param int    $pageSize
     * @param array  $order
     * @param string $field
     * @param string $pageName
     * @return mixed
     * @author: Mikey
     */
    public function getPageList(array $where, $pageSize = 10, array $order, $field = '*', $pageName = 'page')
    {

        $this->applyWhere($where)->applyOrder($order);

        return $this->paginate($pageSize, $field, $pageName);
    }

    /**
     * 创建或者是修改
     * @param        $saveData
     * @return mixed 创建成功返回成功后的主键Id，修改成功返回受影响的记录行数
     * @author: Mikey
     */
    public function saveInfo($saveData)
    {
        $this->fill($saveData);
        parent::save();
    }


    /**
     * 组合where参数
     * @param array $where
     * @return mixed
     */
    public function applyWhere(array $where)
    {
        if (!empty($where)) {
            //例如 ['name' => ['like'=> 'sss']]
            foreach ($where as $key => $value) {
                // 如果第二个参数是字符串，则表示默认使用 = 操作符
                if (is_array($value['1'])) {
                    // 第二个参数是数组
                    switch (!empty($value['0']) && strtolower($value['0'])) {
                        case 'in' :
                            $this->whereIn($key, $value[1]);
                            break;
                        case 'between' :
                            // 例子 $where = ['age',['between',[1,10]]];
                            $this->whereBetween($key, $value[1]);
                            break;
                        case 'notbetween' :
                            // 例子 $where = ['age',['notbetween',[1,10]]];
                            $this->whereNotBetween($key, $value[1]);
                            break;
                        default:
                            $this->where($key, $value[0], $value[1]);
                    }
                } else {
                    $this->where($key, $value);
                }

            }
        }

        return $this;
    }


    /**
     * 组装order
     * @param array $order
     * @return mixed
     */
    public function applyOrder(array $order)
    {
        // 例如：['sort' => 'desc','id' => 'desc']
        foreach ($order as $field => $option) {
            $this->model = $this->model->orderBy($field, $option);
        }

        return $this;
    }

}