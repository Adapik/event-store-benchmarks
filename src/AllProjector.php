<?php

declare(strict_types=1);

namespace Prooph\EventStoreBenchmarks;

use Prooph\Common\Messaging\Message;

class AllProjector extends \Thread
{
    private $driver;
    private $stopAt;

    public function __construct(string $driver, int $stopAt)
    {
        $this->driver = $driver;
        $this->stopAt = $stopAt;
    }

    public function run()
    {
        $id = $this->getThreadId();
        echo "Projection $id started\n";

        try {
            $connection = createConnection($this->driver);
            $eventStore = createEventStore($this->driver, $connection);
            $projectionManager = createProjectionManager($eventStore, $this->driver, $connection);

            $stopAt = $this->stopAt;

            $start = microtime(true);

            $projection = $projectionManager->createProjection('all_projection');
            $projection
                ->init(function (): array {
                    return ['count' => 0];
                })
                ->fromAll()
                ->whenAny(function (array $state, Message $event) use ($stopAt): array {
                    $state['count']++;
                    if ($state['count'] === $stopAt) {
                        $this->stop();
                    }

                    return $state;
                })
                ->run();

            $readEvents = $projection->getState()['count'];

            $end = microtime(true);

            $time = $end - $start;
            $avg = $this->stopAt / $time;

            echo "Projection $id read $readEvents events\n";
            echo "projection $id used $time seconds, avg $avg events/second\n";
        } catch (\Throwable $e) {
            echo $e->getMessage() . PHP_EOL . $e->getTraceAsString();
        }
    }
}
