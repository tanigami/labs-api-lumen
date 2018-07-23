<?php

namespace App\Http\Controllers;

use Doctrine\Common\Collections\Expr\Expression;
use Doctrine\Common\Collections\ExpressionBuilder;
use Doctrine\Common\Persistence\ManagerRegistry;
use Hateoas\Hateoas;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller as BaseController;
use League\Fractal\Manager as Fractal;
use League\Tactician\CommandBus;

class Controller extends BaseController
{
    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var CommandBus
     */
    protected $commandBus;

    /**
     * @var ExpressionBuilder
     */
    protected $expressionBuilder;

    /**
     * @var Hateoas
     */
    protected $hateoas;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param CommandBus $commandBus
     * @param ExpressionBuilder $expressionBuilder
     * @param Hateoas $hateoas
     */
    public function __construct(ManagerRegistry $managerRegistry, CommandBus $commandBus, ExpressionBuilder $expressionBuilder, Hateoas $hateoas)
    {
        $this->managerRegistry = $managerRegistry;
        $this->commandBus = $commandBus;
        $this->expressionBuilder = $expressionBuilder;
        $this->hateoas = $hateoas;
    }

    /**
     * @return Expression
     */
    protected function filteringExpressions(Request $request): array
    {
        $expressions = [];
        $filters = $request->query('filter', null);
        if (!is_null($filters)) {
            foreach ($filters as $field => $filter) {
                foreach ($filter as $operator => $value) {
                    $expressions[] = $this->expressionBuilder->$operator($field, $value);
                }
            }
        }

        return $expressions;
    }
}
