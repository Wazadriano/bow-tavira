<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkItem;
use App\Models\GovernanceItem;
use App\Models\Supplier;
use App\Models\Risk;
use App\Models\SettingList;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        $type = $request->input('type');

        $results = [];

        if (!$type || $type === 'workitems') {
            $workItems = WorkItem::where('title', 'like', "%{$query}%")
                ->orWhere('description', 'like', "%{$query}%")
                ->limit(10)
                ->get();
            $results['workitems'] = $workItems;
        }

        if (!$type || $type === 'governance') {
            $governance = GovernanceItem::where('title', 'like', "%{$query}%")
                ->orWhere('description', 'like', "%{$query}%")
                ->limit(10)
                ->get();
            $results['governance'] = $governance;
        }

        if (!$type || $type === 'suppliers') {
            $suppliers = Supplier::where('name', 'like', "%{$query}%")
                ->limit(10)
                ->get();
            $results['suppliers'] = $suppliers;
        }

        if (!$type || $type === 'risks') {
            $risks = Risk::where('title', 'like', "%{$query}%")
                ->orWhere('description', 'like', "%{$query}%")
                ->limit(10)
                ->get();
            $results['risks'] = $risks;
        }

        return response()->json($results);
    }

    public function tags(Request $request): JsonResponse
    {
        $tags = WorkItem::whereNotNull('tags')
            ->pluck('tags')
            ->flatten()
            ->unique()
            ->values();

        return response()->json($tags);
    }

    public function departments(Request $request): JsonResponse
    {
        $departments = SettingList::where('type', 'department')
            ->where('is_active', true)
            ->orderBy('label')
            ->get();

        return response()->json($departments);
    }

    public function activities(Request $request): JsonResponse
    {
        $activities = SettingList::where('type', 'activity')
            ->where('is_active', true)
            ->orderBy('label')
            ->get();

        return response()->json($activities);
    }
}
