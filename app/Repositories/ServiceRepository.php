<?php

namespace App\Repositories;

use App\Models\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ServiceRepository
{
    public function active(): Collection
    {
        return Service::available()
            ->with(['category:id,name,type', 'provider:id,name,type'])
            ->latest()
            ->get();
    }

    public function paginateForAdmin(int $perPage = 15): LengthAwarePaginator
    {
        return Service::query()
            ->with(['category:id,name,type', 'provider:id,name,type'])
            ->latest()
            ->paginate($perPage);
    }
}
