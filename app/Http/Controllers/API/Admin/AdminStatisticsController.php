<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Models\Profile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminStatisticsController extends Controller
{
    /**
     * Konstruktor - jogosultságok ellenőrzése
     */
    public function __construct()
    {
        $this->middleware('permission:view statistics');
    }

    /**
     * Összefoglaló statisztikák
     */
    public function summary(): JsonResponse
    {
        $stats = [
            'total_users' => User::count(),
            'total_cards' => Card::count(),
            'active_cards' => Card::where('status', Card::STATUS_ACTIVE)->count(),
            'inactive_cards' => Card::where('status', Card::STATUS_INACTIVE)->count(),
            'expired_cards' => Card::where('status', Card::STATUS_EXPIRED)->count(),
            'total_profiles' => Profile::count(),
            'public_profiles' => Profile::where('is_public', true)->count(),
            'private_profiles' => Profile::where('is_public', false)->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Kártyák statisztikái időszakonként
     */
    public function cardsOverTime(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'required|in:day,week,month,year',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->subMonths(6);
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now();

        $format = match($request->period) {
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u', // ISO hét
            'month' => '%Y-%m',
            'year' => '%Y',
        };

        $data = DB::table('cards')
            ->select(DB::raw("DATE_FORMAT(created_at, '{$format}') as period"))
            ->selectRaw('COUNT(*) as card_count')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw("DATE_FORMAT(created_at, '{$format}')"))
            ->orderBy('period', 'asc')
            ->get();

        return response()->json($data);
    }

    /**
     * Profilok látogatottsága
     */
    public function profileVisits(): JsonResponse
    {
        $topProfiles = Profile::select('id', 'name', 'visits')
            ->where('visits', '>', 0)
            ->orderBy('visits', 'desc')
            ->limit(10)
            ->get();

        return response()->json($topProfiles);
    }

    /**
     * Kártya típusok eloszlása
     */
    public function cardTypeDistribution(): JsonResponse
    {
        $distribution = DB::table('cards')
            ->join('card_types', 'cards.card_type_id', '=', 'card_types.id')
            ->select('card_types.name', DB::raw('COUNT(*) as count'))
            ->groupBy('card_types.name')
            ->orderBy('count', 'desc')
            ->get();

        return response()->json($distribution);
    }

    /**
     * Felhasználók növekedése
     */
    public function userGrowth(): JsonResponse
    {
        $monthly = DB::table('users')
            ->select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"))
            ->selectRaw('COUNT(*) as new_users')
            ->where('created_at', '>=', Carbon::now()->subYear())
            ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y-%m')"))
            ->orderBy('month', 'asc')
            ->get();

        return response()->json($monthly);
    }
}
