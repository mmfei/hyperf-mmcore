<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace MmCore\Controller;

use Hyperf\Contract\TranslatorInterface;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use MMCore\Constants\ExceptionCode;
use MMCore\Exception\AuthException;
use Hyperf\Di\Annotation\Inject;
use Qbhy\HyperfAuth\AuthManager;

/**
 * Class NeedLoginController.
 * @Annotation
 */
class NeedLoginController
{
    /**
     * @Inject
     * @var AuthManager
     */
    protected $auth;
    /**
     * @Inject
     * @var RequestInterface
     */
    protected $request;

    /**
     * @Inject
     * @var ResponseInterface
     */
    protected $response;

    protected $language;

    /**
     * @Inject
     * @var TranslatorInterface
     */
    protected $translator;
    public function __construct() {
        $this->language = $this->request->input('language' , env('locale' , 'zh_CN'));
        $this->translator->setLocale($this->language);
    }
    protected function currentUser()
    {
        return $this->auth->guard()->user();
    }

    protected function getCurrentUser($is_need_login = false)
    {
        $user = self::currentUser();
        if ($is_need_login && empty($user)) {
            throw new AuthException($this->translator->trans('error_trans.need_auth'), ExceptionCode::NEED_AUTH);
        }
        return $user;
    }
}
