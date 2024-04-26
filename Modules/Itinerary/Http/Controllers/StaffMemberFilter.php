<?php

namespace Modules\Itinerary\Http\Controllers;

use Illuminate\Database\Query\Builder;
use Spatie\QueryBuilder\AllowedFilter;

class StaffMemberFilter extends AllowedFilter
{
    public function __invoke(Builder $query, $value, string $property)
    {
        // Extract the staff member ID from the filter value
        $staffMemberId = $value;

        // Query the pivot table to filter users based on staff member ID
        $query->whereHas('staff_member_schedules', function ($query) use ($staffMemberId) {
            $query->wherePivot('staff_member_id', $staffMemberId);
        });
    }
}

