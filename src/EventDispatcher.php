<?php

/**
 * This file is part of the nexphant Framework.
 *
 * (c) nexphant <https://github.com/nexphant>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Nexphant\Events;

/**
 * Minimal synchronous event dispatcher.
 *
 * Listeners are callables registered per event class.
 * Supports priority ordering and once-listeners.
 */
class EventDispatcher
{
    /** @var array<string, list<array{callable, int, bool}>> */
    private array $listeners = [];

    /**
     * Register a listener for an event class.
     *
     * @param string   $event     FQCN of the event class
     * @param callable $listener
     * @param int      $priority  Higher = earlier (default 0)
     * @param bool     $once      Remove after first dispatch
     */
    public function listen(string $event, callable $listener, int $priority = 0, bool $once = false): void
    {
        $this->listeners[$event][] = [$listener, $priority, $once];
        usort($this->listeners[$event], fn($a, $b) => $b[1] <=> $a[1]);
    }

    /**
     * Register a one-time listener.
     */
    public function once(string $event, callable $listener, int $priority = 0): void
    {
        $this->listen($event, $listener, $priority, true);
    }

    /**
     * Dispatch an event to all registered listeners.
     *
     * @param object $event
     * @return object  the event (possibly mutated)
     */
    public function dispatch(object $event): object
    {
        $class  = $event::class;
        $remove = [];

        foreach ($this->listeners[$class] ?? [] as $i => [$listener, , $once]) {
            $listener($event);
            if ($once) {
                $remove[] = $i;
            }
        }

        // Remove one-time listeners in reverse order to preserve indexes
        foreach (array_reverse($remove) as $i) {
            array_splice($this->listeners[$class], $i, 1);
        }

        return $event;
    }

    /**
     * Remove all listeners for an event.
     */
    public function forget(string $event): void
    {
        unset($this->listeners[$event]);
    }

    /**
     * Check if any listeners are registered for an event.
     */
    public function hasListeners(string $event): bool
    {
        return !empty($this->listeners[$event]);
    }

    /**
     * Get all registered listeners for an event.
     */
    public function getListeners(string $event): array
    {
        return array_column($this->listeners[$event] ?? [], 0);
    }
}
