<?php

namespace Tests\Feature\Http;

use Auth0\SDK\JWTVerifier;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Mockery;
use Shippinno\Labs\Domain\Model\User\UserBuilder;
use Shippinno\Labs\Domain\Model\User\UserId;
use Shippinno\Labs\Domain\Model\User\UserRepository;
use Shippinno\Labs\Infrastructure\Domain\Model\User\InMemoryUserRepository;
use Tests\TestCase;

class LabTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        if (!$this->hasDependencies()) {
            var_dump('yoyoyoyoyoyoyoy');
            $this->initializeDatabase();
        }
    }

    public function testCreateLab()
    {
        $jwt = $this->validJwt();
        $verifier = Mockery::mock(JWTVerifier::class);
        $verifier->shouldReceive('verifyAndDecode')
            ->with($jwt)
            ->andReturn(
                json_decode('{"sub":"USER_ID"}')
            );
        $this->app->instance(JWTVerifier::class, $verifier);

        $userRepository = new InMemoryUserRepository;
        $userRepository->save(UserBuilder::user()->withUserId(new UserId('USER_ID'))->build());
        $this->app->instance(UserRepository::class, $userRepository);

        $response = $this
            ->json('POST','/api/labs', [
                'name' => 'NEW_LAB_NAME',
                'subject' => 'NEW_LAB_SUBJECT',
                'overview' => 'NEW_LAB_OVERVIEW',
                'capacity' => 123
            ], [
                'Authorization' => 'Bearer ' . $jwt,
            ])
            ->response;

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('', $response->getContent());
        $location = $response->headers->get('Location');
        $this->get($location)->seeStatusCode(200);

        $labId = explode($this->baseUrl . '/api/labs/', $location)[1];

        $response = $this->json(
            'DELETE',
            '/api/labs/' . $labId,
            [],
            ['Authorization' => 'Bearer ' . $jwt]
        )->response;
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('', $response->getContent());

        return $labId;
    }



    /**
     * @depends testCreateLab
     */
//    public function testLabCannotBeDeletedByNonOwner()
//    {
//        $this->json('DELETE','/api/labs/', [
//            'name' => 'name',
//            'subject' => 'subject',
//            'overview' => 'overview',
//            'capacity' => 3
//        ], [
//            'Authorization' => 'Bearer ' . $jwt,
//        ])
//            ->seeStatusCode(201)
//            ->seeJsonEquals([]);
//    }

//    public function testCreateLabWithoutAuthorization()
//    {
//        dd($this
//            ->json('POST','/api/labs', [
//                'name' => 'name',
//                'subject' => 'subject',
//                'overview' => 'overview',
//                'capacity' => 3
//            ])
//            ->response->exception);
////            ->seeStatusCode(401);
//    }

    private function validJwt()
    {
        $jwt = 'jwt';
        $verifier = Mockery::mock(JWTVerifier::class);
        $verifier->shouldReceive('verifyAndDecode')
            ->with($jwt)
            ->andReturn(
                json_decode('{"sub":"USER_ID"}')
            );
        $this->app->instance(JWTVerifier::class, $verifier);

        return $jwt;
    }
}