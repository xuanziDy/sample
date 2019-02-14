<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    // 若未在微博模型中定义 fillable 属性，来指定在微博模型中可以进行正常更新的字段，
    // 我们将看到页面提示 MassAssignmentException - 批量赋值异常 ,Laravel 在尝试保护。

    protected $fillable = ['content'];


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
