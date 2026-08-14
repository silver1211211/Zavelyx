<?php

namespace App\Services\Providers;

use App\Models\Provider;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProviderService
{
    public function paginateForAdmin(int $perPage = 15): LengthAwarePaginator
    {
        return Provider::query()->latest()->paginate($perPage);
    }
}
