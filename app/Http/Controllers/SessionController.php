<?php

namespace App\Http\Controllers;


use Shippinno\Labs\Application\Service\AttendSessionRequest;
use Shippinno\Labs\Application\Service\AttendSessionService;
use Tanigami\ValueObjects\Time\TimeRange;
use Tobscure\JsonApi\Collection;
use Tobscure\JsonApi\Document;

class SessionController extends Controller
{
    public function attend(string $courseId, string $sessionId, AttendSessionService $attendSessionService)
    {
        $attendSessionService->execute(
            new AttendSessionRequest($courseId, $sessionId, '992ee3a3-4c69-4bd1-9357-b05dccb50a0e')
        );

        $this->managerRegistry->getManager()->flush();
    }
}