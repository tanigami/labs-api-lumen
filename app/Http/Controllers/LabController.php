<?php

namespace App\Http\Controllers;

use App\Http\Api\DataTransformer\CourseResourceDataTransformer;
use DateTime;
use DateTimeImmutable;
use Doctrine\Common\Collections\ExpressionBuilder;
use Doctrine\Common\Persistence\ManagerRegistry;
use Hateoas\Representation\CollectionRepresentation;
use Hateoas\Representation\PaginatedRepresentation;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use League\Fractal\Manager;
use Shippinno\Labs\Application\Command\Lab\CreateLab;
use Shippinno\Labs\Application\Command\Lab\DeleteLab;
use Shippinno\Labs\Application\Command\Lab\DeleteLabCommand;
use Shippinno\Labs\Application\Query\FilterLabs;
use Shippinno\Labs\Application\Query\FilterLabsHandler;
use Shippinno\Labs\Application\Query\LabOrdering;
use Shippinno\Labs\Application\Query\Limiting;
use Shippinno\Labs\Application\Query\FetchLab;
use Shippinno\Labs\Application\Query\FetchLabHandler;
use Shippinno\Labs\Application\Query\QueryBus;
use Shippinno\Labs\Application\Service\AddSessionToCourseRequest;
use Shippinno\Labs\Application\Service\AddSessionToCourseService;
use Shippinno\Labs\Application\Service\CreateLabRequest;
use Shippinno\Labs\Application\Service\CreateCourseService;
use Shippinno\Labs\Application\Service\DeleteCourseRequest;
use Shippinno\Labs\Application\Service\DeleteCourseService;
use Shippinno\Labs\Application\Service\EnrollInCourseRequest;
use Shippinno\Labs\Application\Service\EnrollInCourseService;
use Shippinno\Labs\Application\Service\RemoveSessionFromCourseRequest;
use Shippinno\Labs\Application\Service\RemoveSessionFromCourseService;
use Shippinno\Labs\Application\Service\UpdateSessionRequest;
use Shippinno\Labs\Application\Service\UpdateSessionService;
use Shippinno\Labs\Domain\Model\Lab\LabNotFoundException;
use Shippinno\Labs\Domain\Model\Lab\SessionNotFoundException;
use Tanigami\ValueObjects\Time\TimeRange;
use Tobscure\JsonApi\Collection;
use Tobscure\JsonApi\Document;
use Tobscure\JsonApi\Parameters;

class LabController extends Controller
{
    /**
     * @OAS\Get(
     *     path="/courses",
     *     summary="Finds courses",
     *     description="Search!",
     *     operationId="whatisoperatiois",
     *     @OAS\Parameter(
     *         name="status",
     *         in="query",
     *         description="Status values that needed to be considered for filter",
     *         required=true,
     *         explode=true,
     *         @OAS\Schema(
     *             type="array",
     *             default="available",
     *             @OAS\Items(
     *                 type="string",
     *                 enum = {"available", "pending", "sold"},
     *             )
     *         )
     *     ),
     *     @OAS\Response(
     *         response=200,
     *         description="successful operation",
     *         @OAS\MediaType(
     *             mediaType="application/json",
     *             @OAS\Schema(
     *                 type="array",
     *                 @OAS\Items(
     *                    ref="#/components/schemas/CourseResource"
     *                 )
     *             )
     *         )
     *     ),
     *     @OAS\Response(
     *         response=400,
     *         description="Invalid status value"
     *     ),
     *     security={
     *         {"petstore_auth": {"write:pets", "read:pets"}}
     *     }
     * )
     */
    public function index(Request $request, FilterLabsHandler $queryHandler)
    {
        $page = $request->query('page', 1);
        $limit = $request->query('limit', 200);
        $filteringExpressions = $this->filteringExpressions($request);

        $result = $queryHandler->handle(
            new FilterLabs(
                $filteringExpressions,
                new LabOrdering(
                    LabOrdering::ORDER_BY_NAME,
                    LabOrdering::DIRECTION_ASC
                ),
                new Limiting($limit)
            ),
            new CourseResourceDataTransformer
        );

        $paginated = new PaginatedRepresentation(
            new CollectionRepresentation($result['resources'], 'courses'),
            'courses.index',
            compact(['page', 'filter']),
            $page,
            $limit,
            ceil($result['total'] / $limit),
            'page',
            'limit',
            true,
            $result['total']
        );

        return $this->hateoas->serialize($paginated, 'json');
    }

    public function show(FetchLabHandler $queryHandler, string $courseId)
    {
        $resource = $queryHandler->handle(
            new FetchLab($courseId),
            new CourseResourceDataTransformer
        );

        return $this->hateoas->serialize($resource, 'json');
    }
    
    public function store(Request $request, CreateCourseService $createCourseService)
    {
//        http://localhost:3000/callback
//        learnapptest.auth0.com
//        YKF45wdjFYcrYYVe165wbUuevbV3ZCy9
//        ryTHnTEMJWHzn5qxc3Ha1td5JFHDdlYj2WDmbX2rJSu78-GCOOU5MOPcB7ob5x6d

        if (!isset($request->auth)) {
            abort(401);
        }

        $command = new CreateLab(
            $request->auth,
            $request->json('name'),
            $request->json('subject'),
            $request->json('overview'),
            $request->json('capacity')
        );
        $this->commandBus->handle($command);

        $this->managerRegistry->getManager()->flush();

        return response(null, 201, [
            'Location' => route('labs.show', ['labId' => $command->labId()]),
        ]);
    }

    public function update(string $courseId, Request $request, Container $container)
    {
//        $this->validate($request, [
//            'action' => ['required', 'in:removeSession'],
//        ]);

        // addSession, removeSession, updateSession
        return $container->call([$this, $request->json('action')], ['courseId' => $courseId]);
    }

    public function join(string $courseId, Request $request, EnrollInCourseService $enrollInCourseService)
    {
        $enrollInCourseService->execute(
            new EnrollInCourseRequest($courseId, $request->auth)
        );
    }

    public function addSession(string $courseId, Request $request, AddSessionToCourseService $addSessionToCourseService)
    {
        try {
            $resource = $addSessionToCourseService->execute(
                new AddSessionToCourseRequest(
                    $courseId,
                    $request->auth,
                    $request->json('title'),
                    new TimeRange(
                        DateTimeImmutable::createFromFormat(DateTime::ATOM, $request->json('start')),
                        DateTimeImmutable::createFromFormat(DateTime::ATOM, $request->json('end'))
                    ),
                    $request->json('outline')
                ),
                new CourseResourceDataTransformer
            );
        } catch (LabNotFoundException $e) {
            abort(404);
        }

        $this->managerRegistry->getManager()->flush();

        return $this->hateoas->serialize($resource, 'json');
    }

    public function updateSession(string $courseId, Request $request, UpdateSessionService $updateSessionService)
    {
        try {
            $resource = $updateSessionService->execute(
                new UpdateSessionRequest(
                    $courseId,
                    $request->json('sessionId'),
                    $request->json('title'),
                    new TimeRange(
                        DateTimeImmutable::createFromFormat(DateTime::ATOM, $request->json('start')),
                        DateTimeImmutable::createFromFormat(DateTime::ATOM, $request->json('end'))
                    ),
                    $request->json('outline')
                ),
                new CourseResourceDataTransformer
            );
        } catch (LabNotFoundException $e) {
            abort(404);
        } catch (SessionNotFoundException $e) {
            abort(404);
        }

        $this->managerRegistry->getManager()->flush();

        return $this->hateoas->serialize($resource, 'json');
    }

    public function removeSession(string $courseId, Request $request, RemoveSessionFromCourseService $removeSessionFromCourseService)
    {
        try {
            $removeSessionFromCourseService->execute(
                new RemoveSessionFromCourseRequest($courseId, $request->json('session_id'))
            );
            $this->managerRegistry->getManager()->flush();
        } catch (LabNotFoundException $e) {
            abort(404);
        } catch (SessionNotFoundException $e) {
            abort(404);
        }

        $this->managerRegistry->getManager()->flush();
    }

    public function enroll(EnrollInCourseService $enrollInCourseService)
    {
        $enrollInCourseService->execute(new EnrollInCourseRequest('1b87efd6-5d19-42c6-bab3-33e3953a1eaa', '992ee3a3-4c69-4bd1-9357-b05dccb50a0e'));
        $this->managerRegistry->getManager()->flush();
    }

    /**
     * @param Request $request
     * @param string $courseId
     */
    public function destroy(Request $request, string $courseId)
    {
        try {
            $this->commandBus->handle(new DeleteLab($request->auth, $courseId));
        } catch (LabNotFoundException $e) {
            abort(404);
        }

        $this->managerRegistry->getManager()->flush();
    }
}