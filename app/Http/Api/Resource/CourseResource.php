<?php

namespace App\Http\Api\Resource;

use Swagger\Annotations\Schema;
use Doctrine\Common\Collections\Collection;
use JMS\Serializer\Annotation as Serializer;
use Hateoas\Configuration\Annotation as Hateoas;

/**
 * @Hateoas\Relation(
 *     "self",
 *     href = @Hateoas\Route(
 *         "labs.show",
 *         parameters = {
 *             "labId" = "expr(object.id())"
 *         }
 *     )
 * )
 * @Hateoas\Relation(
 *     "owner",
 *     href = @Hateoas\Route(
 *         "users.show",
 *         parameters = {
 *             "userId" = "expr(object.ownerId())"
 *         }
 *     )
 * )
 */
class CourseResource
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $subject;

    /**
     * @var string
     */
    private $overview;

    /**
     * @var int
     */
    private $capacity;

    /**
     * @Serializer\Exclude
     *
     * @var string
     */
    private $ownerId;

    /**
     * @Serializer\Exclude
     *
     * @var EnrollmentResource[]
     */
    private $enrollments;

    /**
     * @var SessionResource[]
     */
    private $sessions;

    /**
     * @param string $id
     * @param string $name
     * @param SessionResource[] $sessions
     * @param EnrollmentResource[] $enrollments
     */
    public function __construct(string $id, string $ownerId, string $name, string $subject, string $overview, int $capacity, array $enrollments, array $sessions)
    {
        $this->id = $id;
        $this->ownerId = $ownerId;
        $this->name = $name;
        $this->subject = $subject;
        $this->overview = $overview;
        $this->capacity = $capacity;
        $this->enrollments = $enrollments;
        $this->sessions = $sessions;
    }

    /**
     * @return string
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function ownerId(): string
    {
        return $this->ownerId;
    }

    /**
     * @return EnrollmentResource[]
     */
    public function enrollments(): array
    {
        return $this->enrollments;
    }

    /**
     * @return SessionResource[]
     */
    public function sessions(): array
    {
        return $this->sessions;
    }
}