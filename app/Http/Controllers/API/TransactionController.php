<?php
// 開啟 PHP 程式碼區塊

namespace App\Http\Controllers\API;
// 定義本檔案所在的命名空間（namespace），方便自動載入（autoloading）與類別組織。
// 這裡我們將控制器放在 App\Http\Controllers\API 命名空間下，通常用於 API 相關的控制器

// 使用 (import) 其他命名空間中的類別，避免在使用時需要寫完整的命名空間路徑
use App\Http\Controllers\Controller;
// 引入應用程式的基礎控制器，該類別通常包含通用邏輯，可被其他控制器繼承

use App\Models\Transaction;
// 引入 Transaction 模型，此模型對應資料庫中的 transactions 資料表，用來進行資料操作（例如查詢、建立、更新和刪除）

use Illuminate\Http\Request;
// 引入 Laravel 的 HTTP 請求類別，封裝了使用者發出的 HTTP 請求中的所有資訊，例如查詢參數、POST 資料、檔案上傳等

// 定義 TransactionController 類別，繼承自 Controller 基底類別
class TransactionController extends Controller
{
    /**
     * 取得目前使用者所有交易記錄
     *
     * 此方法會從 HTTP 請求中取得當前登入的使用者，然後查詢該使用者的所有交易記錄。
     *
     * @param Request $request HTTP 請求物件，封裝所有請求資料
     * @return \Illuminate\Http\JsonResponse 回傳 JSON 格式的交易記錄集合
     */
    public function index(Request $request)
    {
        // 檢查請求中是否帶有已驗證的使用者物件（例如透過中介層驗證後加入 request）
        if (!$request->user()) {
            // 如果找不到使用者，記錄錯誤訊息到系統日誌中
            \Log::error('TransactionController@index: User not authenticated.');
            // 回傳 JSON 格式的錯誤訊息，HTTP 狀態碼設定為 401 (未授權)
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        // 利用 Transaction 模型查詢資料庫，條件為欄位 user_id 與當前使用者的 id 相符
        // ->get() 方法會取得所有符合條件的記錄，並回傳集合（Collection）
        $transactions = Transaction::where('user_id', $request->user()->id)->get();

        // 將查詢到的交易記錄集合轉換成 JSON 格式後回傳給用戶
        return response()->json($transactions);
    }

    /**
     * 新增一筆交易記錄
     *
     * 此方法會接收使用者輸入的交易資料，驗證資料正確性，並將交易記錄儲存到資料庫中。
     *
     * @param Request $request HTTP 請求物件，包含 POST 資料
     * @return \Illuminate\Http\JsonResponse 回傳新增成功的交易記錄，HTTP 狀態碼 201 (已建立)
     */
    public function store(Request $request)
    {
        // 檢查請求是否有帶入已驗證的使用者，若無則回傳未授權錯誤
        if (!$request->user()) {
            \Log::error('TransactionController@store: User not authenticated.');
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        // 驗證使用者輸入的資料，規則如下：
        // - 'date' 欄位：必填且必須是合法日期格式
        // - 'amount' 欄位：必填且必須是數字（可以是整數或小數）
        // - 'type' 欄位：必填且其值必須在 'income' 或 'expense' 之間
        // - 'description' 欄位：選填，但若有提供則必須是字串
        // 如果驗證失敗，Laravel 會自動拋出異常並回傳錯誤訊息
        $validated = $request->validate([
            'date'        => 'required|date',
            'amount'      => 'required|numeric',
            'type'        => 'required|in:income,expense',
            'description' => 'nullable|string',
        ]);

        // 將目前登入使用者的 id 自動加入到驗證後的資料中，
        // 防止使用者端自行修改或傳入其他使用者 id
        $validated['user_id'] = $request->user()->id;

        // 利用 Transaction 模型的 create 方法在資料庫中建立一筆新記錄
        // create 方法會將 $validated 陣列中的資料自動映射到資料表中的對應欄位
        $transaction = Transaction::create($validated);

        // 回傳新增成功的交易記錄，並將 HTTP 狀態碼設為 201 (Created)
        return response()->json($transaction, 201);
    }

    /**
     * 取得單筆交易記錄（必須屬於目前使用者）
     *
     * 此方法根據傳入的 $id 查詢單一交易記錄，但同時確認該記錄屬於目前登入的使用者。
     *
     * @param Request $request HTTP 請求物件
     * @param mixed $id 傳入的交易記錄 ID
     * @return \Illuminate\Http\JsonResponse 回傳查詢到的交易記錄
     */
    public function show(Request $request, $id)
    {
        // 確認使用者是否已登入，否則回傳 401 錯誤
        if (!$request->user()) {
            \Log::error('TransactionController@show: User not authenticated.');
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        // 使用 Transaction 模型查詢交易記錄：
        // 條件 1：id 欄位必須與傳入的 $id 相符
        // 條件 2：user_id 欄位必須與當前使用者 id 相符
        // firstOrFail() 方法：若查詢不到任何記錄，則自動拋出 ModelNotFoundException 並回傳 404 錯誤
        $transaction = Transaction::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        // 將查詢到的單筆交易記錄轉換為 JSON 格式回傳
        return response()->json($transaction);
    }

    /**
     * 更新交易記錄
     *
     * 此方法用於更新現有的交易記錄，使用者可以更新部分或全部欄位資料。
     *
     * @param Request $request HTTP 請求物件，包含更新資料
     * @param mixed $id 要更新的交易記錄 ID
     * @return \Illuminate\Http\JsonResponse 回傳更新後的交易記錄
     */
    public function update(Request $request, $id)
    {
        // 確認請求中是否有驗證過的使用者，否則回傳 401 未授權錯誤
        if (!$request->user()) {
            \Log::error('TransactionController@update: User not authenticated.');
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        // 先查詢出符合條件的交易記錄（必須為當前使用者所擁有）
        $transaction = Transaction::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        // 驗證更新資料，使用 "sometimes" 規則表示如果該欄位存在則必須通過驗證：
        // - 'date' 欄位：如果出現，則必須是有效日期
        // - 'amount' 欄位：如果出現，則必須是數字
        // - 'type' 欄位：如果出現，則必須是 'income' 或 'expense'
        // - 'description' 欄位：選填，若提供則必須為字串
        $validated = $request->validate([
            'date'        => 'sometimes|required|date',
            'amount'      => 'sometimes|required|numeric',
            'type'        => 'sometimes|required|in:income,expense',
            'description' => 'nullable|string',
        ]);

        // 利用 Eloquent 模型的 update 方法，將驗證後的資料更新到資料庫中對應的交易記錄上
        $transaction->update($validated);

        // 回傳更新後的交易記錄，並以 JSON 格式呈現
        return response()->json($transaction);
    }

    /**
     * 刪除交易記錄
     *
     * 此方法用於刪除指定的交易記錄，前提是該記錄必須屬於目前登入的使用者。
     *
     * @param Request $request HTTP 請求物件
     * @param mixed $id 要刪除的交易記錄 ID
     * @return \Illuminate\Http\JsonResponse 回傳刪除成功的訊息
     */
    public function destroy(Request $request, $id)
    {
        // 檢查請求是否有帶入已驗證的使用者，否則回傳 401 未授權錯誤
        if (!$request->user()) {
            \Log::error('TransactionController@destroy: User not authenticated.');
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        // 查詢符合條件的交易記錄，必須是當前使用者的記錄
        $transaction = Transaction::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        // 調用 Eloquent 的 delete 方法，將該筆記錄從資料庫中刪除
        $transaction->delete();

        // 回傳刪除成功的訊息，告知前端操作完成
        return response()->json(['message' => 'Deleted successfully']);
    }

    /**
     * 每月統計：根據傳入的 month (格式 "YYYY-MM") 統計收入、支出與淨額
     *
     * 此方法根據使用者傳入的月份字串，查詢該月所有交易記錄後，計算總收入、總支出以及淨額 (收入 - 支出)。
     *
     * @param Request $request HTTP 請求物件，從查詢參數中取得月份資訊
     * @return \Illuminate\Http\JsonResponse 回傳該月統計結果的 JSON 格式資料
     */
    public function summary(Request $request)
    {
        // 確認是否有有效的使用者登入
        if (!$request->user()) {
            \Log::error('TransactionController@summary: User not authenticated.');
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        // 從 URL 查詢參數中取得 'month'，例如 "2025-02"
        $month = $request->query('month');
        // 若未提供 month 參數，回傳錯誤訊息及 HTTP 狀態碼 400 (Bad Request)
        if (!$month) {
            return response()->json(['error' => 'month parameter required'], 400);
        }
        // 使用 substr 函數擷取年份：從字串起始位置取 4 個字元（YYYY）
        $year = substr($month, 0, 4);
        // 使用 substr 函數擷取月份：從第 5 個字元開始取 2 個字元（MM）
        $mon  = substr($month, 5, 2);

        // 查詢該使用者在指定年份與月份內的所有交易記錄
        // whereYear 與 whereMonth 分別用於過濾 date 欄位的年份與月份
        $transactions = Transaction::where('user_id', $request->user()->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $mon)
            ->get();

        // 從查詢到的交易記錄中過濾出 type 為 'income' 的記錄，並對 amount 欄位求和，得到總收入
        $income  = $transactions->where('type', 'income')->sum('amount');
        // 同理，過濾 type 為 'expense' 的記錄，並對 amount 欄位求和，得到總支出
        $expense = $transactions->where('type', 'expense')->sum('amount');

        // 回傳統計結果，包含：
        // - 'month'：原始傳入的月份字串
        // - 'total_income'：計算出的總收入
        // - 'total_expense'：計算出的總支出
        // - 'net'：淨額（總收入減總支出）
        return response()->json([
            'month'         => $month,
            'total_income'  => $income,
            'total_expense' => $expense,
            'net'           => $income - $expense,
        ]);
    }

    /**
     * 根據年月篩選交易記錄
     *
     * 此方法根據傳入的 month 參數（格式 "YYYY-MM"）來篩選並回傳該月的所有交易記錄。
     *
     * @param Request $request HTTP 請求物件，從查詢參數中取得月份資訊
     * @return \Illuminate\Http\JsonResponse 回傳該月的交易記錄集合
     */
    public function filterByMonth(Request $request)
    {
        // 檢查請求是否有有效的使用者登入，否則回傳 401 錯誤
        if (!$request->user()) {
            \Log::error('TransactionController@filterByMonth: User not authenticated.');
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        // 從查詢參數中取得 'month' 的值，預期格式為 "YYYY-MM"（例如 "2025-02"）
        $month = $request->query('month');
        // 如果未提供 month 參數，回傳錯誤訊息與 HTTP 400 狀態碼
        if (!$month) {
            return response()->json(['error' => 'month parameter required'], 400);
        }
        // 使用 substr 取出年份與月份：
        $year = substr($month, 0, 4); // 取出年份部分 (YYYY)
        $mon  = substr($month, 5, 2); // 取出月份部分 (MM)

        // 利用 Transaction 模型查詢該使用者在指定年份與月份內的所有交易記錄
        $transactions = Transaction::where('user_id', $request->user()->id)
            ->whereYear('date', $year)  // 篩選出 date 欄位中年份為 $year 的記錄
            ->whereMonth('date', $mon)  // 篩選出 date 欄位中月份為 $mon 的記錄
            ->get();

        // 將查詢結果轉換為 JSON 格式回傳給用戶
        return response()->json($transactions);
    }
}
