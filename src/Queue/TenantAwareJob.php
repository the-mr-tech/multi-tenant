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

namespace Hyn\Tenancy\Queue;

use Hyn\Tenancy\Contracts\Website;
use Hyn\Tenancy\Environment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;

trait TenantAwareJob
{
    /**
     * @var int The hostname ID of the previously active tenant.
     */
    public $website_id;

    use SerializesModels {
        __sleep as serializedSleep;
        __wakeup as serializedWakeup;
    }

    public function __sleep()
    {
        /** @var Environment $environment */
        $environment = app(Environment::class);

        if (!$this->website_id && $website = $environment->tenant()) {
            $this->website_id = $website->getKey();
        }

        $attributes = $this->serializedSleep();

        return $attributes;
    }

    public function __wakeup()
    {
        if (isset($this->website_id)) {

            /** @var Environment $environment */
            $environment = app(Environment::class);

            /** @var Website $website */
            $website = app(Website::class);

            $environment->tenant($website->find($this->website_id));
        }

        $this->serializedWakeup();
    }

    /**
     * Manually override the hostname to be used.
     *
     * @param Website|int $website
     * @return $this
     */
    public function onTenant($website)
    {
        $this->website_id = $website instanceof Model ? $website->getKey() : $website;

        return $this;
    }

    /**
     * Manually sets the website_id to the current tenant running
     * This method was made to work specifically with events/queued listeners.
     * We need to manually call this method before dispatching an event with his queued listeners.
     *
     * @return $this|bool
     */
    public function onCurrentTenant()
    {
        /** @var Environment $environment */
        $environment = app(Environment::class);

        $website = $environment->tenant();
        if (!$website) {
            return false;
        }

        $this->website_id = $website->getKey();
        return $this;
    }
}
