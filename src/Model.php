<?php
/**
 * Created by PhpStorm.
 * Author: Fengguangyong
 * Date: 2017/11/29 - 17:03
 */

namespace Fennay\ModelHelper;

use Illuminate\Database\Eloquent\Model as EloquentModel;

class Model extends EloquentModel
{
    public function newEloquentBuilder($query)
    {
        $builder = new HelperQueryBuilder($query);

        return $builder;
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
            $this->setRawAttributes(['id' => $saveData['id']], true);
            $this->exists = true;
        }

        $this->fill($saveData);

        return $this->save($saveData);
    }

    /**
     * $saveData 如果是以为数组走保存更新，
     * [['id'=>1,'name'=>'1'],['id'=>'2','name'=>'2']]
     * @param array $saveData
     * @return mixed
     * @author: Mikey
     */
    public function insertAll(array $saveData)
    {
        if (is_array(reset($saveData))) {
            return $this->saveInfo($saveData);
        }

        $query = $this->newQueryWithoutScopes();
        foreach ($saveData as $k => $v) {
            if ($this->usesTimestamps()) {
                $time = $this->freshTimestamp();
                $v['created_at'] = $time;
                $v['updated_at'] = $time;
            }
            $saveData[$k] = $v;
        }

        $query->insert($saveData);
    }
}