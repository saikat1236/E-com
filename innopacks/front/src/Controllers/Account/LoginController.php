<?php
/**
 * Copyright (c) Since 2024 InnoShop - All Rights Reserved
 *
 * @link       https://www.innoshop.com
 * @author     InnoShop <team@innoshop.com>
 * @license    https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

namespace InnoShop\Front\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use InnoShop\Common\Services\CartService;
use InnoShop\Front\Requests\LoginRequest;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LoginController extends Controller
{
    /**
     * @return mixed
     */
    public function index(): mixed
    {
        if (current_customer()) {
            return redirect(front_route('account.index'));
        }

        return view('account.login');
    }

    /**
     * Login request
     *
     * @param  LoginRequest  $request
     * @return JsonResponse
     */
    public function store(LoginRequest $request): JsonResponse
    {
        try {
            $oldGuestId  = current_guest_id();
            $redirectUri = session('front_redirect_uri');

            if (! auth('customer')->attempt($request->only('email', 'password'))) {
                throw new NotAcceptableHttpException(trans('front::login.account_or_password_error'));
            }

            $customer = current_customer();
            if (empty($customer)) {
                throw new NotFoundHttpException(trans('front::login.empty_customer'));
            }

            if (! $customer->active) {
                auth('customer')->logout();
                throw new \Exception(trans('front::login.inactive_customer'));
            }

            CartService::getInstance(current_customer_id())->mergeCart($oldGuestId);
            session()->forget('front_redirect_uri');
            $data = ['redirect_uri' => $redirectUri];

            return json_success(trans('front::login.login_success'), $data);
        } catch (\Exception $e) {
            return json_fail($e->getMessage());
        }
    }
}