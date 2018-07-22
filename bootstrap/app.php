<?php

use App\Http\Middleware\Auth0Middleware;
use Auth0\SDK\JWTVerifier;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\Client;
use Hateoas\Hateoas;
use Hateoas\HateoasBuilder;
use Hateoas\UrlGenerator\CallableUrlGenerator;
use Laravel\Lumen\Application;
use Laravel\Lumen\Routing\UrlGenerator;
use League\Tactician\CommandBus;
use League\Tactician\Container\ContainerLocator;
use League\Tactician\Handler\CommandHandlerMiddleware;
use League\Tactician\Handler\CommandNameExtractor\ClassNameExtractor;
use League\Tactician\Handler\MethodNameInflector\HandleClassNameInflector;
use League\Tactician\Handler\MethodNameInflector\HandleInflector;
use Shippinno\Labs\Application\Command\Lab\CreateLab;
use Shippinno\Labs\Application\Command\Lab\CreateLabHandler;
use Shippinno\Labs\Application\Command\Lab\DeleteLab;
use Shippinno\Labs\Application\Command\Lab\DeleteLabHandler;
use Shippinno\Labs\Application\DataTransformer\LabDtoDataTransformer;
use Shippinno\Labs\Application\DataTransformer\SessionDtoDataTransformer;
use Shippinno\Labs\Application\Query\AllCoursesQueryHandler;
use Shippinno\Labs\Application\Query\OneLabHandler;
use Shippinno\Labs\Application\Query\QueryBus;
use Shippinno\Labs\Application\Query\SessionsOfCourseQueryHandler;
use Shippinno\Labs\Domain\Model\Lab\Lab;
use Shippinno\Labs\Domain\Model\Lab\LabRepository;
use Shippinno\Labs\Domain\Model\Lab\CourseSpecificationFactory;
use Shippinno\Labs\Domain\Model\Lab\Enrollment;
use Shippinno\Labs\Domain\Model\Lab\EnrollmentRepository;
use Shippinno\Labs\Domain\Model\Lab\Session;
use Shippinno\Labs\Domain\Model\Lab\SessionRepository;
use Shippinno\Labs\Domain\Model\Lab\SessionSpecificationFactory;
use Shippinno\Labs\Domain\Model\User\PasswordHashing;
use Shippinno\Labs\Domain\Model\User\User;
use Shippinno\Labs\Domain\Model\User\UserRepository;
use Shippinno\Labs\Domain\Model\User\UserSpecificationFactory;
use Shippinno\Labs\Infrastructure\Domain\Model\Lab\DoctrineCourseSpecificationFactory;
use Shippinno\Labs\Infrastructure\Domain\Model\Lab\DoctrineSessionSpecificationFactory;
use Shippinno\Labs\Infrastructure\Domain\Model\User\Auth0UserRepository;
use Shippinno\Labs\Infrastructure\Domain\Model\User\BcryptPasswordHashing;
use Shippinno\Labs\Infrastructure\Domain\Model\User\DoctrineUserSpecificationFactory;
use Shippinno\Learn\Infrastructure\Persistence\Doctrine\EntityManagerFactory;

require_once __DIR__.'/../vendor/autoload.php';

try {
    (new Dotenv\Dotenv(__DIR__.'/../'))->load();
} catch (Dotenv\Exception\InvalidPathException $e) {
    //
}

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

$app = new Laravel\Lumen\Application(
    realpath(__DIR__.'/../')
);

// $app->withFacades();

// $app->withEloquent();

AnnotationRegistry::registerLoader('class_exists');

/*
|--------------------------------------------------------------------------
| Configuration
|--------------------------------------------------------------------------|
*/

$app->configure('cors');

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);


$app->singleton(LabRepository::class, function (Application $app) {
    return $app->get(EntityManager::class)->getRepository(Lab::class);
});
$app->singleton(SessionRepository::class, function (Application $app) {
    return $app->get(EntityManager::class)->getRepository(Session::class);
});
$app->singleton(UserRepository::class, function (Application $app) {
    return new Auth0UserRepository(new Client());
});

$app->singleton(CourseSpecificationFactory::class, DoctrineCourseSpecificationFactory::class);
$app->singleton(SessionSpecificationFactory::class, DoctrineSessionSpecificationFactory::class);
$app->singleton(UserSpecificationFactory::class, DoctrineUserSpecificationFactory::class);

//$app->singleton(QueryBus::class, function (Application $app) {
//    $queryBus = new QueryBus;
//    $queryBus->register(new AllCoursesQueryHandler($app->get(CourseRepository::class), new LabDtoDataTransformer));
//    $queryBus->register(new OneLabHandler($app->get(CourseRepository::class), new LabDtoDataTransformer));
//    $queryBus->register(
//        new SessionsOfCourseQueryHandler(
//            $app->get(SessionSpecificationFactory::class),
//            $app->get(SessionRepository::class),
//            new SessionDtoDataTransformer,
//            $app['em']
//        ));
//
//    return $queryBus;
//});

$app->bind(OneLabHandler::class, function (Application $app) {
    return new OneLabHandler($app->get(LabRepository::class), new LabDtoDataTransformer) ;
});

$app->bind(SessionsOfCourseQueryHandler::class, function (Application $app) {
    return new SessionsOfCourseQueryHandler(
        $app->get(SessionSpecificationFactory::class),
        $app->get(SessionRepository::class),
        new SessionDtoDataTransformer,
        $app['em']
    );
});

$app->singleton(Hateoas::class, function (Application $app) {
    return HateoasBuilder::create()
        ->setUrlGenerator(
            null, // By default all links uses the generator configured with the null name
            new CallableUrlGenerator(function (string $route, array $parameters, bool $absolute) use ($app) {
                /** @var \Laravel\Lumen\Routing\UrlGenerator $urlGenerator */
                $urlGenerator = $app->make(UrlGenerator::class);
                return $urlGenerator->route($route, $parameters, $absolute);
            })
        )
        ->build();
});

$app->singleton(CreateLabHandler::class, function (Application $app) {
    return new CreateLabHandler(
        $app->get(LabRepository::class),
        $app->get(UserRepository::class)
    );
});
$app->singleton(DeleteLabHandler::class, function (Application $app) {
    return new DeleteLabHandler(
        $app->get(LabRepository::class),
        $app->get(UserRepository::class)
    );
});

$app->singleton(CommandBus::class, function (Application $app) {
    $containerLocator =  new ContainerLocator($app, [
        CreateLab::class => CreateLabHandler::class,
        DeleteLab::class => DeleteLabHandler::class,
     ]);
    $handlerMiddleware = new CommandHandlerMiddleware(
        new ClassNameExtractor,
        $containerLocator,
        new HandleInflector
    );
    return new CommandBus([$handlerMiddleware]);
});

$app->singleton(\Auth0\SDK\JWTVerifier::class, function (Application $app) {
    return new JWTVerifier([
        'supported_algs' => ['RS256'],
        'valid_audiences' => [env('AUTH0_API_AUDIENCE')],
        'authorized_iss' => [env('AUTH0_DOMAIN')]
    ]);
});

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/

$app->middleware([
    \Barryvdh\Cors\HandleCors::class,
//    App\Http\Middleware\ExampleMiddleware::class
]);

$app->routeMiddleware([
    'auth' => Auth0Middleware::class,
]);

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/

$app->register(\LaravelDoctrine\ORM\DoctrineServiceProvider::class);
$app->register(\Barryvdh\Cors\ServiceProvider::class);

// $app->register(App\Providers\AppServiceProvider::class);
$app->register(App\Providers\AuthServiceProvider::class);
// $app->register(App\Providers\EventServiceProvider::class);

/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/

$app->router->group([
    'namespace' => 'App\Http\Controllers',
], function ($router) {
    require __DIR__.'/../routes/web.php';
});

return $app;
