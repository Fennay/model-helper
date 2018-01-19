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
     * 重新get方法，使其增加缓存
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Collection|mixed|static[]
     */
    public function get($columns = ['*'])
    {
        // 取得设置的缓存key
        $cacheKey = $this->model->getCacheKey();
        // dump($cacheKey);
        // 如果没有设置缓存key，则不缓存，直接取数据
        if (empty($cacheKey)) {
            $rs = parent::get();

            return $rs;
        }
        // 通过key未取得缓存数据，则查询数据库获取数据，
        // 取得缓存数据就直接返回缓存数据
        if (empty($cacheData = Cache::get($cacheKey))) {
            $data = parent::get($columns);
            Cache::put($cacheKey, $data, $this->model->cacheExpire);
            $this->model->setCacheKey('');

            // dump($this->model->getCacheKey());
            return $data;
        } else {
            return $cacheData;
        }
    }

    /**
     * 根据ID获取一条数据
     * @param array $where
     * @param array $orderBy
     * @return mixed
     * @author: Mikey
     */
    public function findOne(array $where, array $orderBy = ['id' => 'desc'])
    {
        $this->applyWHere($where);
        $this->applyOrder($orderBy);

        // $this->model->setCacheKey = $this->model->table . '_by_where_' . md5(serialize($where)) . '_and_order_' . md5(serialize($orderBy));

        return $this->first();
    }

    /**
     * 根据ID获取一条数据
     * @param int $id
     * @return mixed
     * @author: Mikey
     */
    public function getOne(int $id)
    {
        // $this->model->setCacheKey = $this->model->table . '_by_id_' . $id;

        return $this->find($id);
    }

    /**
     * 根据条件读取列表
     * @param array $where
     * @param int   $size
     * @param array $order
     * @param int   $skip
     * @return mixed
     */
    public function getList(array $where, int $size = 0, array $order = ['id' => 'desc'], int $skip = 0)
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
     * @param array  $field
     * @param string $pageName
     * @return mixed
     * @author: Mikey
     */
    public function getPageList(
        array $where,
        int $pageSize = 10,
        array $order,
        array $field = ['*'],
        string $pageName = 'page'
    ) {

        $this->applyWhere($where)->applyOrder($order);

        return $this->paginate($pageSize, $field, $pageName);
    }

    /**
     * 创建或者是修改
     * @param array $saveData
     * @return mixed 创建成功返回成功后的主键Id，修改成功返回受影响的记录行数
     * @author: Mikey
     */
    public function saveInfo(array $saveData)
    {
        if (!empty($saveData['id'])) {
            $this->model->setRawAttributes(['id' => $saveData['id']], true);
            $this->model->exists = true;
        }

        $this->model->fill($saveData);
        $result = $this->model->save($saveData);
        // 清除缓存
        $this->clearCache();

        return $result;
    }

    /**
     * 清除缓存
     * @return bool
     */
    protected function clearCache()
    {
        // 清除first中自动缓存的keys
        Cache::forget($this->model->getCacheKey());
        $cachePrefix = Cache::getPrefix();
        if (empty($clearKeys = $this->model->clearKeys)) {
            return true;
        }
        foreach ($clearKeys as $k => $v) {
            if(false != stripos('*',$v) && 'redis' == Cache::getDefaultDriver()){
                $realKeyArr = Cache::getRedis()->keys($cachePrefix . $v);
                foreach ($realKeyArr as $ck => $vk) {
                    $realKey = str_replace($cachePrefix,'',$vk);
                    Cache::forget($realKey);
                }
            }else{
                Cache::forget($v);
            }
        }

        return true;
    }

    /**
     * 删除
     * @param $id
     * @return mixed
     * @author: Mikey
     */
    public function del($id)
    {
        return $this->applyWhere(['id' => $id])->delete();
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
                // 第二个参数是数组
                if (is_array($value)) {
                    // 如果操作符为空，则取默认值
                    $type = 'default';
                    !empty($value[0]) && ($type = strtolower($value[0]));
                    switch ($type) {
                        case 'between' :
                            // 例子 $where = ['age',['between',[1,10]]];
                            $this->whereBetween($key, $value[1]);
                            break;
                        case 'in' :
                            $this->whereIn($key, $value[1]);
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
            if (strtolower($option) !== 'desc' && strtolower($option) !== 'asc') {
                continue;
            }
            $this->orderBy($field, $option);
        }

        return $this;
    }

}