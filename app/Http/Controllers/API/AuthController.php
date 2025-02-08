<?php
// 定義此檔案所屬的命名空間，方便 Laravel 自動載入此控制器類別
namespace App\Http\Controllers\API;

// 載入其他需要使用的類別

// 載入 Laravel 的 Controller 基底類別，方便我們繼承基本的控制器功能
use App\Http\Controllers\Controller;
// 載入 User 模型，用來與 users 資料表進行互動（建立、查詢使用者資料等）
use App\Models\User;
// 載入 Request 類別，用以處理 HTTP 請求中的資料
use Illuminate\Http\Request;
// 載入 Hash Facade，提供密碼加密與驗證功能
use Illuminate\Support\Facades\Hash;
// 載入 ValidationException 異常類別，當資料驗證失敗時拋出異常並回傳錯誤訊息
use Illuminate\Validation\ValidationException;

// 定義 AuthController 類別，繼承自 Controller 基底類別
class AuthController extends Controller
{
    /**
     * 使用者註冊方法
     *
     * 接收前端傳來的註冊資料，進行驗證、建立新使用者，
     * 並使用 Laravel Sanctum 產生 API Token 供後續 API 認證使用。
     *
     * @param Request $request HTTP 請求物件，包含使用者傳入的資料
     * @return \Illuminate\Http\JsonResponse JSON 格式的回應，包含使用者資料與 API Token
     */
    public function register(Request $request)
    {
        // 使用 $request->validate() 方法驗證傳入的資料，若驗證失敗會自動拋出異常
        $validated = $request->validate([
            // name 欄位必填、型態必須為字串，最大長度不超過 255 個字元
            'name'     => 'required|string|max:255',
            // email 欄位必填、型態必須為字串、格式必須正確、最大長度 255 且不可重複於 users 資料表中
            'email'    => 'required|string|email|max:255|unique:users',
            // password 欄位必填、型態必須為字串、最小長度 6 字元，並需有對應的 password_confirmation 欄位（由 confirmed 規則檢查）
            'password' => 'required|string|min:6|confirmed', // 前端需傳入 password_confirmation
        ]);

        // 使用驗證後的資料建立一個新的使用者記錄到資料庫
        $user = User::create([
            // 將 name 欄位設定為驗證後的 name 值
            'name'     => $validated['name'],
            // 將 email 欄位設定為驗證後的 email 值
            'email'    => $validated['email'],
            // 將 password 欄位設定為經由 Hash::make() 加密後的密碼，確保密碼不以明文儲存
            'password' => Hash::make($validated['password']),
        ]);

        // 使用 Laravel Sanctum 產生一個新的 API Token，token 名稱設定為 'auth_token'
        // plainTextToken 屬性會回傳純文字格式的 token
        $token = $user->createToken('auth_token')->plainTextToken;

        // 回傳 JSON 格式的回應，包含新建立的使用者資料與 API Token，HTTP 狀態碼為 201 (Created)
        return response()->json([
            'user'  => $user,
            'token' => $token,
        ], 201);
    }

    /**
     * 使用者登入方法
     *
     * 接收使用者登入的 email 與 password，驗證資料後比對密碼，
     * 若驗證成功則產生新的 API Token 並回傳使用者資料。
     *
     * @param Request $request HTTP 請求物件，包含登入時提交的資料
     * @return \Illuminate\Http\JsonResponse JSON 格式的回應，包含使用者資料與 API Token
     * @throws ValidationException 當認證失敗時拋出驗證例外
     */
    public function login(Request $request)
    {
        // 驗證登入資料，確保 email 與 password 欄位皆符合規則
        $request->validate([
            // email 必填、型態必須為字串、且必須符合 email 格式
            'email'    => 'required|string|email',
            // password 必填、型態必須為字串
            'password' => 'required|string',
        ]);

        // 根據請求中的 email 查詢資料庫，取得第一筆符合的使用者資料
        $user = User::where('email', $request->email)->first();

        // 如果查無使用者或是密碼驗證不正確，則拋出驗證異常
        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                // 回傳的錯誤訊息關聯在 email 欄位上
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // 若認證成功，使用 Sanctum 產生一個新的 API Token
        $token = $user->createToken('auth_token')->plainTextToken;

        // 回傳 JSON 格式的回應，包含使用者資料與新的 API Token
        return response()->json([
            'user'  => $user,
            'token' => $token,
        ]);
    }

    /**
     * 使用者登出方法
     *
     * 刪除目前使用者所使用的 API Token，達到登出的效果。
     *
     * @param Request $request HTTP 請求物件
     * @return \Illuminate\Http\JsonResponse JSON 格式的回應，包含登出成功的訊息
     */
    public function logout(Request $request)
    {
        // 取得當前使用者透過 token 驗證的使用者，並刪除當前存取的 token
        $request->user()->currentAccessToken()->delete();

        // 回傳 JSON 格式訊息，通知前端已成功登出
        return response()->json(['message' => 'Logged out']);
    }

    /**
     * 取得目前登入的使用者資料
     *
     * 回傳由中介層（middleware）解析出的當前使用者資訊。
     *
     * @param Request $request HTTP 請求物件
     * @return \Illuminate\Http\JsonResponse JSON 格式的回應，包含使用者資料
     */
    public function user(Request $request)
    {
        // 回傳當前請求所屬的使用者資料（通常由 Sanctum 驗證後自動附加到 Request 物件上）
        return response()->json($request->user());
    }
}
