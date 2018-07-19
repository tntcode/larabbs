<?php

namespace App\Http\Controllers\Api;

use Auth;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\Api\SocialAuthorizationRequest;
use App\Http\Requests\Api\AuthorizationRequest;
use Zend\Diactoros\Response as Psr7Response;
use Psr\Http\Message\ServerRequestInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\AuthorizationServer;

class AuthorizationsController extends Controller
{

    /**
     * 第三方登录
     * @param  [type]                     $type    [description]
     * @param  SocialAuthorizationRequest $request [description]
     * @return [type]                              [description]
     */
    public function socialStore($type, SocialAuthorizationRequest $request)
    {
        if (!in_array($type, ['weixin'])) {
            return $this->response->errorBadRequest();
        }

        $driver = \Socialite::driver($type);

        try {
            if ($code = $request->code) {
                $response = $driver->getAccessTokenResponse($code);
                $token = array_get($response, 'access_token');
            } else {
                $token = $request->access_token;

                if ($type == 'weixin') {
                    $driver->setOpenId($request->openid);
                }
            }

            $oauthUser = $driver->userFromToken($token);
        } catch (\Exception $e) {
            return $this->response->errorUnauthorized('参数错误，未获取用户信息');
        }

        switch ($type) {
            case 'weixin':
                $unionid = $oauthUser->offsetExists('unionid') ? $oauthUser->offsetGet('unionid') : null;

                if ($unionid) {
                    $user = User::where('weixin_unionid', $unionid)->first();
                } else {
                    $user = User::where('weixin_openid', $oauthUser->getId())->first();
                }

                // 没有用户，默认创建一个用户
                if (!$user) {
                    $user = User::create([
                        'name' => $oauthUser->getNickname(),
                        'avatar' => $oauthUser->getAvatar(),
                        'weixin_openid' => $oauthUser->getId(),
                        'weixin_unionid' => $unionid,
                    ]);
                }

                break;
        }

        $token = Auth::guard('api')->fromUser($user);
        return $this->respondWithToken($token)->setStatusCode(201);
    }

    /**
     * JWT的用户名/密码登录
     * @param  AuthorizationRequest $request [description]
     * @return [type]                        [description]
     */
    /*public function store(AuthorizationRequest $request)
    {
        $username = $request->username;

        filter_var($username, FILTER_VALIDATE_EMAIL) ?
            $credentials['email'] = $username :
            $credentials['phone'] = $username;

        $credentials['password'] = $request->password;

        if (!$token = \Auth::guard('api')->attempt($credentials)) {
            return $this->response->errorUnauthorized(trans('auth.failed'));
        }

        return $this->respondWithToken($token)->setStatusCode(201);
    }*/

    /**
     * OAuth Password的用户名/密码登录
     * @param  AuthorizationRequest   $originRequest [description]
     * @param  AuthorizationServer    $server        [description]
     * @param  ServerRequestInterface $serverRequest [description]
     * @return [type]                                [description]
     */
    public function store(AuthorizationRequest $originRequest, AuthorizationServer $server, ServerRequestInterface $serverRequest)
    {
        try {
           return $server->respondToAccessTokenRequest($serverRequest, new Psr7Response)->withStatus(201);
        } catch(OAuthServerException $e) {
            return $this->response->errorUnauthorized($e->getMessage());
        }
    }

    /**
     * JWT的刷新token
     * @return [type] [description]
     */
    /*public function update()
    {
        $token = Auth::guard('api')->refresh();
        return $this->respondWithToken($token);
    }*/

    /**
     * OAuth Password的刷新token
     * @param  AuthorizationServer    $server        [description]
     * @param  ServerRequestInterface $serverRequest [description]
     * @return [type]                                [description]
     */
    public function update(AuthorizationServer $server, ServerRequestInterface $serverRequest)
    {
        try {
           return $server->respondToAccessTokenRequest($serverRequest, new Psr7Response);
        } catch(OAuthServerException $e) {
            return $this->response->errorUnauthorized($e->getMessage());
        }
    }

    /**
     * JWT的删除token
     * @return [type] [description]
     */
    /*public function destroy()
    {
        Auth::guard('api')->logout();
        return $this->response->noContent();
    }*/

    /**
     * OAuth Password的删除token
     * @return [type] [description]
     */
    public function destroy()
    {
        $this->user()->token()->revoke();
        return $this->response->noContent();
    }

    protected function respondWithToken($token)
    {
        return $this->response->array([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => \Auth::guard('api')->factory()->getTTL() * 60
        ]);
    }
}