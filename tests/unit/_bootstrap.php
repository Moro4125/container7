<?php

namespace Codeception;

ini_set('assert.exception', 1);

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;

/** @noinspection PhpUndefinedClassInspection */
trait AssertThrows
{
    /** @noinspection PhpConstructorStyleInspection */

    /**
     * Asserts that callback throws an exception
     *
     * @param $throws
     * @param callable $fn
     */
    public function assertThrows($throws, callable $fn)
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->assertThrowsWithMessage($throws, false, $fn);
    }

    /**
     * Asserts that callback throws an exception with a message
     *
     * @param $throws
     * @param $message
     * @param callable $fn
     */
    public function assertThrowsWithMessage($throws, $message, callable $fn)
    {
        /** @var $this TestCase  * */
        $result = $this->getTestResultObject();
        unset($result);

        if (is_array($throws)) {
            $message = ($throws[1]) ? $throws[1] : false;
            $throws = $throws[0];
        }

        is_string($message) && $message = strtolower($message);

        try {
            call_user_func($fn);
        } catch (AssertionFailedError $e) {

            if ($throws !== get_class($e)) {
                throw $e;
            }

            if ($message !== false && $message !== strtolower($e->getMessage())) {
                throw new AssertionFailedError("exception message '$message' was expected, but '" . $e->getMessage() . "' was received");
            }

        } catch (\Throwable $e) {
            if ($throws) {
                if ($throws !== get_class($e)) {
                    throw new AssertionFailedError("exception '$throws' was expected, but " . get_class($e) . ' was thrown');
                }

                if ($message !== false && $message !== strtolower($e->getMessage())) {
                    throw new AssertionFailedError("exception message '$message' was expected, but '" . $e->getMessage() . "' was received");
                }
            } else {
                /** @noinspection PhpUnhandledExceptionInspection */
                throw $e;
            }
        }

        if ($throws) {
            if (isset($e)) {
                $this->assertTrue(true, 'exception handled');
            } else {
                throw new AssertionFailedError("exception '$throws' was not thrown as expected");
            }
        }

    }
}