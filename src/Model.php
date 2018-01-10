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
     * @param $key
     */
    public function setCacheKey($key)
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
}