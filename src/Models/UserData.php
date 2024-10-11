<?php
namespace WebRegulate\LaravelAdministration\Models;

use Illuminate\Database\Eloquent\Model;

class UserData extends Model
{
    protected $table = 'wrla_user_data';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'permissions',
        'settings',
        'data',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}