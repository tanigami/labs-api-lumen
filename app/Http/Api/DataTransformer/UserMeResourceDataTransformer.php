<?php

namespace App\Http\Api\DataTransformer;

use App\Http\Api\Resource\UserMeResource;
use Shippinno\Labs\Application\DataTransformer\UserDataTransformer;
use Shippinno\Labs\Domain\Model\User\User;

class UserMeResourceDataTransformer implements UserDataTransformer
{
    /**
     * @var User
     */
    private  $user;

    /**
     * @param User $user
     */
    public function write(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @return mixed
     */
    public function read()
    {
        return new UserMeResource(
            $this->user->userId()->id(),
            $this->user->username(),
            $this->user->loginCredentials()->emailAddress()->emailAddress(),
            $this->user->apiToken()->apiToken()
        );
    }
}