<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    // 取得目前使用者所有交易記錄
    public function index(Request $request)
    {
        if (!$request->user()) {
            \Log::error('TransactionController@index: User not authenticated.');
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        $transactions = Transaction::where('user_id', $request->user()->id)->get();
        return response()->json($transactions);
    }

    // 新增一筆交易記錄
    public function store(Request $request)
    {
        if (!$request->user()) {
            \Log::error('TransactionController@store: User not authenticated.');
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        $validated = $request->validate([
            'date'        => 'required|date',
            'amount'      => 'required|numeric',
            'type'        => 'required|in:income,expense',
            'description' => 'nullable|string',
        ]);

        // 自動將 user_id 設為目前登入使用者
        $validated['user_id'] = $request->user()->id;

        $transaction = Transaction::create($validated);
        return response()->json($transaction, 201);
    }

    // 取得單筆交易記錄（必須屬於目前使用者）
    public function show(Request $request, $id)
    {
        if (!$request->user()) {
            \Log::error('TransactionController@show: User not authenticated.');
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        $transaction = Transaction::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();
        return response()->json($transaction);
    }

    // 更新交易記錄
    public function update(Request $request, $id)
    {
        if (!$request->user()) {
            \Log::error('TransactionController@update: User not authenticated.');
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        $transaction = Transaction::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $validated = $request->validate([
            'date'        => 'sometimes|required|date',
            'amount'      => 'sometimes|required|numeric',
            'type'        => 'sometimes|required|in:income,expense',
            'description' => 'nullable|string',
        ]);

        $transaction->update($validated);
        return response()->json($transaction);
    }

    // 刪除交易記錄
    public function destroy(Request $request, $id)
    {
        if (!$request->user()) {
            \Log::error('TransactionController@destroy: User not authenticated.');
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        $transaction = Transaction::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();
        $transaction->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }

    // 每月統計：根據傳入的 month (格式 "YYYY-MM") 統計收入、支出與淨額
    public function summary(Request $request)
    {
        if (!$request->user()) {
            \Log::error('TransactionController@summary: User not authenticated.');
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        $month = $request->query('month');  // 例如 "2025-02"
        if (!$month) {
            return response()->json(['error' => 'month parameter required'], 400);
        }
        $year = substr($month, 0, 4);
        $mon  = substr($month, 5, 2);

        $transactions = Transaction::where('user_id', $request->user()->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $mon)
            ->get();

        $income  = $transactions->where('type', 'income')->sum('amount');
        $expense = $transactions->where('type', 'expense')->sum('amount');

        return response()->json([
            'month'         => $month,
            'total_income'  => $income,
            'total_expense' => $expense,
            'net'           => $income - $expense,
        ]);
    }

    // 新增：根據年月篩選交易記錄
    public function filterByMonth(Request $request)
    {
        if (!$request->user()) {
            \Log::error('TransactionController@filterByMonth: User not authenticated.');
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        $month = $request->query('month');  // 例如 "2025-02"
        if (!$month) {
            return response()->json(['error' => 'month parameter required'], 400);
        }
        $year = substr($month, 0, 4);
        $mon  = substr($month, 5, 2);

        $transactions = Transaction::where('user_id', $request->user()->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $mon)
            ->get();

        return response()->json($transactions);
    }
}
