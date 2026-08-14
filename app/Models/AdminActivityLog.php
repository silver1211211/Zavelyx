<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminActivityLog extends Model
{
    protected $fillable = [
        'admin_username',
        'action',
        'description',
        'subject_type',
        'subject_id',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public static function record(
        string $action,
        string $description,
        ?string $subjectType = null,
        ?int $subjectId = null,
        array $metadata = []
    ): void {
        $admin = session('admin_username', 'admin');
        $request = request();

        static::create([
            'admin_username' => $admin,
            'action'         => $action,
            'description'    => $description,
            'subject_type'   => $subjectType,
            'subject_id'     => $subjectId,
            'metadata'       => $metadata ?: null,
            'ip_address'     => $request?->ip(),
            'user_agent'     => $request?->userAgent(),
        ]);
    }
}
