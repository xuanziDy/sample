<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Notifications\ResetPassword;
use Auth;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    //boot 方法会在用户模型类完成初始化之后进行加载，因此我们对事件的监听需要放在该方法中。
    public static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            $user->activation_token = str_random(30);
        });
    }

    public function gravatar($size = 100)
    {
    	$hash = md5(strtolower(trim($this->attributes['email'])));
	    return "http://www.gravatar.com/avatar/$hash?s=$size";
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPassword($token));
    }


    public function statuses()
    {
        return $this->hasMany(Status::class);
    }

    // 在用户模型中定义一个 feed 方法，该方法将当前用户发布过的所有微博从数据库中取出，并根据创建时间来倒序排序。
    public function feed()
    {
        // 还有一点需要注意的是 $user->followings 与 $user->followings() 调用时返回的数据是不一样的， $user->followings 返回的是 Eloquent：集合 。而 $user->followings() 返回的是 数据库请求构建器 ，followings() 的情况下，你需要使用：$user->followings()->get() 或者 $user->followings()->paginate() 才能获得最终的数据

        // $user->followings == $user->followings()->get() // 等于 true



        $user_ids = Auth::user()->followings->pluck('id')->toArray();
        array_push($user_ids, Auth::user()->id);

         // Eloquent 关联的 预加载 with 方法，预加载避免了 N+1 查找的问题，大大提高了查询效率
        return Status::whereIn('user_id', $user_ids)
                              ->with('user')
                              ->orderBy('created_at', 'desc');
    }

    // 获取粉丝关系列表
    public function followers()
    {
        // belongsToMany 方法的第三个参数 user_id 是定义在关联中的模型外键名，而第四个参数 follower_id 则是要合并的模型外键名。
        return $this->belongsToMany(User::Class, 'followers', 'user_id', 'follower_id');
    }

    // 获取用户关注人列表
    public function followings()
    {
        // 在 Laravel 中会默认将两个关联模型的名称进行合并，并按照字母排序，因此我们生成的关联关系表名称会是 user_user。我们也可以自定义生成的名称，把关联表名改为 followers。 这里第二个参数是 定义的关联 模型表名
        return $this->belongsToMany(User::Class, 'followers', 'follower_id', 'user_id');
    }

    //关注
    public function follow($user_ids)
    {
        // is_array 用于判断参数是否为数组，如果已经是数组，则没有必要再使用 compact 方法。
        if (!is_array($user_ids)) {
            $user_ids = compact('user_ids'); //意思是转为 'user_ids' => $user_ids
        }
        $this->followings()->sync($user_ids, false);
    }

    //取消关注
    public function unfollow($user_ids)
    {
        if (!is_array($user_ids)) {
            $user_ids = compact('user_ids');
        }
        $this->followings()->detach($user_ids);
    }

    // 判断当前登录的用户 A 是否关注了用户 B
    public function isFollowing($user_id)
    {
        // 只需要判断用户 B 是否包含在用户 A 的关注人列表上即可
        return $this->followings->contains($user_id);
    }
}
