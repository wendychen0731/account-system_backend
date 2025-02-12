<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Http\Controllers\Controller;

class StatisticsController extends Controller
{
    /**
     * 取得指定月份每日統計資料：每日總收入、總支出與淨額
     * 參數: month (格式: YYYY-MM)
     */
    public function dailySummary(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => '未登入或驗證失敗'], 401);
        }

        $month = $request->get('month'); // 例如 "2025-02"
        if (!$month) {
            return response()->json(['message' => '請提供月份參數 (格式: YYYY-MM)'], 400);
        }

        try {
            $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString();
            $endDate   = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->toDateString();
        } catch (\Exception $e) {
            \Log::error("日期格式錯誤: " . $e->getMessage());
            return response()->json(['message' => '月份格式錯誤，請使用 YYYY-MM 格式'], 400);
        }

        try {
            // 注意：由於 `date` 是 MySQL 的保留字，因此需要用反引號包起來
            $results = DB::table('transactions')
                ->select(
                    DB::raw("DATE_FORMAT(`date`, '%Y-%m-%d') as day"),
                    DB::raw("SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income"),
                    DB::raw("SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense")
                )
                ->where('user_id', $user->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->groupBy(DB::raw("DATE_FORMAT(`date`, '%Y-%m-%d')"))
                ->orderBy(DB::raw("DATE_FORMAT(`date`, '%Y-%m-%d')"), 'asc')
                ->get();

            // 計算每日淨額（收入 - 支出）
            $data = $results->map(function ($item) {
                $item->net = $item->total_income - $item->total_expense;
                return $item;
            });

            return response()->json($data);
        } catch (\Exception $e) {
            \Log::error("Error in dailySummary query: " . $e->getMessage());
            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }
}
