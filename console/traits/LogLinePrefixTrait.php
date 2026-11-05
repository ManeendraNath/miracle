<?php

declare(strict_types=1);

namespace console\traits;

/**
 * For scheduled commands: every output line starts with when it was written and which controller
 * method wrote it, e.g. [2026-11-05 02:00:03] [CronController::actionRunBilling] Billing completed.
 */
trait LogLinePrefixTrait
{
    public function stdout($string, ...$format)
    {
        return parent::stdout($this->withLogPrefix((string) $string), ...$format);
    }

    public function stderr($string, ...$format)
    {
        return parent::stderr($this->withLogPrefix((string) $string), ...$format);
    }

    private function withLogPrefix(string $text): string
    {
        $prefix = sprintf('[%s] [%s::%s] ', date('Y-m-d H:i:s'), (new \ReflectionClass($this))->getShortName(), $this->action->actionMethod ?? '-');

        return (string) preg_replace('/^(?=.)/m', addcslashes($prefix, '\\$'), $text);
    }
}
