<?php
/**
 * Created by PhpStorm.
 * Author: Fengguangyong
 * Date: 2017/11/29 - 17:03
 */

namespace Fennay\ModelHelper;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Cache;

abstract class Model extends EloquentModel
{
    /**
     * @var array 更新数据需删除的缓存key列表
     */
    public $clearKeys = [];
    /**
     * @var string getList设置缓存的键
     */
    public $cacheKey = '';
    /**
     * @var int  设置缓存时间，默认5分钟
     */
    public $cacheExpire = 5;

    /**
     * Model constructor.
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    /**
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @return HelperQueryBuilder
     */
    public function newEloquentBuilder($query)
    {
        $builder = new HelperQueryBuilder($query);

        return $builder;
    }

    /**
     * 设置缓存key
     * @param null $key
     */
    public function setCacheKey($key = null)
    {
        if (empty($key)) {
            $this->cacheKey = '';
        } else {
            $this->cacheKey = $key;
        }
    }

    /**
     * 获得缓存key
     * @return string
     */
    public function getCacheKey()
    {
        return $this->cacheKey;
    }
    
    public function saveInfo(array $saveData)
    {
        //if (!empty($saveData['id'])) {
        //    $this->model->setRawAttributes(['id' => $saveData['id']], true);
        //    $this->model->exists = true;
        //}
        //
        //$this->model->fill($saveData);
        //$result = $this->model->save($saveData);
        //// 清除缓存
        //$this->clearCache();
        //
        //return $result;

        if (!empty($saveData['id'])) {
            $this->setRawAttributes(['id' => $saveData['id']], true);#刻意将主键传给syncOriginal
            $this->exists = true;
        } else {
            $this->setRawAttributes($saveData, true);#刻意将主键不给syncOriginal
            $this->exists = false;
        }
        $this->fill($saveData);
        $this->clearCache();

        return parent::save($saveData);
    }

    /**
     * 清除缓存
     * @return bool
     */
    protected function clearCache()
    {
        // 清除first中自动缓存的keys
        Cache::forget($this->getCacheKey());
        $cachePrefix = Cache::getPrefix();
        if (empty($clearKeys = $this->clearKeys)) {
            return true;
        }
        foreach ($clearKeys as $k => $v) {
            if ((false != stripos($v, '*')) && 'redis' == Cache::getDefaultDriver()) {
                $realKeyArr = Cache::getRedis()->keys($cachePrefix . $v);
                foreach ($realKeyArr as $ck => $vk) {
                    $realKey = str_replace($cachePrefix, '', $vk);
                    Cache::forget($realKey);
                }
            } else {
                Cache::forget($v);
            }
        }

        return true;
    }
}
