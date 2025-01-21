<?php
namespace WebRegulate\LaravelAdministration\Models;

use Illuminate\Database\Eloquent\Model;
use WebRegulate\LaravelAdministration\Classes\WRLAHelper;

class UserData extends Model
{
    public $connection = 'mysql';
    public $table = 'wrla_user_data';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'permissions',
        'settings',
        'data',
    ];

    public function getConnectionName()
    {
        return config('wr-laravel-administration.wrla_user_data.connection');
    }

    public function getTable()
    {
        return config('wr-laravel-administration.wrla_user_data.table');
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(WRLAHelper::getWRLAUserModelClass());
    }
}
