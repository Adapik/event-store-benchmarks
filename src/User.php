<?php

declare(strict_types=1);

namespace Prooph\EventStoreBenchmarks;

use Prooph\EventSourcing\AggregateChanged;
use Prooph\EventSourcing\AggregateRoot;

/**
 *
 */
class User extends AggregateRoot
{
    const ID = '550e8400-e29b-41d4-a716-446655440000';

    public function aggregateId(): string
    {
        return '550e8400-e29b-41d4-a716-446655440000';
    }

    public static function create()
    {
        $self =  new self();
        $self->doSmth();

        return $self;
    }

    public function doSmth()
    {
        $this->recordThat(AggregateChanged::occur($this->aggregateId()));
    }

    /**
     * Apply given event
     */
    protected function apply(AggregateChanged $event): void
    {

    }
}
