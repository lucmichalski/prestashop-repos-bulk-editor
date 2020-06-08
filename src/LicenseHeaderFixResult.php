<?php


namespace Matks\PrestaShopRepoBulkEditor;


class LicenseHeaderFixResult
{
    const FIXED_FILE = 1;
    const IGNORED_FILE = 2;
    const FAILED_TO_FIX = 3;

    /** @var int */
    public $type;

    /** @var string */
    public $message;

    /** @var string */
    public $fixFiledContent;

    /**
     * @param int $type
     * @param string $message
     * @param string $fixFiledContent
     */
    public function __construct($type, $message, $fixFiledContent = null)
    {
        $this->type = $type;
        $this->message = $message;
        $this->fixFiledContent = $fixFiledContent;
    }

    /**
     * @param string $message
     *
     * @return LicenseHeaderFixResult
     */
    public static function createBlacklistedResult($message)
    {
        return new self(self::IGNORED_FILE, $message);
    }

    /**
     * @param string $fixFiledContent
     *
     * @return LicenseHeaderFixResult
     */
    public static function createSuccessResult($fixFiledContent)
    {
        return new self(self::FIXED_FILE, 'fixed', $fixFiledContent);
    }

    /**
     * @param string $message
     *
     * @return LicenseHeaderFixResult
     */
    public static function createFailedToFixResult($message = null)
    {
        if (null === $message) {
            $message = 'failed to fix';
        }

        return new self(self::FAILED_TO_FIX, $message);
    }
}
