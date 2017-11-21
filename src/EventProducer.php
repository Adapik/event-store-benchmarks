<?php

declare(strict_types=1);

namespace Prooph\EventStoreBenchmarks;
use Prooph\Common\Messaging\FQCNMessageFactory;
use Prooph\EventSourcing\Aggregate\AggregateType;
use Prooph\EventSourcing\EventStoreIntegration\AggregateTranslator;
use Prooph\EventStore\Exception\ConcurrencyException;
use Prooph\EventStore\Pdo\PersistenceStrategy\PostgresSingleStreamStrategy;
use Prooph\EventStore\Pdo\PostgresEventStore;

/**
 *
 */
class EventProducer extends \Thread
{
    private $eventsWritten;
    private $driver;
    private $executions;

    public function __construct(string $driver, int $executions)
    {
        $this->eventsWritten = 0;
        $this->driver = $driver;
        $this->executions = $executions;
    }

    public function run()
    {
        $id = $this->getThreadId();
        echo "Writer $id started\n";

        try {
            $connection = createConnection($this->driver);
            $eventStore = new PostgresEventStore(
                new FQCNMessageFactory(),
                $connection,
                new PostgresSingleStreamStrategy()
            );
            $repo = new UserRepository($eventStore, AggregateType::fromAggregateRootClass(User::class), new AggregateTranslator());

            $exceptionsCount = 0;
            for ($i = 0; $i < $this->executions; $i++) {
                $user = $repo->getAggregateRoot(User::ID);
                $user->doSmth();
                try {
                    $repo->saveAggregateRoot($user);
                } catch (ConcurrencyException $e) {
                    $exceptionsCount++;
                }
            }

            $totalEvents = $this->executions;

            echo "Failed to write $exceptionsCount from $totalEvents events\n";
        } catch (\Throwable $e) {
             echo $e->getMessage() . PHP_EOL . $e->getTraceAsString();
        }
    }
}
