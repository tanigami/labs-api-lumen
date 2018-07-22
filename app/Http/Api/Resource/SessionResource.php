<?php

namespace App\Http\Api\Resource;

class SessionResource
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $start;

    /**
     * @var string
     */
    private $end;

    /**
     * @var string
     */
    private $outline;

    /**
     * @param string $id
     * @param string $title
     * @param string $start
     * @param string $end
     * @param string $outline
     */
    public function __construct(string $id, string $title, string $start, string $end, string $outline)
    {
        $this->id = $id;
        $this->title = $title;
        $this->start = $start;
        $this->end = $end;
        $this->outline = $outline;
    }
}
