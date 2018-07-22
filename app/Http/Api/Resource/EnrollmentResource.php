<?php

namespace App\Http\Api\Resource;

use JMS\Serializer\Annotation as Serializer;
use Hateoas\Configuration\Annotation as Hateoas;

/**
 * @Hateoas\Relation(
 *     "user",
 *     href = @Hateoas\Route(
 *         "users.show",
 *         parameters = {
 *             "userId" = "expr(object.learnerId())"
 *         }
 *     )
 * )
 */
class EnrollmentResource
{
    /**
     * * @Serializer\Exclude
     *
     * @var string
     */
    private $learnerId;

    /**
     * @param string $learnerId
     */
    public function __construct(string $learnerId)
    {
        $this->learnerId = $learnerId;
    }

    /**
     * @return string
     */
    public function learnerId(): string
    {
        return $this->learnerId;
    }
}
