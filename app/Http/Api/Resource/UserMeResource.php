<?php

namespace App\Http\Api\Resource;

class UserMeResource
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $signInEmailAddress;

    /**
     * @var string
     */
    private $apiToken;

    /**
     * @param string $id
     * @param string $username
     * @param string $signInEmailAddress
     * @param string $apiToken
     */
    public function __construct(
        string $id,
        string $username,
        string $signInEmailAddress,
        string $apiToken
    ) {
        $this->id = $id;
        $this->username = $username;
        $this->signInEmailAddress = $signInEmailAddress;
        $this->apiToken = $apiToken;
    }
}