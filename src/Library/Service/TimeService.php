<?php
namespace MyHammer\Library\Service;

class TimeService
{

    const SECOND = 1;
    const MINUTE = 60;
    const HOUR = 3600;
    const DAY = 86400;
    const WEEK = 604800;
    const MONTH = 2592000;
    const YEAR = 31104000;

    const NAMES = [
        self::SECOND => 'second',
        self::MINUTE => 'minute',
        self::HOUR => 'hour',
        self::DAY => 'day',
        self::WEEK => 'week',
        self::MONTH => 'month',
        self::YEAR => 'year',
    ];

    private $timeSwift = 0;

    public function getPeriodName(int $seconds)
    {
        return self::NAMES[$seconds];
    }

    public function setTimeSwift(int $timeSwift)
    {
        $this->timeSwift = $timeSwift;
    }

    public function getNow(): \DateTime
    {
        return $this->getFromString('now');
    }

    public function getYesterday(): \DateTime
    {
        return $this->getFromString('yesterday');
    }

    public function getTomorrow(): \DateTime
    {
        return $this->getFromString('tomorrow');
    }

    public function getPreviewMonth(): \DateTime
    {
        return $this->getFromString('previous month');
    }


    public function isBetween(\DateTime $date, \DateTime $start, \DateTime $end)
    {
        $start = $start->getTimestamp();
        $end = $end->getTimestamp();
        $t = $date->getTimestamp();
        return $t >= $start && $t <= $end;
    }

    public function isOlderThan(?\DateTime $time, int $seconds)
    {
        $ts = $time ? $time->getTimestamp() : 0;
        $now = $this->getNow()->getTimestamp();
        return $ts + $seconds < $now;
    }

    public function getFromString(string $dateTime): \DateTime
    {
        $date = new \DateTime($dateTime, new \DateTimeZone('UTC'));
        if ($this->timeSwift) {
            $this->addSeconds($date, $this->timeSwift);
        }
        return $date;
    }

    public function getFromTimestamp(int $timestamp): \DateTime
    {
        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $date->setTimestamp($timestamp);
        return $date;
    }

    public function addSeconds(\DateTime $date, int $seconds): \DateTime
    {
        $date = clone ($date);
        $date->setTimestamp($date->getTimestamp() + $seconds);
        return $date;
    }

    public function subSeconds(\DateTime $date, int $seconds): \DateTime
    {
        $date = clone ($date);
        $date->setTimestamp($date->getTimestamp() - $seconds);
        return $date;
    }

    public function setHour(int $hour)
    {
        $this->getNow()->setTime($hour, 0);
    }

    public function addSecondsToNow(int $seconds): \DateTime
    {
        return $this->addSeconds($this->getNow(), $seconds);
    }

    public function subSecondsFromNow(int $seconds): \DateTime
    {
        return $this->subSeconds($this->getNow(), $seconds);
    }

    public function getSecondsToNow(\DateTime $date, bool $allowBelowZero = false): int
    {
        $cd = $date->getTimestamp() - ($this->getNow())->getTimestamp();
        return $allowBelowZero ? $cd : max(0, $cd);
    }

    /**
     * Returns true if date not set or time interval to now is <= 0
     *
     * @param  \DateTime $time
     * @return bool
     */
    public function isReady(?\DateTime $time): bool
    {
        if (!$time || $this->getSecondsToNow($time, false) <= 0) {
            return true;
        }
        return false;
    }

    public function getCountDown(?\DateTime $time): int
    {
        if (!$time) {
            return 0;
        }
        return $this->getSecondsToNow($time);
    }

    public function decorateInterval(int $seconds): string
    {
        $PRECISION = 1;

        $sign = '+';
        if ($seconds < 0) {
            $sign = '-';
            $seconds = -$seconds;
        }

        if ($seconds > 3600 * 24 * 2) {
            return $sign . round($seconds / (3600 * 24), $PRECISION) . 'd';
        }
        if ($seconds > 3600 * 2) {
            return $sign . round($seconds / (3600), $PRECISION) . 'h';
        }
        if ($seconds > 60 * 2) {
            return $sign . round($seconds / 60, $PRECISION) . 'min';
        }
        return $sign . $seconds . 'sec';
    }

    public function secondsToFormattedTime(int $seconds) : string
    {
        return gmdate("H:i:s", $seconds);
    }
}
