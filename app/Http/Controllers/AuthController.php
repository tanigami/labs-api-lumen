<?php

namespace App\Http\Controllers;

use App\Http\Api\DataTransformer\UserMeResourceDataTransformer;
use App\Http\Api\Resource\UserMeResource;
use Illuminate\Http\Request;
use Shippinno\Labs\Application\DataTransformer\Lab\UserDtoDataTransformer;
use Shippinno\Labs\Application\Service\SignInUserRequest;
use Shippinno\Labs\Application\Service\SignInUserService;
use Shippinno\Labs\Application\Service\SignUpUserRequest;
use Shippinno\Labs\Application\Service\SignUpUserService;
use Shippinno\Labs\Domain\Model\User\UserNotFoundException;

class AuthController extends Controller
{
    public function signUp(SignUpUserService $signUpUserService)
    {
        $signUpUserService->execute(new SignUpUserRequest(
            'tanigami',
            'yoyoyo'
        ));

        $this->managerRegistry->getManager()->flush();
    }

    /**
     * @param Request $request
     * @param SignInUserService $signInUserService
     */
    public function signIn(Request $request, SignInUserService $signInUserService)
    {
        try {
            $user = $signInUserService->execute(
                new SignInUserRequest(
                    $request->json('email'),
                    $request->json('password')
                ),
                new UserMeResourceDataTransformer
            );
        } catch (UserNotFoundException $e) {
            abort(403);
        }
        $this->managerRegistry->getManager()->flush();

        return $this->hateoas->serialize($user, 'json');
    }
}