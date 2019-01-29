<?php

/*
 * This file is part of the hyn/multi-tenant package.
 *
 * (c) Daniël Klabbers <daniel@klabbers.email>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @see https://laravel-tenancy.com
 * @see https://github.com/hyn/multi-tenant
 */

namespace Hyn\Tenancy\Listeners\Database;

use Hyn\Tenancy\Abstracts\WebsiteEvent;
use Hyn\Tenancy\Database\Connection;
use Hyn\Tenancy\Traits\DispatchesEvents;
use Illuminate\Contracts\Events\Dispatcher;
use Hyn\Tenancy\Events;

class MigratesTenants
{
    use DispatchesEvents;
    /**
     * @var Connection
     */
    protected $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(Events\Websites\Created::class, [$this, 'migrate']);
    }

    /**
     * @param WebsiteEvent $event
     * @return bool
     */
    public function migrate(WebsiteEvent $event): bool
    {
        $paths = $this->connection->getMigrationPaths();

        $migrated_paths = 0;
        $nonExecutedMigrationsPath = [];

        foreach ($paths as $path) {
            if ($path && realpath($path) && $this->connection->migrate($event->website, $path)) {
                $migrated_paths++;
            } else {
                $nonExecutedMigrationsPath[] = $path;
            }
        }

        if ($migrated_paths == count($paths)) {
            $this->emitEvent(new Events\Websites\Migrated($event->website));
        } else {
            throw new \Exception(
                "Not all migrations were executed. The list of non executed migrations: " .
                implode(",", $nonExecutedMigrationsPath)
            );
        }

        return true;
    }
}
