<?php

namespace Encore\Admin\Auth\Database;

use App\Models\Campus;
use App\Models\UserHasProgram;
use Carbon\Carbon;
use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * Class Administrator.
 *
 * @property Role[] $roles
 */
class Administrator extends Model implements AuthenticatableContract, JWTSubject
{
    use Authenticatable;
    use HasPermissions;
    use DefaultDatetimeFormat;

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }


    protected $fillable = ['username', 'password', 'name', 'avatar', 'created_at_text'];

    /**
     * Create a new Eloquent model instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $connection = config('admin.database.connection') ?: config('database.default');

        $this->setConnection($connection);

        $this->setTable(config('admin.database.users_table'));

        parent::__construct($attributes);
    }

    public static function boot()
    {
        parent::boot();

        self::creating(function ($m) {
            $first = isset($m->attributes['first_name']) ? trim($m->attributes['first_name']) : '';
            $last  = isset($m->attributes['last_name']) ? trim($m->attributes['last_name']) : '';
            if ($first !== '' && $last !== '') {
                $m->name = trim($first . ' ' . $last);
            }
        });
        self::updating(function ($m) {
            // Only rebuild name if first_name or last_name actually changed
            if ($m->isDirty('first_name') || $m->isDirty('last_name')) {
                $first = isset($m->attributes['first_name']) ? trim($m->attributes['first_name']) : '';
                $last  = isset($m->attributes['last_name']) ? trim($m->attributes['last_name']) : '';
                if ($first !== '' && $last !== '') {
                    $m->name = trim($first . ' ' . $last);
                }
            }
        });
    }


    /**
     * Get avatar attribute.
     *
     * @param string $avatar
     *
     * @return string
     */
    public function getAvatarAttribute($avatar)
    {
        $default = config('admin.default_avatar') ?: '/assets/images/user.jpg';

        // No avatar stored — use default
        if (empty($avatar)) {
            return admin_asset($default);
        }

        // Already a full URL — return as-is
        if (url()->isValidUrl($avatar)) {
            return $avatar;
        }

        // Relative path — resolve via configured Storage disk
        $disk = config('admin.upload.disk');
        if ($disk && array_key_exists($disk, config('filesystems.disks'))) {
            return Storage::disk($disk)->url($avatar);
        }

        return admin_asset($default);
    }


    public function programs()
    {
        return $this->hasMany(UserHasProgram::class, 'user_id');
    }

    public function program()
    {
        $p = UserHasProgram::where(['user_id' => $this->id])->first();
        if ($p == null) {
            $p = new UserHasProgram();
            $p->name = "No program";
        }
        return $p;
    }


    public function campus()
    {
        return $this->belongsTo(Campus::class, 'campus_id');
    }

    public function getCreatedAtTextAttribute($name)
    {
        return Carbon::parse($this->created_at)->diffForHumans();
    }


    /**
     * A user has and belongs to many roles.
     *
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        $pivotTable = config('admin.database.role_users_table');

        $relatedModel = config('admin.database.roles_model');

        return $this->belongsToMany($relatedModel, $pivotTable, 'user_id', 'role_id');
    }

    /**
     * A User has and belongs to many permissions.
     *
     * @return BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        $pivotTable = config('admin.database.user_permissions_table');

        $relatedModel = config('admin.database.permissions_model');

        return $this->belongsToMany($relatedModel, $pivotTable, 'user_id', 'permission_id');
    }
}
