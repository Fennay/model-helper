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
}