<?php

namespace App\Http\Api\DataTransformer;

use App\Http\Api\Resource\CourseResource;
use App\Http\Api\Resource\EnrollmentResource;
use App\Http\Api\Resource\SessionResource;
use DateTime;
use Shippinno\Labs\Application\DataTransformer\LabDataTransformer;
use Shippinno\Labs\Domain\Model\Lab\Lab;
use Shippinno\Labs\Domain\Model\Lab\Enrollment;
use Shippinno\Labs\Domain\Model\Lab\Session;

class CourseResourceDataTransformer implements LabDataTransformer
{
    /**
     * @var Lab
     */
    private $lab;

    /**
     * @param Lab $lab
     */
    public function write(Lab $lab): void
    {
        $this->lab = $lab;
    }

    /**
     * @return mixed
     */
    public function read(): CourseResource
    {
        return new CourseResource(
            $this->lab->labId()->id(),
            $this->lab->ownerId()->id(),
            $this->lab->name(),
            $this->lab->subject(),
            $this->lab->overview(),
            $this->lab->capacity(),
            [],
            []
//            array_map(function (Enrollment $enrollment) {
//                return new EnrollmentResource(
//                    $enrollment->learnerId()
//                );
//            }, $this->lab->enrollments()->toArray()),
//            array_map(function (Session $session) {
//                return new SessionResource(
//                    $session->sessionId()->id(),
//                    $session->title(),
//                    $session->hours()->start()->format(DateTime::ISO8601),
//                    $session->hours()->end()->format(DateTime::ISO8601),
//                    $session->outline()
//                );
//            }, $this->lab->sessions()->toArray())
        );
    }
}